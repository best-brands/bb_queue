<?php

namespace Tygh\Addons\Queue;

use Closure;
use InvalidArgumentException;
use Tygh\Addons\Queue\Adapters\AdapterInterface;
use Tygh\Addons\Queue\Connectors\ConnectorInterface;
use Tygh\Application;

class Manager
{
    protected Application $app;

    /**
     * The array of resolved queue connections.
     *
     * @var array
     */
    protected array $connections = [];

    /**
     * The array of resolved queue connectors.
     *
     * @var array
     */
    protected array $connectors = [];

    /**
     * Create a new queue manager instance.
     *
     * @param Application $app
     *
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Resolve a queue connection instance.
     *
     * @param string|null $name
     *
     * @return AdapterInterface
     */
    public function connection(?string $name = null): AdapterInterface
    {
        $name = $name ?: $this->getDefaultDriver();

        if (!isset($this->connections[$name])) {
            $this->connections[$name] = $this->resolve($name);
            $this->connections[$name]->setContainer($this->app);
        }

        return $this->connections[$name];
    }

    /**
     * Resolve a queue connection.
     *
     * @param string $name
     *
     * @return AdapterInterface
     */
    protected function resolve(string $name): AdapterInterface
    {
        $config = $this->getConfig($name);

        return $this->getConnector($config['driver'])
                    ->connect($config)
                    ->setConnectionName($name)
        ;
    }

    /**
     * Get the connector for a given driver.
     *
     * @param string $driver
     *
     * @return ConnectorInterface
     *
     * @throws InvalidArgumentException
     */
    protected function getConnector(string $driver): ConnectorInterface
    {
        if (!isset($this->connectors[$driver])) {
            throw new InvalidArgumentException("No connector for [$driver].");
        }

        return call_user_func($this->connectors[$driver]);
    }

    /**
     * Add a queue connection resolver.
     *
     * @param string  $driver
     * @param Closure $resolver
     *
     * @return void
     */
    public function addConnector(string $driver, Closure $resolver)
    {
        $this->connectors[$driver] = $resolver;
    }


    /**
     * Get the queue connection configuration.
     *
     * @param string $name
     *
     * @return array|null
     */
    protected function getConfig(string $name): ?array
    {
        if ($name !== 'null') {
            return $this->app['addons.queue.config']['connections'][$name] ?? null;
        }

        return ['driver' => 'null'];
    }

    /**
     * Get the name of the default queue connection.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->app['addons.queue.config']['default'];
    }

    /**
     * Set the name of the default queue connection.
     *
     * @param string $name
     *
     * @return void
     */
    public function setDefaultDriver(string $name)
    {
        $this->app['addons.queue.config']['default'] = $name;
    }

    /**
     * Get the full name for the given connection.
     *
     * @param string|null $connection
     *
     * @return string
     */
    public function getName(?string $connection = null): string
    {
        return $connection ?: $this->getDefaultDriver();
    }

    /**
     * Get the application instance used by the manager.
     *
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * Set the application instance used by the manager.
     *
     * @param Application $app
     *
     * @return $this
     */
    public function setApplication(Application $app): Manager
    {
        $this->app = $app;

        foreach ($this->connections as $connection) {
            $connection->setContainer($app);
        }

        return $this;
    }
}
