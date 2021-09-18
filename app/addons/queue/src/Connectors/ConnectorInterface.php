<?php

namespace Tygh\Addons\Queue\Connectors;

/**
 * Interface QueueInterface
 * @package Tygh\Addons\Queue
 */
interface ConnectorInterface
{
    /**
     * Send a message to the Queue
     *
     * @param string $queue
     * @param string $message
     *
     * @return bool
     */
    public function send(string $queue, string $message): bool;

    /**
     * Receive a message from the queue
     *
     * @return mixed
     */
    public function receive(): array;

    /**
     * Delete a message from the queue
     *
     * @param $receipt
     *
     * @return mixed
     */
    public function delete($receipt);
}
