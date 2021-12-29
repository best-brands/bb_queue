<?php

namespace Tygh\Addons\QueueExample\Jobs;

use Tygh\Addons\Queue\Job;

/**
 * Execute a job.
 */
class ExampleJob extends Job
{
    /** @var string|null By setting the cron expression, it will get scheduled correctly */
    protected ?string $cron_expression = '* * * * *';

    /**
     * @param array  $job_info
     * @param string $message
     */
    public function handle(array $job_info, $message): void
    {
        echo $message;
    }
}
