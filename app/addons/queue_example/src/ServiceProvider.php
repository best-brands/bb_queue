<?php

namespace Tygh\Addons\QueueExample;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\Queue\Schedule;

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
        $pimple[Jobs\ExampleJob::class] = $pimple->factory(fn() => new Jobs\ExampleJob("default"));

        // We add the job to the job_pool dependency, with a safeguard so we don't brick the server if the
        // addon is not present.
        if ($pimple->offsetExists('Tygh\Addons\Queue\Schedule')) {
            $pimple->extend(Schedule::class, function (Schedule $schedule) {
                // By passing a FQCN we retrieve it via our DI.
                $schedule->job(Jobs\ExampleJob::class)->dailyAt('9:30');

                // We can also do direct invocations through instantiating the object.
                $schedule->job(new Jobs\ExampleJob("Direct invocation!"));
            });
        }
    }
}
