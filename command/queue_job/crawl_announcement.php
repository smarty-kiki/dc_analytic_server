<?php

function crawl_announcement_table()
{
    return "crawler_announcement";
}

function crawl_announcement_save_and_send_slack($title, $url, $web)
{/*{{{*/
    if (! $ann = db_simple_query_first(crawl_announcement_table(), ['url' => $url])) {
        db_simple_insert(crawl_announcement_table(), [
            'title' => $title,
            'url' => $url,
            'web' => $web,
            'at' => time(),
        ]);
        slack_say_to_smarty_dc('['.$web.'] '.$title.' '.$url);
    } else {
        if ($title != $ann['title']) {
            db_simple_update(crawl_announcement_table(), ['url' => $url], [
                'title' => $title
            ]);
            slack_say_to_smarty_dc('['.$web.'] 调整公告标题 '.$title.' '.$url);
        }
    }
}/*}}}*/

queue_job('crawl_jubi_announcement', function ()
{/*{{{*/
    try {
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

            crawl_announcement_save_and_send_slack($title, $url, 'jubi');
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
        $domain = 'https://bter.com';

        $html = remote_get($domain.'/articlelist/ann', 10, 3, ['Accept-Language: zh-CN,zh;q=0.8,en;q=0.6']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

        $anns = $dom->find('.latnewslist');
        $anns = array_reverse($anns);

        foreach ($anns as $k => $ann) {

            $url = trim($domain.$ann->find('a', 0)->href);
            $title= trim($ann->find('h3', 0)->plaintext);

            crawl_announcement_save_and_send_slack($title, $url, 'bter');
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
        $domain = 'https://yunbi.zendesk.com';

        $html = remote_get($domain.'/hc/zh-cn/sections/115001467347-区块链资产品种介绍');

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');

        $dom = str_get_html($html);
        $anns = $dom->find('.article-list a');
        $anns = array_reverse($anns);

        foreach ($anns as $ann) {

            $url = trim($domain.$ann->href);
            $title= '云币新币介绍'.trim($ann->plaintext);

            crawl_announcement_save_and_send_slack($title, $url, 'yunbi');
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
        $domain = 'https://szzc.com';
        $url_template = 'https://szzc.com/#!/news/';

        $res = remote_get_json($domain.'/api/news/articles/NOTICE?language=zh', 10);

        if (! $res) {
            return false;
        }

        $data = $res['result']['data'];
        $data = array_reverse($data);

        foreach ($data as $info) {

            $url = $url_template.$info['id'];
            $title = trim(str_replace('【公告】', '', $info['subject']));

            crawl_announcement_save_and_send_slack($title, $url, 'szzc');
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
        $domain = 'https://www.btc9.com';

        $html = remote_get($domain.'/Art/index/id/1.html', 10);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');

        $dom = str_get_html($html);
        $anns = $dom->find('.list-group-item');
        $anns = array_reverse($anns);

        foreach ($anns as $ann) {

            $title = $ann->find('a', 0)->plaintext;

            if (stristr($title, '【上币公告】')) {
                $title = trim(str_replace('【上币公告】', '', $title));
            } else {
                continue;
            }

            $url = trim($domain.$ann->find('a', 0)->href);

            crawl_announcement_save_and_send_slack($title, $url, 'btc9');
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
        $domain = 'http://www.btc38.com';

        $res = remote_get_json($domain.'/newsInfo.php?n='.rand(), 10);

        if (! $res) {
            return false;
        }

        $res = $res['notice'];
        $res = array_reverse($res);

        foreach ($res as $info) {

            $title = $info['title'];

            if (str_ireplace(['开放', '开启'], '', $title) != $title) {
                $title = trim($title);
            } else {
                continue;
            }

            $url = trim($info['url']);

            crawl_announcement_save_and_send_slack($title, $url, 'btc38');
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
        $domain = 'https://www.b.top';

        $html = remote_get($domain.'/notice/index.html?id=2', 10);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');

        $dom = str_get_html($html);
        $anns = $dom->find('.snc-max');
        $anns = array_reverse($anns);

        foreach ($anns as $ann) {

            $url = trim($domain.$ann->find('.snc-right a', 0)->href);
            $title = trim($ann->find('.snc-right a h3', 0)->plaintext);

            if (str_ireplace(['上線'], '', $title) == $title) {
                continue;
            }

            crawl_announcement_save_and_send_slack($title, $url, 'btop');
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[btop] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/
