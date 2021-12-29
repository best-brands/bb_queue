<?php

namespace Tygh\Addons\QueueExample;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\Queue\JobPool;

/**
 * Class ServiceProvider
 * @package Tygh\Addons\QueueExample
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $pimple)
    {
        // We define the job dependency
        $pimple[Jobs\ExampleJob::class] = static function ($pimple) {
            return new Jobs\ExampleJob(
                $pimple['addons.queue.connector']
            );
        };

        // We add the job the to the job_pool dependency
        $pimple->extend('addons.queue.job_pool', static function (JobPool $pool) use ($pimple) {
            $pool->addJobs([
                $pimple[Jobs\ExampleJob::class],
            ]);

            return $pool;
        });
    }
}