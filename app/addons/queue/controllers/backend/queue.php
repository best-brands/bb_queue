<?php

use Tygh\Tygh;

if (php_sapi_name() === 'cli') {
    if ($mode === 'launch_worker') {
        /** @var \Tygh\Addons\Queue\Worker $queue_worker */
        $queue_worker = Tygh::$app['addons.queue.worker'];
        $queue_worker->launch();
    } elseif ($mode === 'schedule_cron_jobs') {
        $scheduler = Tygh::$app['addons.queue.scheduler'];
        $scheduler->schedule();
    }

    exit(0);

} elseif ($mode === 'insert_test') {
    Tygh::$app['addons.queue.jobs.test']->schedule("Hello again!\n");

    return [CONTROLLER_STATUS_DENIED];
}
