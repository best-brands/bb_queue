<?php

namespace Tygh\Addons\Queue\Adapters;

use Carbon\Carbon;
use Tygh\Addons\Queue\InvalidPayloadException;
use Tygh\Addons\Queue\Jobs\DatabaseJob;
use Tygh\Addons\Queue\Jobs\Job;
use Tygh\Addons\Queue\Jobs\JobRecord;
use Tygh\Database\Connection;
use Tygh\Exceptions\DatabaseException;

class DatabaseAdapter extends Adapter implements AdapterInterface
{
    /**
     * The database connection instance.
     *
     * @var Connection
     */
    protected Connection $database;

    /**
     * The name of the default queue.
     *
     * @var string
     */
    protected string $default;

    /**
     * The expiration time of a job.
     *
     * @var int|null
     */
    protected ?int $retry_after = 60;

    /**
     * @param Connection $database
     * @param string     $default
     * @param int        $retry_after
     */
    public function __construct(
        Connection $database,
        string $default = 'default',
        int $retry_after = 60
    ) {
        $this->default     = $default;
        $this->database    = $database;
        $this->retry_after = $retry_after;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function size(?string $queue = null): int
    {
        return $this->database->getField(
            'SELECT COUNT(*) FROM ?:jobs WHERE queue = ?s', $this->getQueue($queue)
        );
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function pop(?string $queue = null): ?Job
    {
        $queue = $this->getQueue($queue);

        return $this->database->transaction(function () use ($queue) {
            if ($job = $this->getNextAvailableJob($queue)) {
                return $this->marshalJob($queue, $job);
            }

            return null;
        });
    }

    /**
     * @inheritDoc
     * @throws InvalidPayloadException
     * @throws DatabaseException
     */
    public function push($job, $data = '', ?string $queue = null)
    {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data));
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function pushRaw(string $payload, string $queue = null, array $options = [])
    {
        return $this->pushToDatabase($queue, $payload);
    }

    /**
     * @inheritDoc
     * @throws InvalidPayloadException
     * @throws DatabaseException
     */
    public function later($delay, $job, $data = '', ?string $queue = null)
    {
        return $this->pushToDatabase($queue, $this->createPayload($job, $data), $delay);
    }

    /**
     * Push a raw payload to the database with a given delay.
     *
     * @param string|null                          $queue
     * @param string                               $payload
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param int                                  $attempts
     *
     * @return int
     * @throws DatabaseException
     */
    protected function pushToDatabase(?string $queue, string $payload, $delay = 0, int $attempts = 0): int
    {
        return (int)$this->database->query(
            'INSERT INTO ?:jobs ?e',
            $this->buildDatabaseData($this->getQueue($queue), $payload, $this->availableAt($delay), $attempts)
        );
    }

    /**
     * Insert a record into the database
     *
     * @param string $queue
     * @param string $payload
     * @param int    $available_at
     * @param int    $attempts
     *
     * @return array
     */
    protected function buildDatabaseData(string $queue, string $payload, int $available_at, int $attempts = 0): array
    {
        return [
            'queue'        => $queue,
            'attempts'     => $attempts,
            'reserved_at'  => null,
            'available_at' => $available_at,
            'created_at'   => $this->currentTime(),
            'payload'      => $payload,
        ];
    }

    /**
     * Get the next available job for the queue.
     *
     * @param string|null $queue
     *
     * @return ?JobRecord
     * @throws DatabaseException
     */
    protected function getNextAvailableJob(?string $queue): ?JobRecord
    {
        $lock = 'FOR UPDATE';

        if ($this->container->get('addons.queue.config')['tweaks']['skip_locked'] ?? false) {
            $lock = 'FOR UPDATE SKIP LOCKED';
        }

        $job = $this->database->getRow(
            'SELECT * FROM ?:jobs'
            . ' WHERE queue = ?s AND ('
            . ' (reserved_at IS NULL AND available_at <= ?i)'
            . ' OR (reserved_at <= ?i)'
            . ' )'
            . ' ORDER BY id ASC'
            . ' LIMIT 1'
            . ' ?p',
            $this->getQueue($queue),
            $this->currentTime(),
            Carbon::now()->subSeconds($this->retry_after)->getTimestamp(),
            $lock,
        );

        return $job ? new JobRecord((object)$job) : null;
    }

    /**
     * Marshal the reserved job into a DatabaseJob instance.
     *
     * @param string    $queue
     * @param JobRecord $job
     *
     * @return DatabaseJob
     * @throws DatabaseException
     */
    protected function marshalJob(string $queue, JobRecord $job): DatabaseJob
    {
        $job = $this->markJobAsReserved($job);

        return new DatabaseJob(
            $this->container, $this, $job, $this->connection_name, $queue
        );
    }

    /**
     * Mark the given job ID as reserved.
     *
     * @param JobRecord $job
     *
     * @return JobRecord
     * @throws DatabaseException
     */
    protected function markJobAsReserved(JobRecord $job): JobRecord
    {
        $this->database->query('UPDATE ?:jobs SET ?u WHERE id = ?i', [
            'reserved_at' => $job->touch(),
            'attempts'    => $job->increment(),
        ], $job->id);

        return $job;
    }

    /**
     * Delete a reserved job from the reserved queue and release it.
     *
     * @param string      $queue
     * @param DatabaseJob $job
     * @param int         $delay
     *
     * @return void
     * @throws DatabaseException
     */
    public function deleteAndRelease(string $queue, DatabaseJob $job, int $delay)
    {
        $this->database->transaction(function () use ($queue, $job, $delay) {
            db_query("DELETE FROM ?:jobs WHERE id = ?i", $job->getJobId());
            $this->release($queue, $job->getJobRecord(), $delay);
        });
    }

    /**
     * Release a reserved job back onto the queue.
     *
     * @param string    $queue
     * @param JobRecord $job
     * @param int       $delay
     *
     * @return int
     * @throws DatabaseException
     */
    public function release(string $queue, JobRecord $job, int $delay): int
    {
        return $this->pushToDatabase($queue, $job->payload, $delay, $job->attempts);
    }

    /**
     * Delete a reserved job from the queue.
     *
     * @param string $queue
     * @param string $id
     *
     * @return void
     *
     * @throws \Throwable
     */
    public function deleteReserved($queue, string $id)
    {
        $this->database->transaction(function () use ($id) {
            db_query("DELETE FROM ?:jobs WHERE id = ?i", $id);
        });
    }

    /**
     * Get the queue or return the default.
     *
     * @param string|null $queue
     *
     * @return string
     */
    public function getQueue(?string $queue): string
    {
        return $queue ?: $this->default;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function clear(string $queue): int
    {
        return (int)$this->database->query(
            'DELETE FROM ?:jobs WHERE queue = ?s', $this->getQueue($queue)
        );
    }

    /**
     * Get the underlying database instance.
     *
     * @return Connection
     */
    public function getDatabase(): Connection
    {
        return $this->database;
    }
}
