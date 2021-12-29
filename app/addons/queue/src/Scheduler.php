<?php

namespace Tygh\Addons\Queue;

use Tygh\Addons\Queue\CronExpressionParser\Expression;

/**
 * Class Scheduler
 * @package Tygh\Addons\Queue
 */
class Scheduler
{
    protected JobPool $pool;

    protected Expression $expression_parser;

    public function __construct(
        JobPool $pool,
        Expression $expression_parser
    ) {
        $this->pool = $pool;
        $this->expression_parser = $expression_parser;
    }

    /**
     * Schedule a job.
     *
     * @param string $fqcn
     * @param $message
     */
    public function schedule(string $fqcn, $message)
    {
        $this->pool->getJob($fqcn)->schedule($message);
    }

    /**
     * Schedule jobs to be run (cron).
     */
    public function scheduleCronJobs(): void
    {
        $this->pool->apply(function (JobInterface $job) {
            if (!$job->hasCronExpression()) {
                return;
            }

            if ($this->expression_parser->isCronDue($job->getCronExpression())) {
                $job->schedule();
            }
        });
    }
}