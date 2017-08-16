<?php

function crawl_announcement_table()
{
    return "crawler_announcement";
}

queue_job('crawl_jubi_announcement', function ()
{/*{{{*/
    try {
        $jubi_domain = 'https://www.jubi.com';

        $html = file_get_contents($jubi_domain.'/gonggao/');
        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = new html_parser($html);

        $new_list = $dom->find('.new_list', 0);
        $titles = $new_list->find('.title');
        $titles = array_reverse($titles);

        foreach ($titles as $title) {

            $url = $jubi_domain.$title->href;

            if (! db_simple_query_first(crawl_announcement_table(), ['url' => $url])) {
                db_simple_insert(crawl_announcement_table(), [
                    'title' => $title->plaintext,
                    'url' => $url,
                    'web' => 'jubi',
                    'at' => now(),
                ]);
                slack_say_to_smarty_dc('jubi: '.$title->plaintext.' '.$url);
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('jubi: 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [], $tube = 'default', $config_key = 'default');/*}}}*/
