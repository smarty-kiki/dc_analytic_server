<?php

function crawl_announcement_table()
{
    return "crawler_announcement";
}

queue_job('crawl_jubi_announcement', function ()
{/*{{{*/
    try {
        $jubi_domain = 'https://www.jubi.com';

        $html = remote_get($jubi_domain.'/gonggao/', 10);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

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
                    'at' => time(),
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

        $html = remote_get($bter_domain.'/articlelist/ann', 10, 3, ['Accept-Language: zh-CN,zh;q=0.8,en;q=0.6']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

        $anns = $dom->find('.latnewslist');
        $anns = array_reverse($anns);

        foreach ($anns as $k => $ann) {

            $url = trim($bter_domain.$ann->find('a', 0)->href);
            $title= trim($ann->find('h3', 0)->plaintext);

            if (! db_simple_query_first(crawl_announcement_table(), ['url' => $url])) {
                db_simple_insert(crawl_announcement_table(), [
                    'title' => $title,
                    'url' => $url,
                    'web' => 'bter',
                    'at' => time(),
                ]);

                slack_say_to_smarty_dc('[bter] '.$title.' '.$url);
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[bter] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawl_yunbi_announcement', function ()
{/*{{{*/
    try {
        $yunbi_domain = 'https://yunbi.zendesk.com';

        $html = remote_get($yunbi_domain.'/hc/zh-cn/sections/115001467347-区块链资产品种介绍');

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');

        $dom = str_get_html($html);
        $titles = $dom->find('.article-list a');
        $titles = array_reverse($titles);

        foreach ($titles as $title) {

            $url = trim($yunbi_domain.$title->href);
            $title_text = trim($title->plaintext);

            if (! db_simple_query_first(crawl_announcement_table(), ['url' => $url])) {
                db_simple_insert(crawl_announcement_table(), [
                    'title' => $title_text,
                    'url' => $url,
                    'web' => 'yunbi',
                    'at' => time(),
                ]);
                slack_say_to_smarty_dc('[yunbi] 云币新币介绍 '.$title_text.' '.$url);
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[yunbi] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawl_szzc_announcement', function ()
{/*{{{*/
    try {
        $szzc_domain = 'https://szzc.com';
        $url_template = 'https://szzc.com/#!/news/';

        $res = remote_get_json($szzc_domain.'/api/news/articles/NOTICE?language=zh', 10);

        if (! $res) {
            return false;
        }

        $data = $res['result']['data'];
        $data = array_reverse($data);

        foreach ($data as $info) {

            $url = $url_template.$info['id'];
            $title = trim(str_replace('【公告】', '', $info['subject']));

            if (! db_simple_query_first(crawl_announcement_table(), ['url' => $url])) {
                db_simple_insert(crawl_announcement_table(), [
                    'title' => $title,
                    'url' => $url,
                    'web' => 'szzc',
                    'at' => time(),
                ]);
                slack_say_to_smarty_dc('[szzc] '.$title.' '.$url);
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[szzc] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawl_btc9_announcement', function ()
{/*{{{*/
    try {
        $btc9_domain = 'https://www.btc9.com';

        $html = remote_get($btc9_domain.'/Art/index/id/1.html', 10);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');

        $dom = str_get_html($html);
        $titles = $dom->find('.list-group-item');
        $titles = array_reverse($titles);

        foreach ($titles as $title) {

            $title_text = $title->find('a', 0)->plaintext;

            if (stristr($title_text, '【上币公告】')) {
                $title_text = trim(str_replace('【上币公告】', '', $title_text));
            } else {
                continue;
            }

            $url = trim($btc9_domain.$title->find('a', 0)->href);

            if (! db_simple_query_first(crawl_announcement_table(), ['url' => $url])) {
                db_simple_insert(crawl_announcement_table(), [
                    'title' => $title_text,
                    'url' => $url,
                    'web' => 'btc9',
                    'at' => time(),
                ]);
                slack_say_to_smarty_dc('[btc9] '.$title_text.' '.$url);
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[btc9] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawl_btc38_announcement', function ()
{/*{{{*/
    try {
        $btc38_domain = 'http://www.btc38.com';

        $res = remote_get_json($btc38_domain.'/newsInfo.php?n='.rand(), 10);

        if (! $res) {
            return false;
        }

        $res = $res['notice'];
        $res = array_reverse($res);

        foreach ($res as $info) {

            $title_text = $info['title'];

            if (str_ireplace(['开放', '开启'], '', $title_text) != $title_text) {
                $title_text = trim($title_text);
            } else {
                continue;
            }

            $url = trim($info['url']);

            if (! db_simple_query_first(crawl_announcement_table(), ['url' => $url])) {
                db_simple_insert(crawl_announcement_table(), [
                    'title' => $title_text,
                    'url' => $url,
                    'web' => 'btc38',
                    'at' => time(),
                ]);
                slack_say_to_smarty_dc('[btc38] '.$title_text.' '.$url);
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[btc38] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawl_btop_announcement', function ()
{/*{{{*/
    try {
        $btop_domain = 'https://www.b.top';

        $html = remote_get($btop_domain.'/notice/index.html?id=2', 10);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');

        $dom = str_get_html($html);
        $titles = $dom->find('.snc-max');
        $titles = array_reverse($titles);

        foreach ($titles as $title) {

            $url = trim($btop_domain.$title->find('.snc-right a', 0)->href);
            $title_text = trim($title->find('.snc-right a h3', 0)->plaintext);

            if (str_ireplace(['上線'], '', $title_text) == $title_text) {
                continue;
            }

            if (! db_simple_query_first(crawl_announcement_table(), ['url' => $url])) {
                db_simple_insert(crawl_announcement_table(), [
                    'title' => $title_text,
                    'url' => $url,
                    'web' => 'btop',
                    'at' => time(),
                ]);
                slack_say_to_smarty_dc('[btop] '.$title_text.' '.$url);
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[btop] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/
