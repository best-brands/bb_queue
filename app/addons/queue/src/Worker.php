<?php

namespace Tygh\Addons\Queue;

use Carbon\Carbon;
use Throwable;
use Tygh\Addons\Queue\Adapters\AdapterInterface;
use Tygh\Addons\Queue\Failed\FailedJobProviderInterface;
use Tygh\Addons\Queue\Jobs\JobInterface;

/**
 * A worker that will poll for 1 minute for jobs.
 */
class Worker
{
    const EXIT_SUCCESS = 0;
    const EXIT_ERROR = 1;
    const EXIT_MEMORY_LIMIT = 12;

    /**
     * The name of the worker.
     *
     * @var string
     */
    protected string $name;

    /**
     * The queue manager instance.
     *
     * @var Manager
     */
    protected Manager $manager;

    /**
     * @var FailedJobProviderInterface
     */
    protected FailedJobProviderInterface $failed_job_provider;

    /**
     * The callback used to reset the application's scope.
     *
     * @var callable
     */
    protected $reset_scope;

    /**
     * Indicates if the worker should exit.
     *
     * @var bool
     */
    public bool $should_quit = false;

    /**
     * Indicates if the worker is paused.
     *
     * @var bool
     */
    public bool $paused = false;

    /**
     * Create the worker
     *
     * @param Manager                    $manager
     * @param FailedJobProviderInterface $failed_job_provider
     * @param callable|null              $reset_scope
     */
    public function __construct(
        Manager $manager,
        FailedJobProviderInterface $failed_job_provider,
        callable $reset_scope = null
    ) {
        $this->manager             = $manager;
        $this->failed_job_provider = $failed_job_provider;
        $this->reset_scope         = $reset_scope;
    }

    /**
     * Listen to the given queue in a loop.
     *
     * @param string        $connection_name
     * @param string        $queue
     * @param WorkerOptions $options
     *
     * @return int
     */
    public function daemon(string $connection_name, string $queue, WorkerOptions $options): int
    {
        if ($supports_async_signals = $this->supportsAsyncSignals()) {
            $this->listenForSignals();
        }

        $last_restart = $this->getTimestampOfLastQueueRestart();

        [$start_time, $jobs_processed] = [hrtime(true) / 1e9, 0];

        while (true) {
            if (!$this->daemonShouldRun()) {
                $status = $this->pauseWorker($options, $last_restart);

                if (!is_null($status)) {
                    return $this->stop($status);
                }

                continue;
            }

            if (isset($this->reset_scope)) {
                ($this->reset_scope)();
            }

            $job = $this->getNextJob(
                $this->manager->connection($connection_name), $queue
            );

            if ($supports_async_signals) {
                $this->registerTimeoutHandler($job, $options);
            }

            // If the daemon should run (not in maintenance mode, etc.), then we can run
            // fire off this job for processing. Otherwise, we will need to sleep the
            // worker so no more jobs are processed until they should be processed.
            if ($job) {
                $jobs_processed++;

                $this->runJob($job, $connection_name, $options);

                if ($options->rest > 0) {
                    $this->sleep($options->rest);
                }
            } else {
                $this->sleep($options->sleep);
            }

            if ($supports_async_signals) {
                $this->resetTimeoutHandler();
            }

            $status = $this->stopIfNecessary(
                $options, $last_restart, $start_time, $jobs_processed, $job
            );

            if (!is_null($status)) {
                return $this->stop($status);
            }
        }
    }

    /**
     * Get the next job from the queue connection.
     *
     * @param AdapterInterface $connection
     * @param string           $queue
     *
     * @return Jobs\JobInterface
     */
    protected function getNextJob(AdapterInterface $connection, string $queue): ?Jobs\JobInterface
    {
        $pop_job_callback = function ($queue) use ($connection) {
            return $connection->pop($queue);
        };

        try {
            foreach (explode(',', $queue) as $queue) {
                if (!is_null($job = $pop_job_callback($queue))) {
                    return $job;
                }
            }
        } catch (Throwable $e) {
            $this->sleep(1);
        }

        return null;
    }

    /**
     * Process the given job.
     *
     * @param Jobs\JobInterface $job
     * @param string            $connection_name
     * @param WorkerOptions     $options
     *
     * @return void
     */
    protected function runJob(Jobs\JobInterface $job, string $connection_name, WorkerOptions $options): void
    {
        try {
            $this->process($connection_name, $job, $options);
        } catch (Throwable $e) {
            fn_set_hook('queue_run_job_exception', $job, $e);

        }
    }

