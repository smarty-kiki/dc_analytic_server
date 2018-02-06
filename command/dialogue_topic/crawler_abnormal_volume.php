<?php

dialogue_topic('帮我查一下 * 的价格', function ($user_id, $message, $time, $symbol) {

    dialogue_say($user_id, $symbol.' 涨爆了');

    dialogue_ask_and_wait($user_id, '要按市价买吗', 180, function ($user_id, $message, $time) {

        if (! stristr($message, '不')) {

            // code here
            dialogue_say($user_id, '好，下单了');
        }
    });

});
