<?php

namespace Tygh\Addons\Queue\Failed;

use Throwable;

interface FailedJobProviderInterface
{
    /**
     * Log a failed job into storage.
     *
     * @param string     $connection
     * @param string     $queue
     * @param string     $payload
     * @param Throwable $exception
     *
     * @return string|int|null
     */
    public function log(string $connection, string $queue, string $payload, Throwable $exception);

    /**
     * Get a list of all the failed jobs.
     *
     * @param array $params
     *
     * @return array
     */
    public function all(array $params = []): array;

    /**
     * Get a single failed job.
     *
     * @param mixed $id
     *
     * @return array|null
     */
    public function find($id): ?array;

    /**
     * Delete a single or multiple failed job from storage.
     *
     * @param mixed $id
     *
     * @return bool
     */
    public function forget($id): bool;

    /**
     * Flush all the failed jobs from storage.
     *
     * @param int|null $hours
     *
     * @return void
     */
    public function flush(?int $hours = null): void;
}
