<?php

// File to get autocomplete when using the `\Tygh::$app` shorthand.
namespace PHPSTORM_META {
    override(
        new \Tygh\Application,
        map([
            '' => '@',
        ])
    );
}
