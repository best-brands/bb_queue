<?php

namespace Tygh\Addons\Queue;

/**
 * The default job interface.
 */
interface JobInterface
{
    /**
     * Execute a job with the passed data.
     *
     * @param array $job_info
     * @param mixed $message
     *
     * @throws JobException
     */
    public function handle(array $job_info, $message): void;

    /**
     * Schedule a job.
     *
     * @param $message
     *
     * @return bool
     */
    public function schedule($message = null): bool;

    /**
     * Gets the name of the queue.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Write formatted to the standard output.
     *
     * @param string $message
     */
    public function write(string $message): void;

    /**
     * Set the job execution context.
     *
     * @param array $data
     */
    public function setContext(array $data): void;

    /**
     * Get the cron expression string.
     *
     * @return string
     */
    public function getCronExpression(): string;

    /**
     * Get the delay of the queue.
     *
     * @return int
     */
    public function getDelay(): int;

    /**
     * Get the timeout of the queue.
     *
     * @return int
     */
    public function getTimeout(): int;

    /**
     * Get the retention period of the queue.
     *
     * @return int
     */
    public function getRetentionPeriod(): int;

    /**
     * @return bool
     */
    public function hasCronExpression(): bool;
}
