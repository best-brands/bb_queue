<?php

namespace Tygh\Addons\Queue;

class WorkerOptions
{
    /**
     * The name of the worker.
     *
     * @var string
     */
    public string $name;

    /**
     * The number of seconds to wait before retrying a job that encountered an uncaught exception.
     *
     * @var int
     */
    public int $backoff;

    /**
     * The maximum amount of RAM the worker may consume.
     *
     * @var int
     */
    public int $memory;

    /**
     * The maximum number of seconds a child worker may run.
     *
     * @var int
     */
    public int $timeout;

    /**
     * The number of seconds to wait in between polling the queue.
     *
     * @var int
     */
    public int $sleep;

    /**
     * The number of seconds to rest between jobs.
     *
     * @var int
     */
    public int $rest;

    /**
     * The maximum amount of times a job may be attempted.
     *
     * @var int
     */
    public int $max_tries;

    /**
     * Indicates if the worker should run in maintenance mode.
     *
     * @var bool
     */
    public bool $force;

    /**
     * Indicates if the worker should stop when the queue is empty.
     *
     * @var bool
     */
    public bool $stop_when_empty;

    /**
     * The maximum number of jobs to run.
     *
     * @var int
     */
    public int $max_jobs;

    /**
     * The maximum number of seconds a worker may live.
     *
     * @var int
     */
    public int $max_time;

    /**
     * Create a new worker options instance.
     *
     * @param string $name
     * @param int    $backoff
     * @param int    $memory
     * @param int    $timeout
     * @param int    $sleep
     * @param int    $max_tries
     * @param bool   $force
     * @param bool   $stop_when_empty
     * @param int    $max_jobs
     * @param int    $max_time
     * @param int    $rest
     *
     * @return void
     */
    public function __construct(
        string $name = 'default',
        int $backoff = 0,
        int $memory = 128,
        int $timeout = 60,
        int $sleep = 3,
        int $max_tries = 1,
        bool $force = false,
        bool $stop_when_empty = false,
        int $max_jobs = 0,
        int $max_time = 0,
        int $rest = 0
    ) {
        $this->name            = $name;
        $this->backoff         = $backoff;
        $this->sleep           = $sleep;
        $this->rest            = $rest;
        $this->force           = $force;
        $this->memory          = $memory;
        $this->timeout         = $timeout;
        $this->max_tries       = $max_tries;
        $this->stop_when_empty = $stop_when_empty;
        $this->max_jobs        = $max_jobs;
        $this->max_time        = $max_time;
    }
}
