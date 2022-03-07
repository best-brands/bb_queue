<?php

namespace Tygh\Addons\Queue\Jobs;

use Throwable;

interface JobInterface
{
    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId(): string;

    /**
     * Get the decoded body of the job.
     *
     * @return array
     */
    public function payload(): array;

    /**
     * Fire the job.
     *
     * @return void
     */
    public function fire(): void;

    /**
     * Release the job back into the queue.
     *
     * Accepts a delay specified in seconds.
     *
     * @param int $delay
     *
     * @return void
     */
    public function release(int $delay = 0): void;

    /**
     * Determine if the job was released back into the queue.
     *
     * @return bool
     */
    public function isReleased(): bool;

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void;

    /**
     * Determine if the job has been deleted.
     *
     * @return bool
     */
    public function isDeleted(): bool;

    /**
     * Determine if the job has been deleted or released.
     *
     * @return bool
     */
    public function isDeletedOrReleased(): bool;

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int;

    /**
     * Determine if the job has been marked as a failure.
     *
     * @return bool
     */
    public function hasFailed(): bool;

    /**
     * Mark the job as "failed".
     *
     * @return void
     */
    public function markAsFailed(): void;

    /**
     * Delete the job, call the "failed" method, and raise the failed job event.
     *
     * @param Throwable|null $e
     *
     * @return void
     */
    public function fail(?Throwable $e = null): void;

    /**
     * Determine if the job should fail when it timeouts.
     *
     * @return bool
     */
    public function shouldFailOnTimeout(): bool;

    /**
     * Get the number of times to attempt a job.
     *
     * @return int|null
     */
    public function maxTries(): ?int;

    /**
     * Get the maximum number of exceptions allowed, regardless of attempts.
     *
     * @return int|null
     */
    public function maxExceptions(): ?int;

    /**
     * Get the number of seconds the job can run.
     *
     * @return int|null
     */
    public function timeout(): ?int;

    /**
     * Get the timestamp indicating when the job should timeout.
     *
     * @return int|null
     */
    public function retryUntil(): ?int;

    /**
     * Get the name of the queued job class.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the resolved name of the queued job class.
     *
     * Resolves the name of "wrapped" jobs such as class-based handlers.
     *
     * @return string
     */
    public function resolveName(): string;

    /**
     * Get the name of the connection the job belongs to.
     *
     * @return string
     */
    public function getConnectionName(): string;

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue(): string;

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody(): string;
}
