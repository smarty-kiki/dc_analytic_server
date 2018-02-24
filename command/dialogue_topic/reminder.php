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

    dialogue_say($user_id, '好的，我将在 '.$time.' 提醒你');

});/*}}}*/

dialogue_topic(['取消*的提醒'], function ($user_id, $content, $time, $description) {/*{{{*/

    $description = strtoupper(trim($description));

    $reminders = db_simple_query('reminder', ['description like' => "%$description%", 'at >' => now()]);

    if ($reminders) {

        if (count($reminders) === 1) {

            $reminder = reset($reminders);

            $yes_or_no = dialogue_ask_and_wait($user_id, '是在 '.$reminder['at'].' 的 "'.$reminder['description'].'" 提醒吧');

            if (mb_stristr($yes_or_no, '不')) {
                dialogue_say($user_id, '没有其他与 "'.$description.'" 相关的提醒了');
            } else {
                db_simple_delete('reminder', ['id' => $reminder['id']]);

                dialogue_say($user_id, '取消了');
            }
        } else {
            $content = '有多个相关的提醒，回复我要取消的序号:';

            foreach ($reminders as $index => $reminder) {
                $content .= "\n$index. ".$reminder['description'].' '.$reminder['at'];
            }

            do {
                $index = dialogue_ask_and_wait($user_id, $content);
            } while (! isset($reminders[$index]));

            db_simple_delete('reminder', ['id' => $reminders[$index]['id']]);

            dialogue_say($user_id, '取消了');
        }
    } else {
        dialogue_say($user_id, '没有找到将来与 "'.$description.'" 相关的提醒');
    }
});/*}}}*/
