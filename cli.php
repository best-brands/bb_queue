<?php

if (php_sapi_name() !== 'cli') {
    exit(502);
}

define('AREA', 'A');
define('ACCOUNT_TYPE', 'admin');
define('NO_SESSION', true);

try {
    require(dirname(__FILE__) . '/init.php');

    fn_dispatch();
} catch (Exception $e) {
    \Tygh\Tools\ErrorHandler::handleException($e);
} catch (Throwable $e) {
    \Tygh\Tools\ErrorHandler::handleException($e);
}
