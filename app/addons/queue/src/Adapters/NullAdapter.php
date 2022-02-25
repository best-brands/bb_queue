<?php

namespace Tygh\Addons\Queue\Adapters;

use Tygh\Addons\Queue\Jobs\JobInterface;

class NullAdapter extends Adapter implements AdapterInterface
{
    /**
     * @inheritDoc
     * @return int
     */
    public function size(?string $queue = null): int
    {
        return 0;
    }

    /**
     * @inheritDoc
     * @return mixed
     */
    public function push($job, $data = '', ?string $queue = null)
    {
        return null;
    }

    /**
     * @inheritDoc
     * @return mixed
     */
    public function pushRaw(string $payload, ?string $queue = null, array $options = [])
    {
        return null;
    }

    /**
     * @inheritDoc
     * @return mixed
     */
    public function later($delay, $job, $data = '', ?string $queue = null)
    {
        return null;
    }

    /**
     * @inheritDoc
     * @return JobInterface|null
     */
    public function pop(?string $queue = null): ?JobInterface
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function clear(string $queue): int
    {
        return 0;
    }
}
