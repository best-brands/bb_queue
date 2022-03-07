<?php

namespace Tygh\Addons\QueueExample;

use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;

/**
 * Class Bootstrap
 * @package Tygh\Addons\QueueExample
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @inheritDoc
     */
    public function boot(ApplicationInterface $app)
    {
        // We register the service provider to the DI.
        $app->register(new ServiceProvider());
    }
}