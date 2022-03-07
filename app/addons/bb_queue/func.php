<?php

use Tygh\Addons\Queue\Facades\Dispatcher;
use Tygh\Exceptions\DeveloperException;

if (!function_exists('trait_uses_recursive')) {
    /**
     * Returns all traits used by a trait and its traits.
     *
     * @param string $trait
     *
     * @return array
     */
    function trait_uses_recursive(string $trait): array
    {
        $traits = class_uses($trait) ?: [];

        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (!function_exists('class_uses_recursive')) {
    /**
     * Returns all traits used by a class, its parent classes and trait of their traits.
     *
     * @param object|string $class
     *
     * @return array
     */
    function class_uses_recursive($class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += trait_uses_recursive($class);
        }

        return array_unique($results);
    }
}

/**
 * Dispatch a job to the queue.
 *
 * @throws DeveloperException
 */
function fn_queue_dispatch($command): void
{
    Dispatcher::dispatch($command);
}

/**
 * Dispatch a job in synchronous mode.
 *
 * @param $command
 *
 * @return void
 * @throws DeveloperException
 */
function fn_queue_dispatch_sync($command): void
{
    Dispatcher::dispatchSync($command);
}

/**
 * Function to retrieve failed jobs.
 *
 * @param array $params
 *
 * @return array
 */
function fn_queue_get_failed_jobs(array $params = []): array
{
    // Placeholder

    return [];
}

