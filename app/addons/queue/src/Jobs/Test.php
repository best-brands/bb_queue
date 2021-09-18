<?php

namespace Tygh\Addons\Queue\Jobs;

use Tygh\Addons\Queue\Job;

/**
 * Execute a job.
 */
class Test extends Job
{
    protected string $name = 'test';

    /**
     * @param array  $job_info
     * @param string $message
     */
    public function handle(array $job_info, $message): void
    {
        echo $message;
    }
}
