<?php

namespace Tygh\Addons\Queue\Adapters;

use Tygh\Addons\Queue\Jobs\Job;
use Tygh\Addons\Queue\Jobs\JobInterface;

interface AdapterInterface
{
    /**
     * Get the size of the queue.
     *
     * @param string|null $queue
     *
     * @return int
     */
    public function size(?string $queue = null): int;

    /**
     * Push a new job onto the queue.
     *
     * @param string|object $job
     * @param mixed         $data
     * @param string|null   $queue
     *
     * @return mixed
     */
    public function push($job, $data = '', ?string $queue = null);

    /**
     * Push a new job onto the queue.
     *
     * @param string $queue
     * @param string $job
     * @param mixed  $data
     *
     * @return mixed
     */
    public function pushOn(string $queue, string $job, $data = '');

    /**
     * Push a raw payload onto the queue.
     *
     * @param string      $payload
     * @param string|null $queue
     * @param array       $options
     *
     * @return mixed
     */
    public function pushRaw(string $payload, ?string $queue = null, array $options = []);

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param string|object                        $job
     * @param mixed                                $data
     * @param string|null                          $queue
     *
     * @return mixed
     */
    public function later($delay, $job, $data = '', ?string $queue = null);

    /**
     * Push a new job onto the queue after a delay.
     *
     * @param string                               $queue
     * @param \DateTimeInterface|\DateInterval|int $delay
     * @param string                               $job
     * @param mixed                                $data
     *
     * @return mixed
     */
    public function laterOn(string $queue, $delay, string $job, $data = '');

    /**
     * Pop the next job off of the queue.
     *
     * @param string|null $queue
     *
     * @return Job|null
     */
    public function pop(string $queue = null): ?JobInterface;

    /**
     * Get the connection name for the queue.
     *
     * @return string
     */
    public function getConnectionName(): string;

    /**
     * Set the connection name for the queue.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setConnectionName(string $name): AdapterInterface;

    /**
     * Delete all the jobs from the queue.
     *
     * @param  string  $queue
     * @return int
     */
    public function clear(string $queue): int;
}