    /**
     * Process the given job from the queue.
     *
     * @param string            $connection_name
     * @param Jobs\JobInterface $job
     * @param WorkerOptions     $options
     *
     * @return void
     *
     * @throws Throwable
     */
    public function process(string $connection_name, Jobs\JobInterface $job, WorkerOptions $options)
    {
        try {
            $this->raiseBeforeJobEvent($connection_name, $job);

            $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
                $connection_name, $job, $options->max_tries
            );

            if ($job->isDeleted()) {
                $this->raiseAfterJobEvent($connection_name, $job);
            }

            $job->fire();

            $this->raiseAfterJobEvent($connection_name, $job);
        } catch (Throwable $e) {
            $this->handleJobException($connection_name, $job, $options, $e);
        }
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * This will likely be because the job previously exceeded a timeout.
     *
     * @param string       $connection_name
     * @param JobInterface $job
     * @param int          $max_tries
     *
     * @return void
     *
     * @throws Throwable
     */
    protected function markJobAsFailedIfAlreadyExceedsMaxAttempts(
        string $connection_name, JobInterface $job, int $max_tries
    ): void {
        $max_tries = !is_null($job->maxTries()) ? $job->maxTries() : $max_tries;
        $retry_until = $job->retryUntil();

        if ($retry_until && Carbon::now()->getTimestamp() <= $retry_until) {
            return;
        }

        if (!$retry_until && ($max_tries === 0 || $job->attempts() <= $max_tries)) {
            return;
        }

        $this->failJob($job, $e = $this->maxAttemptsExceededException($job));

        throw $e;
    }

    /**
     * Trigger the before queue job hook.
     *
     * @param string       $connection_name
     * @param JobInterface $job
     *
     * @return void
     */
    protected function raiseBeforeJobEvent(string $connection_name, JobInterface $job)
    {
        $this->writeOutput($job, 'starting');

        /**
         * Gets triggered once a job has been processed.
         *
         * @param string       $connection_name
         * @param JobInterface $job
         */
        fn_set_hook('queue_job_processing', $connection_name, $job);
    }

    /**
     * Trigger the after queue job hook.
     *
     * @param string       $connection_name
     * @param JobInterface $job
     *
     * @return void
     */
    protected function raiseAfterJobEvent(string $connection_name, JobInterface $job)
    {
        $this->writeOutput($job, 'success');

        /**
         * Gets triggered once a job has been processed.
         *
         * @param string       $connection_name
         * @param JobInterface $job
         */
        fn_set_hook('queue_job_processed', $connection_name, $job);
    }

    /**
     * Raise the exception occurred queue job event.
     *
     * @param string       $connection_name
     * @param JobInterface $job
     * @param Throwable    $e
     *
     * @return void
     */
    protected function raiseExceptionOccurredJobEvent(string $connection_name, JobInterface $job, Throwable $e)
    {
        $this->writeOutput($job, 'failed');

        /**
         * Gets triggered once a job has been processed.
         *
         * @param string       $connection_name
         * @param JobInterface $job
         */
        fn_set_hook('queue_job_exception_occurred', $connection_name, $job, $e);
    }

    /**
     * Calculate the backoff for the given job.
     *
     * @param Jobs\JobInterface $job
     * @param WorkerOptions     $options
     *
     * @return int
     */
    protected function calculateBackoff(Jobs\JobInterface $job, WorkerOptions $options): int
    {
        $backoff = explode(
            ',',
            method_exists($job, 'backoff') && !is_null($job->backoff())
                ? $job->backoff()
                : $options->backoff
        );

        return (int)($backoff[$job->attempts() - 1] ?? $backoff[count($backoff) - 1]);
    }

    /**
     * Handle an exception that occurred while the job was running.
     *
     * @param string            $connection_name
     * @param Jobs\JobInterface $job
     * @param WorkerOptions     $options
     * @param Throwable         $e
     *
     * @return void
     *
     * @throws Throwable
     */
    protected function handleJobException(
        string $connection_name,
        Jobs\JobInterface $job,
        WorkerOptions $options,
        Throwable $e
    ) {
        try {
            // First, we will go ahead and mark the job as failed if it will exceed the maximum
            // attempts it is allowed to run the next time we process it. If so we will just
            // go ahead and mark it as failed now, so we do not have to release this again.
            if (!$job->hasFailed()) {
                $this->markJobAsFailedIfWillExceedMaxAttempts(
                    $connection_name, $job, $options->max_tries, $e
                );
            }

            $this->raiseExceptionOccurredJobEvent(
                $connection_name, $job, $e
            );
        } finally {
            // If we catch an exception, we will attempt to release the job back onto the queue,
            // so it is not lost entirely. This will let the job be retried at a later time by
            // another listener (or this same one). We will re-throw this exception after.
            if (!$job->isDeleted() && !$job->isReleased() && !$job->hasFailed()) {
                $job->release($this->calculateBackoff($job, $options));
            }
        }

        throw $e;
    }

