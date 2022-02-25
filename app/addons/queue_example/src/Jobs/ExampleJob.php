<?php

namespace Tygh\Addons\QueueExample\Jobs;

use Tygh\Addons\Queue\InteractsWithQueue;
use Tygh\Addons\Queue\Queueable;
use Tygh\Addons\Queue\ShouldQueue;

/**
 * Execute a job.
 */
class ExampleJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    protected string $message;

    public function __construct(
        string $message
    ) {
        $this->message = $message;
    }

    public function handle(): void
    {
        echo $this->message;
    }
}
