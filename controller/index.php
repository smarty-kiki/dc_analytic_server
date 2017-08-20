<?php

if_get('/', function ()
{
    $domain = 'https://www.jubi.com';

    $html = remote_get($domain.'/gonggao/', 10);

    if (! $html) {
        return false;
    }

    $html = mb_convert_encoding($html, 'utf8', 'auto');
    $dom = str_get_html($html);

    $new_list = $dom->find('.new_list', 0);
    $anns = $new_list->find('.title');
    $anns = array_reverse($anns);

    foreach ($anns as $ann) {

        $url = trim($domain.$ann->href);
        $title= trim($ann->plaintext);

        crawl_announcement_save_and_send_slack($title.'1', $url, 'jubi');
    }
    return 'hello world';
});
