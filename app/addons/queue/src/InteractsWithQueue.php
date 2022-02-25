<?php

namespace Tygh\Addons\Queue;

use Carbon\Carbon;
use Throwable;
use Tygh\Addons\Queue\Jobs\JobInterface;

trait InteractsWithQueue
{
    /**
     * The underlying queue job instance.
     *
     * @var Jobs\JobInterface
     */
    public Jobs\JobInterface $job;

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return $this->job ? $this->job->attempts() : 1;
    }

    /**
     * Delete the job from the queue.
     *
     * @return void
     */
    public function delete(): void
    {
        if ($this->job) {
            $this->job->delete();
        }
    }

    /**
     * Fail the job from the queue.
     *
     * @param Throwable|null $exception
     *
     * @return void
     */
    public function fail(?Throwable $exception = null): void
    {
        if ($this->job) {
            $this->job->fail($exception);
        }
    }

    /**
     * Release the job back into the queue.
     *
     * @param int $delay
     *
     * @return void
     */
    public function release(int $delay = 0): void
    {
        if ($this->job) {
            $this->job->release($delay);
        }
    }

    /**
     * Set the base queue job instance.
     *
     * @param Jobs\JobInterface $job
     *
     * @return $this
     */
    public function setJob(Jobs\JobInterface $job): self
    {
        $this->job = $job;

        return $this;
    }
}
