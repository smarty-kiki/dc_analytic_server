<?php

dialogue_topic(['提醒我*'], function ($user_id, $content, $time, $description) {/*{{{*/

    $description = strtoupper(trim($description));

    $time = dialogue_ask_and_wait($user_id, '什么时候提醒你呢？');

    $time = now($time);

    db_simple_insert('reminder', [
        'user_id' => $user_id,
        'description' => $description,
        'at' => $time,
    ]);

    dialogue_say($user_id, '好的，我将在 '.$time.' 提醒你 '.$description);

});/*}}}*/
