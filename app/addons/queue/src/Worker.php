<?php

namespace Tygh\Addons\Queue;

use InvalidArgumentException;
use Tygh\Addons\Queue\Connectors\ConnectorInterface;

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
        $this->job_pool = $job_pool;
    }

    /**
     * Find and execute jobs.
     */
    public function launch(): void
    {
        do {
            [$job_data, $message] = $this->connector->receive();

            if (!$job_data) {
                if (time() > ($this->ttl + TIME)) {
                    printf("TTL exceeded\n");
                    exit();
                }

                sleep(1);
                continue;
            }

            printf("Attempting to handle %s\n%s\n", $job_data['id'], str_repeat("=", 80));

            try {
                $job = $this->job_pool->getJob($job_data['queue_id']);
            } catch (InvalidArgumentException $e) {
                printf("Unable to find job handler %s\n", $job_data['queue_id']);
                goto job_finish;
            }

            $job->handle($job_data, $message);

            job_finish:
            printf("%s\nFinished job %s\n", str_repeat("=", 80), $job_data['id']);
            $this->connector->delete($job_data['id']);
        } while (1);
    }
}
