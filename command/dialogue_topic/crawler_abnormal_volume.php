<?php

dialogue_topic('查*的价格', function ($user_id, $message, $time, $symbol) {

    $symbol = trim($symbol);

    $res = remote_get_json("https://bittrex.com/api/v1.1/public/getticker?market=BTC-$symbol");

    if ($res['success']) {
        dialogue_say($user_id, $res['result']['Last'].' BTC');
    } else {
        dialogue_say($user_id, '币网没这个币');
    }

});
