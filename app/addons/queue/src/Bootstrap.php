<?php

namespace Tygh\Addons\Queue;

use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;

/**
 * Class Bootstrap
 * @package Tygh\Addons\Queue
 */
class Bootstrap implements BootstrapInterface
{
    /**
     * @inheritDoc
     */
    public function boot(ApplicationInterface $app)
    {
        $app->register(new ServiceProvider());
    }
}