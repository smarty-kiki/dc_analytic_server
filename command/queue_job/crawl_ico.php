<?php

function crawl_ico_table()
{
    return "crawler_ico";
}

function crawl_ico_save_and_send_slack($title, $url, $web, $from, $to)
{/*{{{*/
    if (! $ico = db_simple_query_first(crawl_ico_table(), ['url' => $url])) {
        db_simple_insert(crawl_ico_table(), [
            'title' => $title,
            'url' => $url,
            'web' => $web,
            'at' => time(),
            'from' => $from,
            'to' => $to,
        ]);
        slack_say_to_smarty_dc('['.$web.'] 新确定的众筹 '.$title.' '.$url);
    } else {
        if (abs($from - $ico['from']) > 60) {
            db_simple_update(crawl_ico_table(), ['url' => $url], [
                'from' => $from,
                'to' => $to,
            ]);
            slack_say_to_smarty_dc('['.$web.'] 调整众筹开始时间 '.$title.' '.$url);
        }
    }
}/*}}}*/

queue_job('crawl_icoage_ico', function ()
{/*{{{*/
    try {
        $domain = 'http://www.icoage.com';

        $html = remote_get($domain.'/?p=search&flag=2', 10, 3, ['Accept-Language: zh-CN,zh;q=0.8'], ['lang'=>'cn']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

        $icos = $dom->find('.isotope-item');

        foreach ($icos as $ico) {

            $title = trim($ico->find('.thumb-info-inner', 0)->plaintext);
            $time = trim($ico->find('.thumb-info-type', 0)->plaintext);
            $url = $domain.trim($ico->find('a', 0)->href);

            if ($time == '尚未确定') {
                continue;
            } else {
                $time_tmp = explode(' - ', $time);
                $from = strtotime($time_tmp[0]);
                $to = strtotime($time_tmp[1]);
            }

            crawl_ico_save_and_send_slack($title, $url, 'icoage', $from, $to);
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[icoage] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawl_icoinfo_ico', function ()
{/*{{{*/
    try {
        $domain = 'https://ico.info';

        $html = remote_get($domain.'/projects?status=comming_soon', 10, 3, ['Accept-Language: zh-CN,zh;q=0.8'], ['lang'=>'cn']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

        $icos = $dom->find('.project-item');

        foreach ($icos as $ico) {

            $title = trim($ico->find('.project-detail h3 a', 0)->plaintext);
            $url = $domain.trim($ico->find('.project-detail h3 a', 0)->href);
            $time_str = trim($ico->find('script', 0)->innertext);

            foreach (explode(';', $time_str) as $line) {
                if (stristr($line, 'var icoStartAt')) {
                    $str = explode('"', $line);
                    $from = strtotime($str[1]);
                } elseif (stristr($line, 'var icoEndAt')) {
                    $str = explode('"', $line);
                    $to = strtotime($str[1]);
                }
            }

            crawl_ico_save_and_send_slack($title, $url, 'icoinfo', $from, $to);
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[icoinfo] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawl_renrenico_ico', function ()
{/*{{{*/
    try {
        $domain = 'https://renrenico.com';

        $html = remote_get($domain.'/', 10, 3, ['Accept-Language: zh-CN,zh;q=0.8'], ['lang'=>'cn']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

        $icos = $dom->find('#icoWill .item-box');

        foreach ($icos as $ico) {

            $time_str = trim($ico->find('.item-state', 0)->plaintext);
            if (stristr($time_str, '待定')) {
                continue;
            }

            $title = trim($ico->find('.ico-item-title', 0)->plaintext);
            $url = $domain.trim($ico->find('.ico-item-title', 0)->href);

            $from = strtotime(str_replace([
                '距开始：',
                '天 ',
                '小时',
                '分',
                '秒',
            ], [
                '+',
                'days +',
                'hours +',
                'minutes +',
                'seconds',
            ], $time_str));

            crawl_ico_save_and_send_slack($title, $url, 'renrenico', $from, $from + 7200);
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[renrenico] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawl_icooo_ico', function ()
{/*{{{*/
    try {
        $domain = 'http://www.icooo.com';

        $html = remote_get($domain.'/Issue/index/status/begin.html', 10, 3, ['Accept-Language: zh-CN,zh;q=0.8'], ['lang'=>'cn']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

        $icos = $dom->find('.item-li');

        foreach ($icos as $ico) {

            $title = trim($ico->find('.item-title', 0)->plaintext);
            $url = $domain.trim($ico->find('.item-title a', 0)->href);
            $time_str = trim($ico->find('.fore3 .num', 0)->plaintext);

            $from = strtotime('+'.str_replace([
                '天',
                '小时',
            ], [
                'days +',
                'hours',
            ], $time_str));

            crawl_ico_save_and_send_slack($title, $url, 'icooo', $from, $from + 7200);
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[icooo] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/

queue_job('crawl_3ico_ico', function ()
{/*{{{*/
    try {
        $domain = 'https://www.3ico.com';

        $html = remote_get($domain.'/', 10, 3, ['Accept-Language: zh-CN,zh;q=0.8'], ['lang'=>'cn']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

        $icos = $dom->find('#js-icoing li');

        foreach ($icos as $ico) {

            $title = trim($ico->find('.desc', 0)->plaintext);
            $url = $domain.trim($ico->find('a', 0)->href);
            $time_str = trim($ico->find('.time', 0)->plaintext);

            $time_info = array_filter(explode('|', str_replace(['锁定：', '开始：', '结束：'], '|', $time_str)));
            $from = array_shift($time_info);
            $to = array_pop($time_info);

            crawl_ico_save_and_send_slack($title, $url, '3ico', $from, $from + 7200);
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[3ico] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/