    /**
     * Register the worker timeout handler.
     *
     * @param JobInterface|null $job
     * @param WorkerOptions     $options
     *
     * @return void
     */
    protected function registerTimeoutHandler(?Jobs\JobInterface $job, WorkerOptions $options)
    {
        // We will register a signal handler for the alarm signal so that we can kill this
        // process if it is running too long because it has frozen. This uses the async
        // signals supported in recent versions of PHP to accomplish it conveniently.
        pcntl_signal(SIGALRM, function () use ($job, $options) {
            if ($job) {
                $this->markJobAsFailedIfWillExceedMaxAttempts(
                    $job->getConnectionName(), $job, $options->max_tries, $e = $this->maxAttemptsExceededException($job)
                );

                $this->markJobAsFailedIfItShouldFailOnTimeout(
                    $job->getConnectionName(), $job, $e
                );
            }

            $this->kill(static::EXIT_ERROR);
        });

        pcntl_alarm(
            max($this->timeoutForJob($job, $options), 0)
        );
    }

    /**
     * Create an instance of MaxAttemptsExceededException.
     *
     * @param Jobs\JobInterface $job
     *
     * @return MaxAttemptsExceededException
     */
    protected function maxAttemptsExceededException(JobInterface $job): MaxAttemptsExceededException
    {
        return new MaxAttemptsExceededException(
            $job->resolveName() . ' has been attempted too many times or run too long. The job may have previously timed out.'
        );
    }

    /**
     * Mark the given job as failed if it has exceeded the maximum allowed attempts.
     *
     * @param string            $connection_name
     * @param Jobs\JobInterface $job
     * @param int               $max_tries
     * @param Throwable         $e
     *
     * @return void
     */
    protected function markJobAsFailedIfWillExceedMaxAttempts($connection_name, JobInterface $job, int $max_tries, Throwable $e)
    {
        $max_tries = !is_null($job->maxTries()) ? $job->maxTries() : $max_tries;

        if ($job->retryUntil() && $job->retryUntil() <= Carbon::now()->getTimestamp()) {
            $this->failJob($job, $e);
        }

        if (!$job->retryUntil() && $max_tries > 0 && $job->attempts() >= $max_tries) {
            $this->failJob($job, $e);
        }
    }

    /**
     * Mark the given job as failed if it should fail on timeouts.
     *
     * @param                   $connection_name
     * @param Jobs\JobInterface $job
     * @param Throwable         $e
     *
     * @return void
     */
    protected function markJobAsFailedIfItShouldFailOnTimeout($connection_name, Jobs\JobInterface $job, Throwable $e)
    {
        if ($job->shouldFailOnTimeout()) {
            $this->failJob($job, $e);
        }
    }

    /**
     * Reset the worker timeout handler.
     *
     * @return void
     */
    protected function resetTimeoutHandler()
    {
        pcntl_alarm(0);
    }

    /**
     * Get the appropriate timeout for the given job.
     *
     * @param JobInterface|null $job
     * @param WorkerOptions     $options
     *
     * @return int
     */
    protected function timeoutForJob(?Jobs\JobInterface $job, WorkerOptions $options): int
    {
        return $job && !is_null($job->timeout()) ? $job->timeout() : $options->timeout;
    }

    /**
     * Determine if the daemon should process on this iteration.
     *
     * @return bool
     */
    protected function daemonShouldRun(): bool
    {
        return !$this->paused;
    }

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param int|float $seconds
     *
     * @return void
     */
    public function sleep($seconds)
    {
        if ($seconds < 1) {
            usleep($seconds * 1000000);
        } else {
            sleep($seconds);
        }
    }

    /**
     * Pause the worker for the current loop.
     *
     * @param WorkerOptions $options
     * @param int           $last_restart
     *
     * @return int|null
     */
    protected function pauseWorker(WorkerOptions $options, int $last_restart): ?int
    {
        $this->sleep($options->sleep > 0 ? $options->sleep : 1);
        return $this->stopIfNecessary($options, $last_restart);
    }

