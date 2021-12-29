<?php

namespace Tygh\Addons\Queue;

use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;
use Tygh\Core\HookHandlerProviderInterface;
use Tygh\Registry;

require_once Registry::get('config.dir.addons') . '/queue/func.php';

/**
 * Class Bootstrap
 * @package Tygh\Addons\Queue
 */
class Bootstrap implements BootstrapInterface, HookHandlerProviderInterface
{
    /**
     * @inheritDoc
     */
    public function boot(ApplicationInterface $app)
    {
        $app->register(new ServiceProvider());
    }

    public function getHookHandlerMap(): array
    {
        return [
//            'save_log' => [
//                'addons.queue.hook_handlers.log',
//                'onSaveLog',
//            ],
        ];
    }
}
