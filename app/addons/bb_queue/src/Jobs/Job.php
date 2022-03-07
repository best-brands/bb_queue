<?php

namespace Tygh\Addons\Queue\Jobs;

use Throwable;
use Tygh\Addons\Queue\InteractsWithTime;
use Tygh\Application;

abstract class Job implements JobInterface
{
    use InteractsWithTime;

    /**
     * The job handler instance.
     *
     * @var mixed
     */
    protected $instance;

    /**
     * The IoC container instance.
     *
     * @var Application
     */
    protected Application $container;

    /**
     * Indicates if the job has been deleted.
     *
     * @var bool
     */
    protected bool $deleted = false;

    /**
     * Indicates if the job has been released.
     *
     * @var bool
     */
    protected bool $released = false;

    /**
     * Indicates if the job has failed.
     *
     * @var bool
     */
    protected bool $failed = false;

    /**
     * The name of the connection the job belongs to.
     *
     * @var string
     */
    protected string $connection_name;

    /**
     * The name of the queue the job belongs to.
     *
     * @var string
     */
    protected string $queue;

    /**
     * @inheritDoc
     */
    abstract public function getJobId(): string;

    /**
     * @inheritDoc
     */
    abstract public function getRawBody(): string;

    /**
     * @inheritDoc
     */
    public function fire(): void
    {
        $payload = $this->payload();

        [$class, $method] = JobName::parse($payload['job']);

        ($this->instance = $this->resolve($class))->{$method}($this, $payload['data']);
    }

    /**
     * @inheritDoc
     */
    public function delete(): void
    {
        $this->deleted = true;
    }

    /**
     * @inheritDoc
     */
    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    /**
     * @inheritDoc
     */
    public function release(int $delay = 0): void
    {
        $this->released = true;
    }

    /**
     * @inheritDoc
     */
    public function isReleased(): bool
    {
        return $this->released;
    }

    /**
     * @inheritDoc
     */
    public function isDeletedOrReleased(): bool
    {
        return $this->isDeleted() || $this->isReleased();
    }

    /**
     * @inheritDoc
     */
    public function hasFailed(): bool
    {
        return $this->failed;
    }

    /**
     * @inheritDoc
     */
    public function markAsFailed(): void
    {
        $this->failed = true;
    }

    /**
     * @inheritDoc
     */
    public function fail(?Throwable $e = null): void
    {
        $this->markAsFailed();

        if ($this->isDeleted()) {
            return;
        }

        try {
            // If the job has failed, we will delete it, call the "failed" method and then call
            // an event indicating the job has failed so it can be logged if needed. This is
            // to allow every developer to better keep monitor of their failed queue jobs.
            $this->delete();
            $this->failed($e);
        } finally {
            fn_set_hook('queue_job_failed', $this->connection_name, $this, $e);
        }
    }

    /**
     * Process an exception that caused the job to fail.
     *
     * @param Throwable|null $e
     *
     * @return void
     */
    protected function failed(?Throwable $e)
    {
        $payload = $this->payload();

        [$class, $method] = JobName::parse($payload['job']);

        if (method_exists($this->instance = $this->resolve($class), 'failed')) {
            $this->instance->failed($payload['data'], $e, $payload['uuid'] ?? '');
        }
    }

    /**
     * Resolve the given class.
     *
     * @param  string  $class
     * @return mixed
     */
    protected function resolve(string $class)
    {
        return $this->container->get($class);
    }

    /**
     * Get the resolved job handler instance.
     *
     * @return mixed
     */
    public function getResolvedJob()
    {
        return $this->instance;
    }

    /**
     * @inheritDoc
     */
    public function payload(): array
    {
        return json_decode($this->getRawBody(), true);
    }

    /**
     * @inheritDoc
     */
    public function maxTries(): ?int
    {
        return $this->payload()['maxTries'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function maxExceptions(): ?int
    {
        return $this->payload()['maxExceptions'] ?? null;
    }

    /**
     * Determine if the job should fail when it timeouts.
     *
     * @return bool
     */
    public function shouldFailOnTimeout(): bool
    {
        return $this->payload()['failOnTimeout'] ?? false;
    }

    /**
     * The number of seconds to wait before retrying a job that encountered an uncaught exception.
     *
     * @return int|null
     */
    public function backoff(): ?int
    {
        return $this->payload()['backoff'] ?? $this->payload()['delay'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function timeout(): ?int
    {
        return $this->payload()['timeout'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function retryUntil(): ?int
    {
        return $this->payload()['retryUntil'] ?? $this->payload()['timeoutAt'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return $this->payload()['job'];
    }

    /**
     * @inheritDoc
     */
    public function resolveName(): string
    {
        return JobName::resolve($this->getName(), $this->payload());
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
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * Get the service container instance.
     *
     * @return Application
     */
    public function getContainer(): Application
    {
        return $this->container;
    }
}
