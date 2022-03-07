<?php

namespace Tygh\Addons\BbQueueEmails;

use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;
use Tygh\Core\HookHandlerProviderInterface;

class Bootstrap implements BootstrapInterface, HookHandlerProviderInterface
{
    /**
     * @inheritDoc
     */
    public function boot(ApplicationInterface $app)
    {
        $app->register(new ServiceProvider());
    }

    /**
     * @return array
     */
    public function getHookHandlerMap(): array
    {
        return [
            'mailer_create_message_before' => [
                HookHandlers\MailerHookHandler::class,
                'beforeCreateMessage',
            ],
        ];
    }
}
