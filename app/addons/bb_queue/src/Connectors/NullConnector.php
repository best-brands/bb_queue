<?php

namespace Tygh\Addons\Queue\Connectors;

use Tygh\Addons\Queue\Adapters\AdapterInterface;
use Tygh\Addons\Queue\Adapters\NullAdapter;

class NullConnector implements ConnectorInterface
{
    public function connect(array $config): AdapterInterface
    {
        return new NullAdapter();
    }
}
