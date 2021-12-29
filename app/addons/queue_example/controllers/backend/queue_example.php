<?php

use Tygh\Addons\QueueExample\Jobs\ExampleJob;

if ($mode === 'test') {
    fn_queue_dispatch(ExampleJob::class, null);
}
