<?php

namespace Tygh\Addons\Queue\Serializer;

/**
 * Json serializer.
 */
class JsonSerializer implements SerializerInterface
{
    /**
     * @inheritDoc
     */
    public function serialize($data): string
    {
        $result = json_encode($data);

        if (false === $result) {
            throw new \InvalidArgumentException("Unable to serialize value. Error: " . json_last_error_msg());
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function unserialize(string $string)
    {
        $result = json_decode($string, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Unable to unserialize value. Error: " . json_last_error_msg());
        }

        return $result;
    }
}
