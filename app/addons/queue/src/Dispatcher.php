<?php

namespace Tygh\Addons\Queue;

use RuntimeException;
use Tygh\Addons\Queue\Adapters\AdapterInterface;
use Tygh\Addons\Queue\Jobs\SyncJob;
use Tygh\Application;
use Tygh\Exceptions\DeveloperException;

class Dispatcher
{
    /**
     * The command to handler mapping for non-self-handling events.
     *
     * @var array
     */
    protected array $handlers = [];

    /**
     * The container instance.
     *
     * @var Application
     */
    protected Application $container;

    /**
     * @var Manager the queue manager instance
     */
    protected ?Manager $manager;

    /**
     * @param Application  $application
     * @param Manager|null $manager
     */
    public function __construct(Application $application, ?Manager $manager = null)
    {
        $this->container = $application;
        $this->manager   = $manager;
    }

    /**
     * Determine if the given command has a handler.
     *
     * @param mixed $command
     *
     * @return bool
     */
    public function hasCommandHandler($command): bool
    {
        return array_key_exists(get_class($command), $this->handlers);
    }

    /**
     * Retrieve the handler for a command.
     *
     * @param mixed $command
     *
     * @return bool|mixed
     */
    public function getCommandHandler($command)
    {
        if ($this->hasCommandHandler($command)) {
            return $this->container[$this->handlers[get_class($command)]];
        }

        return false;
    }

    /**
     * Dispatch a command to its appropriate handler.
     *
     * @param mixed $command
     *
     * @return mixed
     * @throws DeveloperException
     */
    public function dispatch($command)
    {
        return $this->commandShouldBeQueued($command)
            ? $this->dispatchToQueue($command)
            : $this->dispatchNow($command);
    }

    /**
     * Determine if the given command should be queued.
     *
     * @param mixed $command
     *
     * @return bool
     */
    protected function commandShouldBeQueued($command): bool
    {
        return $command instanceof ShouldQueue;
    }

    /**
     * @param                   $command
     * @param                   $handler
     *
     * @return mixed
     * @throws DeveloperException
     */
    public function dispatchNow($command, $handler = null)
    {
        $uses = class_uses_recursive($command);

        if (in_array(InteractsWithQueue::class, $uses) && !$command->job) {
            $command->setJob(new SyncJob($this->container, json_encode([]), 'sync', 'sync'));
        }

        if ($handler || $handler = $this->getCommandHandler($command)) {
            $callback = function ($command) use ($handler) {
                $method = method_exists($handler, 'handle') ? 'handle' : '__invoke';
                return $handler->{$method}($command);
            };
        } else {
            $callback = function ($command) {
                $method = method_exists($command, 'handle') ? 'handle' : '__invoke';
                return call_user_func([$command, $method]);
            };
        }

        return $callback($command);
    }

    /**
     * Dispatch a command to its appropriate handler behind a queue.
     *
     * @param mixed $command
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function dispatchToQueue($command)
    {
        $connection = $command->connection ?? null;

        if (!$this->manager) {
            $this->manager = $this->container->get(Manager::class);
        }

        $queue = $this->manager->connection($connection);

        if (!$queue instanceof AdapterInterface) {
            throw new RuntimeException('Queue resolver did not return a Queue implementation.');
        }

        if (method_exists($command, 'queue')) {
            return $command->queue($queue, $command);
        }

        return $this->pushCommandToQueue($queue, $command);
    }

    /**
     * Push the command onto the given queue instance.
     *
     * @param AdapterInterface $queue
     * @param mixed            $command
     *
     * @return mixed
     */
    protected function pushCommandToQueue(AdapterInterface $queue, $command)
    {
        if (isset($command->queue, $command->delay)) {
            return $queue->laterOn($command->queue, $command->delay, $command);
        }

        if (isset($command->queue)) {
            return $queue->pushOn($command->queue, $command);
        }

        if (isset($command->delay)) {
            return $queue->later($command->delay, $command);
        }

        return $queue->push($command);
    }
}
