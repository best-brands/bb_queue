<?php

namespace Tygh\Addons\Queue;

use Carbon\Carbon;
use DateInterval;
use DateTimeInterface;
use Tygh\Addons\Queue\Jobs\JobInterface;

trait Queueable
{
    /**
     * The name of the connection the job should be sent to.
     *
     * @var string|null
     */
    public ?string $connection = null;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string|null
     */
    public ?string $queue = null;

    /**
     * The number of seconds before the job should be made available.
     *
     * @var DateTimeInterface|DateInterval|int|null
     */
    public $delay = 0;

    /**
     * Set the desired connection for the job.
     *
     * @param string|null $connection
     *
     * @return $this
     */
    public function onConnection(?string $connection): self
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * Set the desired queue for the job.
     *
     * @param string|null $queue
     *
     * @return $this
     */
    public function onQueue(?string $queue): self
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * Set the desired delay for the job.
     *
     * @param DateTimeInterface|DateInterval|int|null $delay
     *
     * @return $this
     */
    public function delay($delay): self
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * Write the status output for the queue worker.
     *
     * @param string $status
     * @param string $extra
     *
     * @return void
     */
    protected function writeOutput(string $status, string $extra = ''): void
    {
        switch ($status) {
        case 'info':
            $this->writeStatus('Info', $extra);
            break;
        case 'debug':
            if (defined('DEVELOPMENT')) {
                $this->writeStatus('Debug', $extra);
            }
            break;
        case 'warn':
            $this->writeStatus('Warning', $extra);
            break;
        }
    }

    /**
     * Format the status output for the queue worker.
     *
     * @param string $status
     * @param string $extra
     *
     * @return void
     */
    protected function writeStatus(string $status, string $extra = '')
    {
        echo sprintf(
            "[%s][%s] %s %s %s" . PHP_EOL,
            Carbon::now()->format('Y-m-d H:i:s'),
            $this->job->getJobId(),
            str_pad("$status:", 11),
            $this->job->resolveName(),
            $extra,
        );
    }
}
