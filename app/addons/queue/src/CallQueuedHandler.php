<?php

namespace Tygh\Addons\Queue;

use RuntimeException;
use Tygh;
use Tygh\Addons\Queue\Jobs\JobInterface;
use Tygh\Application;

class CallQueuedHandler
{
    /**
     * The bus dispatcher implementation.
     *
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * The container instance.
     *
     * @var Application
     */
    protected Application $container;

    /**
     * Create a new handler instance.
     *
     * @param Dispatcher  $dispatcher
     * @param Application $container
     */
    public function __construct(Dispatcher $dispatcher, Application $container)
    {
        $this->dispatcher = $dispatcher;
        $this->container  = $container;
    }

    /**
     * Handle the queued job.
     *
     * @param Jobs\JobInterface $job
     * @param array             $data
     *
     * @return void
     * @throws Tygh\Exceptions\DeveloperException
     */
    public function call(Jobs\JobInterface $job, array $data)
    {
        $command = $this->setJobInstanceIfNecessary(
            $job, $this->getCommand($data)
        );

        if ($command instanceof ShouldBeUniqueUntilProcessing) {
            $this->ensureUniqueJobLockIsReleased($command);
        }

        $this->dispatcher->dispatchNow(
            $command, $this->resolveHandler($job, $command)
        );

        if (!$job->isReleased() && !$command instanceof ShouldBeUniqueUntilProcessing) {
            $this->ensureUniqueJobLockIsReleased($command);
        }

        if (!$job->isDeletedOrReleased()) {
            $job->delete();
        }
    }

    /**
     * Ensure the lock for a unique job is released.
     *
     * @param mixed $command
     *
     * @return void
     */
    protected function ensureUniqueJobLockIsReleased($command)
    {
        if (!$command instanceof ShouldBeUnique) {
            return;
        }

        $uniqueId = method_exists($command, 'uniqueId')
            ? $command->uniqueId()
            : ($command->uniqueId ?? '');

        $lock = Tygh::$app['lock.factory']->create('cscart_unique_job:' . get_class($command) . $uniqueId);
        $lock->release();
    }

    /**
     * Get the command from the given payload.
     *
     * @param array $data
     *
     * @return mixed
     */
    protected function getCommand(array $data)
    {
        if (strpos($data['command'], 'O:') === 0) {
            return unserialize($data['command']);
        }

        throw new RuntimeException('Unable to extract job payload.');
    }

    /**
     * Resolve the handler for the given command.
     *
     * @param Jobs\JobInterface $job
     * @param mixed             $command
     *
     * @return mixed
     */
    public function resolveHandler(Jobs\JobInterface $job, $command)
    {
        $handler = $this->dispatcher->getCommandHandler($command) ?: null;

        if ($handler) {
            $this->setJobInstanceIfNecessary($job, $handler);
        }

        return $handler;
    }


    /**
     * Set the job instance of the given class if necessary.
     *
     * @param JobInterface $job
     * @param mixed        $instance
     *
     * @return mixed
     */
    protected function setJobInstanceIfNecessary(Jobs\JobInterface $job, $instance)
    {
        if (in_array(InteractsWithQueue::class, class_uses_recursive($instance))) {
            $instance->setJob($job);
        }

        return $instance;
    }

    /**
     * Call the failed method on the job instance.
     *
     * @param array $data
     * @param       $e
     *
     * @return void
     */
    public function failed(array $data, $e)
    {
        $command = $this->getCommand($data);

        if (!$command instanceof ShouldBeUniqueUntilProcessing) {
            $this->ensureUniqueJobLockIsReleased($command);
        }

        if (method_exists($command, 'failed')) {
            $command->failed($e);
        }
    }
}
