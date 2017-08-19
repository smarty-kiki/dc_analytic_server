<?php

if_get('/ical/ico', function ()
{/*{{{*/
    $icos = db_simple_query(crawl_ico_table(), ['from >=' => strtotime('-2 days')], 'order by at limit 100');

    if ($icos) {
        $ical = new iCal();

        foreach ($icos as $ico) {
            $ical->new_event();
            $ical->set_title($ico['web'].': '.$ico['title']);
            $ical->set_description($ico['url']);
            $ical->set_dates(now($ico['from']), now($ico['from'] + 3600));
            $ical->set_status("confirmed");
            $ical->set_alarm();
            $ical->set_alarm_text("");
            $ical->set_alarm_trigger(600);
        }
        $ical->Write();
    }

    exit;
});/*}}}*/
