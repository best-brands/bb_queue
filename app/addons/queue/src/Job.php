<?php

namespace Tygh\Addons\Queue;

use Tygh\Addons\Queue\Connectors\ConnectorInterface;

/**
 * A base job implementation.
 */
abstract class Job implements JobInterface
{
    /** @var string The queue name is by */
    protected string $name;

    /** @var int The default delay of a message. */
    protected int $delay = 0;

    /** @var int The retry period between each entry */
    protected int $timeout = 0;

    /** @var int The retention period of queue messages */
    protected int $retention = SECONDS_IN_DAY;

    protected ?string $cron_expression = null;

    /** @var ConnectorInterface The database connector */
    protected ConnectorInterface $connector;

    /**
     * Constructor
     *
     * @param ConnectorInterface $connector
     */
    public function __construct(ConnectorInterface $connector)
    {
        $this->connector = $connector;
    }

    /**
     * Execute a job with the passed data.
     *
     * @param array $job_info
     * @param mixed $message
     *
     * @throws JobException
     */
    abstract public function handle(array $job_info, $message): void;

    /**
     * Schedule a job.
     *
     * @param $message
     *
     * @return bool
     */
    public function schedule($message = null): bool
    {
        return $this->connector->send($this->getName(), $message);
    }

    /**
     * Gets the name of the queue.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the delay of the queue.
     *
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }

    /**
     * Get the timeout of the queue.
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Get the retention period of the queue.
     *
     * @return int
     */
    public function getRetentionPeriod(): int
    {
        return $this->retention;
    }

    public function getCronExpression(): string
    {
        if ($this->cron_expression === null) {
            throw new JobException('Job does not have a cron expression');
        }

        return $this->cron_expression;
    }

    /**
     * Check whether the job has a cron expression.
     *
     * @return bool
     */
    public function hasCronExpression(): bool
    {
        return $this->cron_expression !== null;
    }
}
