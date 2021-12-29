<?php

namespace Tygh\Addons\Queue\Exceptions;

/**
 * When a job can not be handled and should be rescheduled.
 */
class RescheduleException extends JobException
{
    protected int $delay;

    public function __construct($delay)
    {
        $this->delay = $delay;
        parent::__construct("Jobs needs rescheduling");
    }

    public function getDelay(): int
    {
        return $this->delay;
    }
}
