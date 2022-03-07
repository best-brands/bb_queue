<?php

namespace PHPSTORM_META {
    override(sql_injection_subst(), map([
        "?:" => "cscart_"
    ]));

    override(
        new \Tygh\Application,
        map([
            '' => '@',
        ])
    );
}
