<?php

$schema = [
    /**
     * Default queue connection name
     */
    'default' => 'default',

    /**
     * All queue connections.
     *
     * Here you may configure the connection information for each server that
     * is used by your application. It will try to rely on the already exposed
     * CsCart connections, although in some cases this might not work reliably.
     *
     * Drivers: "database", "null"
     */
    'connections' => [
        'default' => [
            'driver'      => 'database',
            'queue'       => 'default',
            'retry_after' => 60,
        ],
    ],

    /**
     * All worker options.
     */
    'worker' => [
        /**
         * Here you may configure the default worker options, such as to which
         * queues it should listen by default, memory usage, timeouts, rest time,
         * the amount of time to sleep between job finding, etc.
         */
        'options' => [
            'name'            => 'default',
            'backoff'         => 0,
            'memory'          => 1024,
            'timeout'         => 60,
            'sleep'           => 3,
            'max_tries'       => 3,
            'force'           => false,
            'stop_when_empty' => false,
            'max_jobs'        => 0,
            'max_time'        => 300,
            'rest'            => 0,
        ],

        /**
         * To which queues to listen by default
         */
        'queues' => [
            'high',
            'default',
            'low',
        ],
    ],

    /**
     * Tweaks for certain environments.
     */
    'tweaks' => [
        /**
         * Whether to include "SKIP LOCKED" after "FOR UPDATE".
         */
        'skip_locked' => false,
    ],
];

return $schema;
