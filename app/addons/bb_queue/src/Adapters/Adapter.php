<?php

namespace Tygh\Addons\Queue\Adapters;

use DateTimeInterface;
use Tygh\Addons\Queue\InvalidPayloadException;
use Tygh\Addons\Queue\InteractsWithTime;
use Tygh\Application;

abstract class Adapter implements AdapterInterface
{
    use InteractsWithTime;

    /**
     * The IoC container instance.
     *
     * @var Application
     */
    protected Application $container;

    /**
     * The connection name for the queue.
     *
     * @var string
     */
    protected string $connection_name;

    /**
     * @inheritdoc
     */
    public function pushOn(string $queue, string $job, $data = '')
    {
        return $this->push($job, $data, $queue);
    }

    /**
     * @inheritdoc
     */
    public function laterOn(string $queue, $delay, string $job, $data = '')
    {
        return $this->later($delay, $job, $data, $queue);
    }

    /**
     * Create a payload string from the given job and data.
     *
     * @param \Closure|string|object $job
     * @param mixed                  $data
     *
     * @return string
     *
     * @throws InvalidPayloadException
     */
    protected function createPayload($job, $data = ''): string
    {
        $payload = json_encode($this->createPayloadArray($job, $data));

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidPayloadException(
                'Unable to JSON encode payload. Error code: ' . json_last_error()
            );
        }

        return $payload;
    }

    /**
     * Create a payload array from the given job and data.
     *
     * @param string|object $job
     * @param mixed         $data
     *
     * @return array
     */
    protected function createPayloadArray($job, $data = ''): array
    {
        return is_object($job)
            ? $this->createObjectPayload($job)
            : $this->createStringPayload($job, $data);
    }

    /**
     * Create a payload for an object-based queue handler.
     *
     * @param object $job
     *
     * @return array
     */
    protected function createObjectPayload(object $job): array
    {
        $payload = [
            'displayName'   => $this->getDisplayName($job),
            'job'           => 'Tygh\Addons\Queue\CallQueuedHandler@call',
            'maxTries'      => $job->tries ?? null,
            'maxExceptions' => $job->maxExceptions ?? null,
            'failOnTimeout' => $job->failOnTimeout ?? false,
            'backoff'       => $this->getJobBackoff($job),
            'timeout'       => $job->timeout ?? null,
            'retryUntil'    => $this->getJobExpiration($job),
            'data'          => [
                'commandName' => $job,
                'command'     => $job,
            ],
        ];

        $command = serialize(clone $job);

        return array_merge($payload, [
            'data' => array_merge($payload['data'], [
                'commandName' => get_class($job),
                'command'     => $command,
            ]),
        ]);
    }

    /**
     * Get the display name for the given job.
     *
     * @param object $job
     *
     * @return string
     */
    protected function getDisplayName(object $job): string
    {
        return method_exists($job, 'displayName')
            ? $job->displayName() : get_class($job);
    }

    /**
     * Get the backoff for an object-based queue handler.
     *
     * @param mixed $job
     *
     * @return string|null
     */
    public function getJobBackoff($job): ?string
    {
        if (!method_exists($job, 'backoff') && !isset($job->backoff)) {
            return null;
        }

        if (is_null($backoff = $job->backoff ?? $job->backoff())) {
            return null;
        }

        return join(',', array_map(static function ($backoff) {
            return $backoff instanceof DateTimeInterface
                ? $this->secondsUntil($backoff) : $backoff;
        }, $backoff));
    }

    /**
     * Get the expiration timestamp for an object-based queue handler.
     *
     * @param mixed $job
     *
     * @return mixed
     */
    public function getJobExpiration($job)
    {
        if (!method_exists($job, 'retryUntil') && !isset($job->retryUntil)) {
            return;
        }

        $expiration = $job->retryUntil ?? $job->retryUntil();

        return $expiration instanceof DateTimeInterface
            ? $expiration->getTimestamp()
            : $expiration;
    }

    /**
     * Create a typical, string based queue payload array.
     *
     * @param string $job
     * @param mixed  $data
     *
     * @return array
     */
    protected function createStringPayload(string $job, $data): array
    {
        return [
            'displayName'   => explode('@', $job)[0],
            'job'           => $job,
            'maxTries'      => null,
            'maxExceptions' => null,
            'failOnTimeout' => false,
            'backoff'       => null,
            'timeout'       => null,
            'data'          => $data,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getConnectionName(): string
    {
        return $this->connection_name;
    }

    /**
     * @inheritDoc
     */
    public function setConnectionName(string $name): self
    {
        $this->connection_name = $name;

        return $this;
    }

    /**
     * Get the application container instance
     *
     * @return Application
     */
    public function getContainer(): Application
    {
        return $this->container;
    }

    /**
     * Set the IoC container instance.
     *
     * @param Application $container
     *
     * @return void
     */
    public function setContainer(Application $container): void
    {
        $this->container = $container;
    }
}
