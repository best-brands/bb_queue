<?php

namespace Tygh\Addons\Queue\Jobs;

class JobName
{
    /**
     * Parse the given job name into a class / method array.
     *
     * @param string $job
     *
     * @return array
     */
    public static function parse(string $job): array
    {
        return self::parseCallback($job, 'fire');
    }

    /**
     * Parse a Class[@]method style callback into class and method.
     *
     * @param string      $callback
     * @param string|null $default
     *
     * @return array<int, string|null>
     */
    public static function parseCallback(string $callback, ?string $default = null): array
    {
        return strpos($callback, '@') !== false
            ? explode('@', $callback, 2)
            : [$callback, $default];
    }

    /**
     * Get the resolved name of the queued job class.
     *
     * @param string $name
     * @param array  $payload
     *
     * @return string
     */
    public static function resolve(string $name, array $payload): string
    {
        if (!empty($payload['displayName'])) {
            return $payload['displayName'];
        }

        return $name;
    }
}
