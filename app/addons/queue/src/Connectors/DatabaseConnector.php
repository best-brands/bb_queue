<?php

namespace Tygh\Addons\Queue\Connectors;

use Tygh\Addons\Queue\Serializer\SerializerInterface;

/**
 * Class MySQL
 * @package Tygh\Backend\Queue
 */
class DatabaseConnector implements ConnectorInterface
{
    /** @var string The message serializer to use. */
    protected SerializerInterface $serializer;

    /**
     * MySQL constructor.
     *
     * @param SerializerInterface $serializer
     */
    public function __construct(
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * Send something to the queue.
     *
     * @param string $queue
     * @param string $data
     *
     * @return bool
     */
    public function send(string $queue, $data): bool {
        $inserted_on = time();

        return (bool)db_query(
            'INSERT INTO ?:queue_messages (queue_id, body, inserted_on) VALUES (?s, ?s, ?s)',
            $queue,
            $this->serializer->serialize($data),
            $inserted_on
        );
    }

    /**
     * @inheritDoc
     */
    public function receive(): array
    {
        $consumer = mt_rand() . '-' . time();

        $result = db_query(
            'UPDATE ?:queue_messages SET consumer = ?s, read_on = UNIX_TIMESTAMP(NOW())'
            . ' WHERE (read_on IS NULL OR (UNIX_TIMESTAMP(NOW()) > read_on)) AND UNIX_TIMESTAMP(NOW()) >= inserted_on'
            . ' ORDER BY inserted_on, id ASC'
            . ' LIMIT 1',
            $consumer
        );

        if (!$result) {
            return [false, false];
        }

        $job_info = db_get_row('SELECT * FROM ?:queue_messages WHERE consumer = ?s LIMIT 1', $consumer);

        db_query('UPDATE ?:queue_messages SET read_times = read_times + 1 WHERE id = ?i', $job_info['id']);

        return [$job_info, $this->serializer->unserialize($job_info['body'])];
    }

    /**
     * @inheritDoc
     */
    public function delete($receipt)
    {
        return db_query(
            'DELETE FROM ?:queue_messages WHERE id = ?i OR (inserted_on < (UNIX_TIMESTAMP(NOW()) - ?i) AND read_times > 2)',
            $receipt,
            SECONDS_IN_DAY * 7,
        );
    }
}
