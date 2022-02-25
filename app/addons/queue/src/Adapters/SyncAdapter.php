<?php

namespace Tygh\Addons\Queue\Adapters;

use Throwable;
use Tygh\Addons\Queue\Jobs\JobInterface;
use Tygh\Addons\Queue\Jobs\SyncJob;

class SyncAdapter extends Adapter implements AdapterInterface
{
    /**
     * @inheritDoc
     * @return int
     */
    public function size(?string $queue = null): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function push($job, $data = '', ?string $queue = null): int
    {
        $queue_job = $this->resolveJob($this->createPayload($job, $queue, $data), $queue);

        try {
            $this->raiseBeforeJobEvent($this->connection_name, $job);

            $queue_job->fire();

            $this->raiseAfterJobEvent($this->connection_name, $job);
        } catch (Throwable $e) {
            $this->handleException($queue_job, $e);
        }

        return 0;
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
    protected function raiseExceptionOccurredJobEvent(JobInterface $job, Throwable $e)
    {
        /**
         * Gets triggered once a job has been processed.
         *
         * @param string       $connection_name
         * @param JobInterface $job
         * @param Throwable    $e
         */
        fn_set_hook('queue_job_exception_occurred', $this->connection_name, $job, $e);
    }

    /**
     * Resolve a Sync job instance.
     *
     * @param string $payload
     * @param string $queue
     *
     * @return SyncJob
     */
    protected function resolveJob(string $payload, string $queue): SyncJob
    {
        return new SyncJob($this->container, $payload, $this->connection_name, $queue);
    }

    /**
     * Handle an exception that occurred while processing a job.
     *
     * @param JobInterface $job
     * @param Throwable    $e
     *
     * @return void
     *
     * @throws Throwable
     */
    protected function handleException(JobInterface $job, Throwable $e)
    {
        $this->raiseExceptionOccurredJobEvent($job, $e);

        $job->fail($e);

        throw $e;
    }

    /**
     * @inheritDoc
     * @return mixed
     */
    public function pushRaw(string $payload, ?string $queue = null, array $options = [])
    {
        return null;
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function later($delay, $job, $data = '', string $queue = null)
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * @inheritDoc
     * @return JobInterface|null
     */
    public function pop(?string $queue = null): ?JobInterface
    {
        return null;
    }

    /**
     * @inheritDocs
     */
    public function clear(string $queue): int
    {
        return 0;
    }
}
