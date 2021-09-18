<?php

namespace Tygh\Addons\Queue;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\Queue\Connectors\DatabaseConnector;
use Tygh\Addons\Queue\Jobs\Test;
use Tygh\Addons\Queue\Serializer\JsonSerializer;
use Tygh\Addons\Queue\Serializer\SerializerInterface;
use Tygh\Registry;

/**
 * Class QueueProvider
 * @package Tygh\Providers
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * Provide queues
     * @param Container $pimple
     */
    public function register(Container $pimple)
    {
        $pimple['addons.queue.connector'] = static function ($pimple) {
            return new DatabaseConnector(
                $pimple['addons.queue.serializer']
            );
        };

        $pimple['addons.queue.cron_expression_parser'] = static function () {
            return new CronExpressionParser\Expression();
        };

        $pimple['addons.queue.jobs.test'] = static function ($pimple) {
            return new Test(
                $pimple['addons.queue.connector']
            );
        };

        $pimple['addons.queue.job_pool'] = static function ($pimple) {
            return new JobPool([
                $pimple['addons.queue.jobs.test'],
            ]);
        };

        $pimple['addons.queue.serializer'] = static function (): SerializerInterface {
            return new JsonSerializer();
        };

        $pimple['addons.queue.worker'] = static function ($pimple) {
            return new Worker(
                $pimple['addons.queue.connector'],
                $pimple['addons.queue.job_pool']
            );
        };

        $pimple['addons.queue.scheduler'] = static function ($pimple) {
            return new Scheduler(
                $pimple['addons.queue.job_pool'],
                $pimple['addons.queue.cron_expression_parser'],
            );
        };
    }
}
