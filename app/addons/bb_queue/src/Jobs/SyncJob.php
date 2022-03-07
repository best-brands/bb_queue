<?php

namespace Tygh\Addons\Queue\Jobs;

use Tygh\Application;

class SyncJob extends Job implements JobInterface
{
    /**
     * The class name of the job.
     *
     * @var string
     */
    protected string $job;

    /**
     * The queue message data.
     *
     * @var string
     */
    protected string $payload;

    /**
     * Create a new job instance.
     *
     * @param Application $container
     * @param string      $payload
     * @param string      $connection_name
     * @param string      $queue
     */
    public function __construct(Application $container, string $payload, string $connection_name, string $queue)
    {
        $this->queue           = $queue;
        $this->payload         = $payload;
        $this->container       = $container;
        $this->connection_name = $connection_name;
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
        parent::release($delay);
    }

    /**
     * Get the number of times the job has been attempted.
     *
     * @return int
     */
    public function attempts(): int
    {
        return 1;
    }

    /**
     * Get the job identifier.
     *
     * @return string
     */
    public function getJobId(): string
    {
        return '';
    }

    /**
     * Get the raw body string for the job.
     *
     * @return string
     */
    public function getRawBody(): string
    {
        return $this->payload;
    }

    /**
     * Get the name of the queue the job belongs to.
     *
     * @return string
     */
    public function getQueue(): string
    {
        return 'sync';
    }
}
