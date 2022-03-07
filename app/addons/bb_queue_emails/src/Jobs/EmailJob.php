<?php

namespace Tygh\Addons\BbQueueEmails\Jobs;

use Tygh\Addons\Queue\InteractsWithQueue;
use Tygh\Addons\Queue\Queueable;
use Tygh\Addons\Queue\ShouldQueue;
use Tygh\Exceptions\EmailSyncException;
use Tygh\Mailer\Message;
use Tygh\Mailer\TransportFactory;
use Tygh\Tygh;

class EmailJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue;

    protected Message $message;

    /**
     * Creates an email job with a message as context.
     *
     * @param Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Send the email message.
     *
     * @return void
     * @throws EmailSyncException
     */
    public function handle(): void
    {
        /** @var TransportFactory $transport */
        $transport = Tygh::$app['mailer.transport_factory'];
        $company_id = $this->message->getCompanyId();

        $result = $transport->createTransportByCompanyId($company_id)->sendMessage($this->message);

        if (empty($errors = $result->getErrors())) {
            return;
        }

        $exception_msg = '';

        foreach ($errors as $error) {
            $exception_msg .= sprintf("%s\n", $error);
            $this->writeOutput('warn', $error);
        }

        throw new EmailSyncException($exception_msg);
    }
}
