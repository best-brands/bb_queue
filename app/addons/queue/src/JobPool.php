<?php

namespace Tygh\Addons\Queue;

use Closure;
use Exception;
use InvalidArgumentException;

/**
 * Class ConnectorPool
 * @package Tygh\Addons\Marketplaces
 */
class JobPool
{
    /** @var JobInterface[] */
    protected array $jobs;

    /**
     * ConnectorPool constructor.
     *
     * @param JobInterface[] $jobs
     */
    public function __construct(array $jobs)
    {
        $this->addJobs($jobs);
    }

    /**
     * Add jobs to pool.
     *
     * @param JobInterface[] $jobs
     *
     * @throws InvalidArgumentException
     */
    public function addJobs(array $jobs): void
    {
        foreach ($jobs as $job) {
            if (!$job instanceof JobInterface) {
                throw new InvalidArgumentException(
                    sprintf('Job must be an instance of %s', JobInterface::class)
                );
            }

            $this->jobs[$job->getName()] = $job;
        }
    }

    /**
     * Get job.
     *
     * @param string $job_name
     *
     * @return JobInterface
     * @throws InvalidArgumentException
     */
    public function getJob(string $job_name): JobInterface
    {
        if (!isset($this->jobs[$job_name])) {
            throw new InvalidArgumentException(sprintf('Invalid job name "%s"', $job_name));
        }

        return $this->jobs[$job_name];
    }

    /**
     * Iterate over all jobs
     *
     * @param Closure $closure
     * @param string  $filter
     */
    public function apply(Closure $closure, string $filter = JobInterface::class)
    {
        foreach ($this->jobs as $job) {
            if ($job instanceof $filter) {
                try {
                    $closure($job);
                } catch (JobException $e) {
                } catch (Exception $e) {
                    fn_log_event(
                        'settings',
                        'error',
                        'Failed iterating over job ' . $job->getName() . PHP_EOL
                        . $e->getMessage() . PHP_EOL
                        . $e->getMessage() . PHP_EOL
                    );
                }
            }
        }
    }
}
