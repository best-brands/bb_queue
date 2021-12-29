<?php

use Tygh\Tygh;

if (php_sapi_name() === 'cli') {
    if ($mode === 'launch_worker') {
        /** @var \Tygh\Addons\Queue\Worker $queue_worker */
        $queue_worker = Tygh::$app['addons.queue.worker'];
        $queue_worker->launch();
    } elseif ($mode === 'schedule_cron_jobs') {
        $scheduler = Tygh::$app['addons.queue.scheduler'];
        $scheduler->scheduleCronJobs();
    }

    exit(0);
}

if ($mode === 'manage') {
    Tygh::$app['view']->assign('queue_messages', db_get_array('SELECT * FROM cscart_queue_messages'));
}
