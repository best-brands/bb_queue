<?php

namespace Tygh\Addons\Queue;

use DateTime;
use Tygh\Addons\Queue\Connectors\ConnectorInterface;
use Tygh\Addons\Queue\Exceptions\JobException;

/**
 *
 */
abstract class Job implements JobInterface
{
    /** @var int The default delay of a message. */
    protected int $delay = 0;

    /** @var int The retry period between each entry */
    protected int $timeout = 0;

    /** @var int The retention period of queue messages */
    protected int $retention = SECONDS_IN_DAY;

    protected ?string $cron_expression = null;

    /** @var bool Determine whether a job should be unique */
    protected bool $is_unique = false;

    /** @var mixed */
    protected $id;

    /** @var mixed */
    protected $queue_id;

    /** @var mixed */
    protected $consumer;

    /** @var mixed */
    protected $inserted_on;

    /** @var mixed */
    protected $read_on;

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
        $result = false;

        if (
            $this->is_unique && $this->connector->countInQueue(get_class($this)) < 1
            || !$this->is_unique
        ) {
            $result = $this->connector->send(get_class($this), $message);
        }

        return $result;
    }

    /**
     * @param $data
     */
    public function setContext(array $data): void
    {
        $keys = [
            'id' => 'id',
            'queue_id' => 'queue_id',
            'consumer' => 'consumer',
            'inserted_on' => 'inserted_on',
            'read_on' => 'read_on',
        ];

        foreach ($data as $k => $v) {
            if (!array_key_exists($k, $keys)) {
                continue;
            }

            $this->{$keys[$k]} = $v;
        }
    }

    /**
     * @param string $message
     */
    public function write(string $message): void
    {
        echo sprintf(
            "[%s][%s] %s %s\n",
            (new DateTime())->format('Y-m-d H:i:s'),
            $this->id,
            $this->queue_id,
            $message
        );
    }

    /**
     * Reschedule job.
     *
     * @param array $job_data
     * @param int   $amount
     *
     * @return mixed
     */
    public function reschedule(array $job_data, int $amount)
    {
        return $this->connector->reschedule($job_data['id'], $amount);
    }

    /**
     * Gets the name of the queue.
     *
     * @return string
     */
    public function getName(): string
    {
        return get_class($this);
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

    /**
     * @inheritDoc
     */
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
