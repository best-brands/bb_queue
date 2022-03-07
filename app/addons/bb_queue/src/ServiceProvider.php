<?php

namespace Tygh\Addons\Queue;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class QueueProvider
 * @package Tygh\Providers
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * Provide queues
     *
     * @param Container $pimple
     */
    public function register(Container $pimple)
    {
        $pimple['addons.queue.config'] = static function () {
            return fn_get_schema('queue', 'config');
        };

        $pimple[Connectors\DatabaseConnector::class] = fn($pimple) => new Connectors\DatabaseConnector($pimple['db']);
        $pimple[Connectors\NullConnector::class]     = fn() => new Connectors\NullConnector();
        $pimple[Connectors\SyncConnector::class]     = fn() => new Connectors\SyncConnector();

        $pimple[CronExpressionParser\Expression::class] = $pimple->factory(fn() => new CronExpressionParser\Expression());

        $pimple[Failed\DatabaseFailedJobProvider::class]  = fn($pimple) => new Failed\DatabaseFailedJobProvider($pimple['db']);
        $pimple[Failed\FailedJobProviderInterface::class] = $pimple[Failed\DatabaseFailedJobProvider::class];

        $pimple[HookHandlers\JobsHookHandler::class]    = fn($pimple) => new HookHandlers\JobsHookHandler($pimple[Failed\FailedJobProviderInterface::class]);
        $pimple[HookHandlers\LoggingHookHandler::class] = fn() => new HookHandlers\LoggingHookHandler();

        $pimple[CallQueuedHandler::class] = $pimple->factory(fn($pimple) => new CallQueuedHandler(
            $pimple[Dispatcher::class],
            $pimple['app'],
        ));

        $pimple[Dispatcher::class] = fn($pimple) => new Dispatcher(
            $pimple['app'],
            $pimple[Manager::class]
        );

        $pimple[Manager::class] = static function ($pimple) {
            $manager = new Manager($pimple['app']);

            $manager->addConnector('database', fn() => $pimple[Connectors\DatabaseConnector::class]);
            $manager->addConnector('null', fn() => $pimple[Connectors\NullConnector::class]);
            $manager->addConnector('sync', fn() => $pimple[Connectors\SyncConnector::class]);

            return $manager;
        };

        $pimple[Schedule::class] = fn($pimple) => new Schedule(
            $pimple['app'],
            $pimple[Dispatcher::class],
        );

        $pimple[Worker::class] = fn($pimple) => new Worker(
            $pimple[Manager::class],
            $pimple[Failed\FailedJobProviderInterface::class],
            null
        );

        $pimple[WorkerOptions::class] = static function ($pimple) {
            $config = $pimple['addons.queue.config']['worker']['options'] ?? [];

            return new WorkerOptions(
                $config['name'] ?? 'default',
                $config['backoff'] ?? 0,
                $config['memory'] ?? 1024,
                $config['timeout'] ?? 60,
                $config['sleep'] ?? 3,
                $config['max_tries'] ?? 3,
                $config['force'] ?? false,
                $config['stop_when_empty'] ?? false,
                $config['max_jobs'] ?? 0,
                $config['max_time'] ?? 60,
                $config['rest'] ?? 0,
            );
        };
    }
}
