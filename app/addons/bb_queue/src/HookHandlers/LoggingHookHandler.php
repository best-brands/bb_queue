<?php

namespace Tygh\Addons\Queue\HookHandlers;

class LoggingHookHandler
{
    /**
     * Handle a log save.
     *
     * @param      $type
     * @param      $action
     * @param      $data
     * @param      $user_id
     * @param      $content
     * @param      $event_type
     * @param      $object_primary_keys
     * @param bool $suppress
     */
    public function onSaveLog(
        $type, $action, $data, $user_id, $content, $event_type, $object_primary_keys, bool &$suppress = true
    ): void {
        if (fn_get_ip()['host'] === '127.0.0.1' || php_sapi_name() === 'cli') {
            $suppress = true;
        }
    }
}
