<?php

namespace Tygh\Addons\BbQueueEmails;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $pimple): void
    {
        $pimple[HookHandlers\MailerHookHandler::class] = fn($pimple) => new HookHandlers\MailerHookHandler($pimple['app']);

        $pimple[QueueTransport::class] = fn() => new QueueTransport();

        /* Create a new queue mailer transport */
        $pimple['mailer.transport.queue'] = static function ($pimple) {
            return fn(array $settings) => $pimple[QueueTransport::class];
        };
    }
}
