<?php

namespace Tygh\Addons\Queue\Failed;

use Carbon\Carbon;
use DateTimeInterface;
use Throwable;
use Tygh\Database\Connection;
use Tygh\Exceptions\DatabaseException;

class DatabaseFailedJobProvider implements FailedJobProviderInterface, PrunableFailedJobProviderInterface
{
    /**
     * The connection resolver implementation.
     *
     * @var Connection
     */
    protected Connection $database;

    /**
     * Create a new database failed job provider.
     *
     * @param Connection $database
     */
    public function __construct(Connection $database)
    {
        $this->database = $database;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function log(string $connection, string $queue, string $payload, Throwable $exception): ?int
    {
        $failed_at = Carbon::now();
        $exception = (string)mb_convert_encoding($exception, 'UTF-8');

        return (int)$this->database->query('INSERT INTO ?:jobs_failed ?e', [
            'connection' => $connection,
            'queue'      => $queue,
            'payload'    => $payload,
            'exception'  => $exception,
            'failed_at'  => $failed_at,
        ]);
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function all(array $params = []): array
    {
        $default_params = [
            'page'           => 1,
            'items_per_page' => 50,
            'decode_payload' => false,
        ];

        $params = array_merge($default_params, $params);

        $sortings = [
            'id'         => '?:jobs_failed.id',
            'connection' => '?:jobs_failed.connection',
            'queue'      => '?:jobs_failed.queue',
            'failed_at'  => '?:jobs_failed.failed_at',
        ];

        $sorting = db_sort($params, $sortings, 'id', 'desc');
        $condition = '';

        if (!empty($params['connection'])) {
            $condition .= $this->database->quote(' AND ?:jobs_failed.connection = ?s', $params['connection']);
        }

        if (!empty($params['queue'])) {
            $condition .= $this->database->quote(' AND ?:jobs_failed.queue = ?s', $params['queue']);
        }

        if (!empty($params['job_ids'])) {
            $condition .= $this->database->quote('AND ?:jobs_failed.id IN (?n)', $params['job_ids']);
        }

        $limit = '';

        if (!empty($params['items_per_page'])) {
            $params['total_items'] = $this->database->getField(
                'SELECT COUNT(?:jobs_failed.id) FROM ?:jobs_failed WHERE 1 ?p',
                $condition,
            );
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }

        $failed_jobs = $this->database->getArray(
            'SELECT * FROM ?:jobs_failed WHERE 1 ?p ?p ?p',
            $condition,
            $sorting,
            $limit,
        );

        if (!empty($params['decode_payload'])) {
            foreach ($failed_jobs as &$job) {
                $job['decoded_payload'] = json_decode($job['payload'], true);
            }
        }

        return [$failed_jobs, $params];
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function find($id): ?array
    {
        return $this->database->getRow(
            'SELECT * FROM ?:jobs_failed WHERE id = ?i', $id
        );
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function forget($id): bool
    {
        return $this->database->query('DELETE FROM ?:jobs_failed WHERE id IN (?n)', $id) > 0;
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function flush(?int $hours = null): void
    {
        $condition = '';

        if ($hours) {
            $condition = $this->database->quote(' AND failed_at <= ?i ', Carbon::now()->subHours($hours));
        }

        $this->database->query('DELETE FROM ?:jobs_failed WHERE 1 ?p', $condition);
    }

    /**
     * @inheritDoc
     * @throws DatabaseException
     */
    public function prune(DateTimeInterface $before): int
    {
        return (int)$this->database->query(
            'DELETE FROM ?:jobs_failed WHERE failed_at < ?i',
            $before->getTimestamp()
        );
    }
}
