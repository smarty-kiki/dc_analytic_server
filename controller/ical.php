<?php

if_get('/ical/reminder/*', function ($user_id)
{/*{{{*/
    $reminders = db_simple_query('reminder', ['user_id' => $user_id], 'order by at');

    if ($reminders) {
        $ical = new iCal();

        foreach ($reminders as $reminder) {
            $ical->new_event();
            $ical->set_title($reminder['description']);
            $ical->set_description($reminder['description']);
            $ical->set_dates(now($reminder['at']), now($reminder['at'] + 3600));
            $ical->set_status("confirmed");
            $ical->set_alarm();
            $ical->set_alarm_text("");
            $ical->set_alarm_trigger(600);
        }
        $ical->Write();
    }

    exit;
});/*}}}*/
