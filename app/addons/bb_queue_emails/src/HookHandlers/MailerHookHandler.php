<?php

namespace Tygh\Addons\BbQueueEmails\HookHandlers;

use Pimple\Container;
use Tygh\Addons\BbQueueEmails\QueueTransport;
use Tygh\Mailer\IMessageBuilder;
use Tygh\Mailer\ITransport;
use Tygh\Mailer\Mailer;

class MailerHookHandler
{
    /** @var Container  */
    protected Container $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Changes message params before message created
     *
     * @param Mailer          $instance  Mailer instance
     * @param array           $message   Message params
     * @param string          $area      Current working area (A-admin|C-customer)
     * @param string          $lang_code Language code
     * @param ITransport      $transport Instance of transport for send mail
     * @param IMessageBuilder $builder   Message builder instance
     */
    public function beforeCreateMessage(
        Mailer $instance,
        array $message,
        string $area,
        string $lang_code,
        ITransport &$transport,
        IMessageBuilder $builder
    ) {
        if (empty($message['no_queue'])) {
            $transport = $this->container[QueueTransport::class];
        }
    }
}
