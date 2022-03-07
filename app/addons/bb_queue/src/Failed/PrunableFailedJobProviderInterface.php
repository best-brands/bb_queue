<?php

namespace Tygh\Addons\Queue\Failed;

use DateTimeInterface;

interface PrunableFailedJobProviderInterface
{
    /**
     * Prune all the entries older than the given date.
     *
     * @param DateTimeInterface $before
     *
     * @return int
     */
    public function prune(DateTimeInterface $before): int;
}
