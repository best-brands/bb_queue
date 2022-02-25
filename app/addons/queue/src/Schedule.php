<?php

namespace Tygh\Addons\Queue;

use Closure;
use DateTimeZone;
use RuntimeException;
use Tygh\Application;
use Tygh\Exceptions\DeveloperException;

/**
 * Schedule jobs on the queue.
 */
class Schedule
{
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;

    /**
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * @var Application
     */
    protected Application $application;

    /**
     * The timezone the date should be evaluated on.
     *
     * @var DateTimeZone|string
     */
    protected $timezone = null;

    /**
     * All of th events on the schedule.
     *
     * @var Event\Event[]
     */
    protected array $events = [];

    /**
     * @param Application $application
     * @param Dispatcher  $dispatcher
     */
    public function __construct(Application $application, Dispatcher $dispatcher)
    {
        $this->application = $application;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Add a new callback event to the schedule.
     *
     * @param string|callable $callback
     * @param array           $parameters
     *
     * @return Event\Event
     */
    public function call($callback, array $parameters = []): Event\Event
    {
        $this->events[] = $event = new Event\Event(
            $callback, $parameters, $this->timezone
        );

        return $event;
    }

    /**
     * Add a new job callback event to the schedule.
     *
     * @param object|string $job
     * @param string|null   $queue
     * @param string|null   $connection
     *
     * @return void
     */
    public function job($job, ?string $queue = null, ?string $connection = null): Event\Event
    {
        return $this->call(function () use ($job, $queue, $connection) {
            $job = is_string($job) ? $this->application->get($job) : $job;

            if ($job instanceof ShouldQueue) {
                $this->dispatchToQueue($job, $queue ?? $job->queue, $connection ?? $job->connection);
            } else {
                $this->dispatchNow($job);
            }
        })->name(is_string($job) ? $job : get_class($job));
    }

    /**
     * Dispatch the given job right now.
     *
     * @param object $job
     *
     * @return void
     * @throws DeveloperException
     */
    protected function dispatchNow(object $job): void
    {
        $this->dispatcher->dispatchNow($job);
    }

    /**
     * Dispatch the given job to the queue.
     *
     * @param object      $job
     * @param string|null $queue
     * @param string|null $connection
     *
     * @return void
     *
     * @throws RuntimeException
     * @throws DeveloperException
     */
    protected function dispatchToQueue(object $job, ?string $queue, ?string $connection)
    {
        if ($job instanceof Closure) {
            throw new RuntimeException(
                'Closure jobs are not supported due to lack of serialization support'
            );
        }

        if ($job instanceof ShouldBeUnique) {
            throw new DeveloperException("Unique jobs are not supported");
        }

        $this->dispatcher->dispatch(
            $job->onConnection($connection)->onQueue($queue)
        );
    }

    /**
     * Get all the events on the schedule that are due.
     *
     * @return Event\Event[]
     */
    public function dueEvents(): array
    {
        return array_filter($this->events, fn($event) => $event->isDue());
    }

    /**
     * Get all the events on the schedule.
     *
     * @return Event\Event[]
     */
    public function events(): array
    {
        return $this->events;
    }
}

