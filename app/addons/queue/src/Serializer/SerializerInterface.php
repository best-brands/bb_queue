<?php

namespace Tygh\Addons\Queue\Serializer;

/**
 * Serializer interface.
 */
interface SerializerInterface
{
    /**
     * Serialize data.
     *
     * @param $data
     *
     * @return string
     */
    public function serialize($data): string;

    /**
     * Unserialize data.
     *
     * @param string $data
     *
     * @return mixed
     */
    public function unserialize(string $data);
}
