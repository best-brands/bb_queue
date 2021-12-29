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
     * Return the amount of items in a queue.
     *
     * @param string $queue
     *
     * @return int
     */
    public function countInQueue(string $queue): int;

    /**
     * Reschedule a job.
     *
     * @param int  $id
     * @param int  $time
     * @param bool $relative
     *
     * @return mixed
     */
    public function reschedule(int $id, int $time, bool $relative = true);

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
