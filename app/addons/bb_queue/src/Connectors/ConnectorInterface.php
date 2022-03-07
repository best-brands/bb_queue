<?php

namespace Tygh\Addons\Queue\Connectors;

use Tygh\Addons\Queue\Adapters\AdapterInterface;

/**
 * Interface QueueInterface
 * @package Tygh\Addons\Queue
 */
interface ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return AdapterInterface
     */
    public function connect(array $config): AdapterInterface;
}
