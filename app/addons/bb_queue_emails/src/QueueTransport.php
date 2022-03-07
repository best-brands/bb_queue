<?php

namespace Tygh\Addons\BbQueueEmails;

use Tygh\Mailer\ITransport;
use Tygh\Mailer\Message;
use Tygh\Mailer\SendResult;

class QueueTransport implements ITransport
{
    public function sendMessage(Message $message): SendResult
    {
        fn_queue_dispatch(new Jobs\EmailJob($message));
        ($result = new SendResult())->setIsSuccess(true);
        return $result;
    }
}
