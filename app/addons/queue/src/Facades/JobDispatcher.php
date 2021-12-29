<?php

namespace Tygh\Addons\Queue\Facades;

use Tygh\Tygh;

/**
 * Class JobDispatcher
 * @package Tygh\Addons\Queue\Facades
 */
class JobDispatcher
{
    /**
     * Facade job dispatcher.
     *
     * @param string $fqcn
     * @param $message
     */
    public static function dispatch(string $fqcn, $message)
    {
        Tygh::$app['addons.queue.scheduler']->schedule($fqcn, $message);
    }
}