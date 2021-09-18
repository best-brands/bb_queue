<?php

namespace PHPSTORM_META {
    override(
        new \Tygh\Application,
        map([
            'addons.queue.connector' => \Tygh\Addons\Queue\Connectors\ConnectorInterface::class,
            'addons.queue.cron_expression_parser' => \Tygh\Addons\Queue\CronExpressionParser\Expression::class,
            'addons.queue.jobs.test' => \Tygh\Addons\Queue\Jobs\Test::class,
            'addons.queue.job_pool' => \Tygh\Addons\Queue\JobPool::class,
            'addons.queue.scheduler' => \Tygh\Addons\Queue\Scheduler::class,
            'addons.queue.serializer' => \Tygh\Addons\Queue\Serializer\SerializerInterface::class,
            'addons.queue.worker' => \Tygh\Addons\Queue\Worker::class,
        ])
    );
}
