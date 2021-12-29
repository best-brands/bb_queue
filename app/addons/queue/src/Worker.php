<?php

namespace Tygh\Addons\Queue;

use DateTime;
use InvalidArgumentException;
use Throwable;
use Tygh\Addons\Queue\Connectors\ConnectorInterface;
use Tygh\Addons\Queue\Exceptions\RescheduleException;

/**
 * A worker that will poll for 1 minute for jobs.
 */
class Worker
{
    protected int $ttl = 60;

    protected ConnectorInterface $connector;

    protected JobPool $job_pool;

    public function __construct(
        ConnectorInterface $connector,
        JobPool $job_pool
    ) {
        $this->connector = $connector;
        $this->job_pool  = $job_pool;
    }

    public function write(array $job_data, string $message)
    {
        echo sprintf(
            "[%s][%s] %s %s\n",
            (new DateTime())->format('Y-m-d H:i:s'),
            $job_data['id'] ?? 'worker',
            $job_data['queue_id'],
            $message
        );
    }

    /**
     * Find and execute jobs.
     */
    public function launch(): void
    {
        ini_set('memory_limit', '2048M');

        do {
            [$job_data, $message] = $this->connector->receive();

            if (!$job_data) {
                if (time() > ($this->ttl + TIME)) {
                    exit();
                }

                sleep(1);
                continue;
            }

            $this->write($job_data, 'start');

            try {
                $job = $this->job_pool->getJob($job_data['queue_id']);
            } catch (InvalidArgumentException $e) {
                $this->write($job_data, 'undefined job handler');
                goto job_finish;
            }

            $job->setContext($job_data);

            try {
                $job->handle($job_data, $message);
            } catch (RescheduleException $e) {
                $this->connector->reschedule($job_data['id'], $e->getDelay());
                $job->write(sprintf('reschedule by "%d"', $e->getDelay()));
                continue;
            } catch (Throwable $e) {
                $this->connector->reschedule($job_data['id'], $job->getTimeout());
                $job->write(sprintf('reschedule by "%d"', $job->getTimeout()));
                continue;
            }

            job_finish:
            $this->write($job_data, 'finished');
            $this->connector->delete($job_data['id']);
        } while (1);
    }
}
