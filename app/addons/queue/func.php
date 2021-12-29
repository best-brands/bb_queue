<?php

function fn_queue_dispatch(string $fcqn, $message)
{
    if (Tygh::$app->has($fcqn)) {
        Tygh::$app[$fcqn]->schedule($message);
    } else {
        (new $fcqn)->schedule($message);
    }
}

function fn_queue_dispatch_sync(string $fcqn, $message)
{
    if (Tygh::$app->has($fcqn)) {
        Tygh::$app->get($fcqn)->handle([], $message);
    } else {
        (new $fcqn)->handle([], $message);
    }
}

