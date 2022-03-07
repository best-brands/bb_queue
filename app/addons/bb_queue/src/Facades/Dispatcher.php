<?php

namespace Tygh\Addons\Queue\Facades;

use Tygh\Tygh;

/**
 * Class JobDispatcher
 * @package Tygh\Addons\Queue\Facades
 */
class Dispatcher
{
    /**
     * Facade job dispatcher.
     *
     * @param $command
     *
     * @throws \Tygh\Exceptions\DeveloperException
     */
    public static function dispatch($command): void
    {
        Tygh::$app[\Tygh\Addons\Queue\Dispatcher::class]->dispatch($command);
    }

    /**
     * Facade the immediate job dispatcher.
     *
     * @param $command
     *
     * @throws \Tygh\Exceptions\DeveloperException
     */
    public static function dispatchSync($command): void
    {
        Tygh::$app[\Tygh\Addons\Queue\Dispatcher::class]->dispatchNow($command);
    }
}
