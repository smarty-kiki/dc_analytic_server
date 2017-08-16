<?php

function crawl_announcement_table()
{
    return "crawler_announcement";
}

queue_job('crawl_jubi_announcement', function ()
{/*{{{*/
    try {
        $jubi_domain = 'https://www.jubi.com';

        $html = remote_get($jubi_domain.'/gonggao/');

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = new html_parser($html);

        $new_list = $dom->find('.new_list', 0);
        $titles = $new_list->find('.title');
        $titles = array_reverse($titles);

        foreach ($titles as $title) {

            $url = trim($jubi_domain.$title->href);
            $title_text = trim($title->plaintext);

            if (! db_simple_query_first(crawl_announcement_table(), ['url' => $url])) {
                db_simple_insert(crawl_announcement_table(), [
                    'title' => $title_text,
                    'url' => $url,
                    'web' => 'jubi',
                    'at' => now(),
                ]);
                slack_say_to_smarty_dc('[jubi] '.$title_text.' '.$url);
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[jubi] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawl_bter_announcement', function ()
{/*{{{*/
    try {
        $bter_domain = 'https://bter.com';

        $html = remote_get($bter_domain.'/articlelist/ann', 3, 3, ['Accept-Language: zh-CN,zh;q=0.8,en;q=0.6']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $html = stristr($html, '<div class="latnewslist">');
        $html = stristr($html, '<div class="newsplink">', true);
        $html = preg_replace('/id=".*"/', '', $html);
        preg_match_all('/h3\>(.*)\<\//', $html, $matches);

        if (! $matches[1]) {
            return false;
        }

        $titles = $matches[1];
        $titles = array_reverse($titles);

        $dom = new html_parser($html);

        $hrefs = $dom->find('a');
        $hrefs = array_reverse($hrefs);

        foreach ($titles as $k => $title) {

            $url = trim($bter_domain.$hrefs[$k]->href);
            $title_text = trim($title);

            if (! db_simple_query_first(crawl_announcement_table(), ['url' => $url])) {
                db_simple_insert(crawl_announcement_table(), [
                    'title' => $title_text,
                    'url' => $url,
                    'web' => 'bter',
                    'at' => now(),
                ]);
                slack_say_to_smarty_dc('[bter] '.$title_text.' '.$url);
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[bter] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/
