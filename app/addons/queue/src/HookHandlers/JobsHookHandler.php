<?php

namespace Tygh\Addons\Queue\HookHandlers;

use Throwable;
use Tygh\Addons\Queue\Failed\FailedJobProviderInterface;
use Tygh\Addons\Queue\Jobs\JobInterface;

class JobsHookHandler
{
    /**
     * The provider to report job exceptions to.
     *
     * @var FailedJobProviderInterface
     */
    protected FailedJobProviderInterface $failed_job_provider;

    /**
     * Create the job hook handler.
     *
     * @param FailedJobProviderInterface $failed_job_provider
     */
    public function __construct(FailedJobProviderInterface $failed_job_provider)
    {
        $this->failed_job_provider = $failed_job_provider;
    }

    /**
     * Handles job exceptions
     *
     * @param string       $connection_name
     * @param JobInterface $job
     * @param Throwable    $e
     *
     * @return void
     */
    public function onJobException(string $connection_name, JobInterface $job, Throwable $e): void
    {
        $this->failed_job_provider->log(
            $connection_name, $job->getQueue(), $job->getRawBody(), $e
        );
    }
}
