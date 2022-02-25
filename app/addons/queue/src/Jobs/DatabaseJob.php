<?php

namespace Tygh\Addons\Queue\Jobs;

use Throwable;
use Tygh\Addons\Queue\Adapters\DatabaseAdapter;
use Tygh\Application;
use Tygh\Database\Connection;
use Tygh\Exceptions\DatabaseException;

class DatabaseJob extends Job implements JobInterface
{
    /**
     * The database queue instance.
     *
     * @var Connection
     */
    protected $database;

    /**
     * The database job payload.
     *
     * @var JobRecord
     */
    protected JobRecord $job;

    /**
     * Create a new job instance.
     *
     * @param Application     $container
     * @param DatabaseAdapter $database
     * @param JobRecord       $job
     * @param string          $connection_name
     * @param string          $queue
     *
     * @return void
     */
    public function __construct(
        Application $container,
        DatabaseAdapter $database,
        JobRecord $job,
        string $connection_name,
        string $queue
    ) {
        $this->job             = $job;
        $this->queue           = $queue;
        $this->database        = $database;
        $this->container       = $container;
        $this->connection_name = $connection_name;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function release(int $delay = 0): void
    {
        parent::release($delay);
        $this->database->deleteAndRelease($this->queue, $this, $delay);
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function delete(): void
    {
        parent::delete();
        $this->database->deleteReserved($this->queue, $this->job->id);
    }

    /**
     * @inheritDoc
     */
    public function attempts(): int
    {
        return $this->job->attempts;
    }

    /**
     * @inheritDoc
     */
    public function getJobId(): string
    {
        return $this->job->id;
    }

    /**
     * @inheritDoc
     */
    public function getRawBody(): string
    {
        return $this->job->payload;
    }

    /**
     * Get the job record.
     *
     * @return JobRecord
     */
    public function getJobRecord(): JobRecord
    {
        return $this->job;
    }
}
