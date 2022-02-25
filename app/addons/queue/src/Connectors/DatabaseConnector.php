<?php

namespace Tygh\Addons\Queue\Connectors;

use Tygh\Addons\Queue\Adapters\AdapterInterface;
use Tygh\Addons\Queue\Adapters\DatabaseAdapter;
use Tygh\Database\Connection;

/**
 * Class MySQL
 * @package Tygh\Backend\Queue
 */
class DatabaseConnector implements ConnectorInterface
{
    /**
     * Database connections.
     *
     * @var Connection
     */
    protected Connection $connection;

    /**
     * Create a new connector instance.
     *
     * @param Connection $connection
     *
     * @return void
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Establish a queue connection.
     *
     * @param array $config
     *
     * @return AdapterInterface
     */
    public function connect(array $config): AdapterInterface
    {
        return new DatabaseAdapter(
            $this->connection,
            $config['queue'],
            $config['retry_after'] ?? 60,
        );
    }
}