    /**
     * Determine the exit code to stop the process if necessary.
     *
     * @param WorkerOptions $options
     * @param int|null      $last_restart
     * @param int|null      $start_time
     * @param int|null      $job_processed
     * @param mixed         $job
     *
     * @return int|null
     */
    protected function stopIfNecessary(
        WorkerOptions $options,
        ?int $last_restart = 0,
        ?int $start_time = 0,
        ?int $job_processed = 0,
        $job = null
    ): ?int {
        if ($this->should_quit) {
            return static::EXIT_SUCCESS;
        } elseif ($this->memoryExceeded($options->memory)) {
            return static::EXIT_MEMORY_LIMIT;
        } elseif ($this->queueShouldRestart($last_restart)) {
            return static::EXIT_SUCCESS;
        } elseif ($options->stop_when_empty && is_null($job)) {
            return static::EXIT_SUCCESS;
        } elseif ($options->max_time && hrtime(true) / 1e9 - $start_time >= $options->max_time) {
            return static::EXIT_SUCCESS;
        } elseif ($options->max_jobs && $job_processed >= $options->max_jobs) {
            return static::EXIT_SUCCESS;
        }

        return null;
    }

    /**
     * Determine if the queue worker should restart.
     *
     * @param int|null $last_restart
     *
     * @return bool
     */
    protected function queueShouldRestart(?int $last_restart): bool
    {
        return $this->getTimestampOfLastQueueRestart() != $last_restart;
    }

    /**
     * Determine if the memory limit has been exceeded.
     *
     * @param int $memory_limit
     *
     * @return bool
     */
    public function memoryExceeded(int $memory_limit): bool
    {
        return (memory_get_usage(true) / 1024 / 1024) >= $memory_limit;
    }

    /**
     * Get the last queue restart timestamp, or null.
     *
     * @return int|null
     */
    protected function getTimestampOfLastQueueRestart(): ?int
    {
        return null;
    }

    /**
     * Enable async signals for the process.
     *
     * @return void
     */
    protected function listenForSignals()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, function () {
            $this->shouldQuit = true;
        });

        pcntl_signal(SIGUSR2, function () {
            $this->paused = true;
        });

        pcntl_signal(SIGCONT, function () {
            $this->paused = false;
        });
    }

    /**
     * Write the status output for the queue worker.
     *
     * @param JobInterface $job
     * @param string       $status
     *
     * @return void
     */
    protected function writeOutput(JobInterface $job, string $status): void
    {
        switch ($status) {
        case 'starting':
            $this->writeStatus($job, 'Processing');
            break;
        case 'success':
            $this->writeStatus($job, 'Processed');
            break;
        case 'failed':
            $this->writeStatus($job, 'Failed');
            break;
        }
    }

    /**
     * Format the status output for the queue worker.
     *
     * @param JobInterface $job
     * @param string       $status
     *
     * @return void
     */
    protected function writeStatus(JobInterface $job, string $status)
    {
        echo sprintf(
            "[%s][%s] %s %s" . PHP_EOL,
            Carbon::now()->format('Y-m-d H:i:s'),
            $job->getJobId(),
            str_pad("$status:", 11),
            $job->resolveName()
        );
    }

    /**
     * Mark the given job as failed and raise the relevant event.
     *
     * @param Jobs\JobInterface $job
     * @param Throwable         $e
     *
     * @return void
     */
    protected function failJob(Jobs\JobInterface $job, Throwable $e): void
    {
        $job->fail($e);
    }

    /**
     * Determine if "async" signals are supported.
     *
     * @return bool
     */
    protected function supportsAsyncSignals(): bool
    {
        return extension_loaded('pcntl');
    }

    /**
     * Stop listening and bail out of the script.
     *
     * @param int $status
     *
     * @return int
     */
    public function stop(int $status = 0): int
    {
        fn_set_hook('queue_worker_stopping', $status);

        return $status;
    }

    /**
     * Kill the process.
     *
     * @param int $status
     *
     * @return never
     */
    public function kill(int $status = 0)
    {
        fn_set_hook('queue_worker_stopping', $status);

        if (extension_loaded('posix')) {
            posix_kill(getmypid(), SIGKILL);
        }

        exit($status);
    }

    /**
     * Set the name of the worker.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the queue manager instance.
     *
     * @return Manager
     */
    public function getManager(): Manager
    {
        return $this->manager;
    }

    /**
     * Set the queue manager instance.
     *
     * @param Manager $manager
     *
     * @return void
     */
    public function setManager(Manager $manager)
    {
        $this->manager = $manager;
    }
}
