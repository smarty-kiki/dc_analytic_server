<?php

function crawl_ico_table()
{
    return "crawler_ico";
}

queue_job('crawl_icoage_ico', function ()
{/*{{{*/
    try {
        $icoage_domain = 'http://www.icoage.com';

        $html = remote_get($icoage_domain.'/?p=search&flag=2', 3, 3, ['Accept-Language: zh-CN,zh;q=0.8'], ['lang'=>'cn']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

        $icos = $dom->find('.isotope-item');
        $icos = array_reverse($icos);

        foreach ($icos as $ico) {


            $title = trim($ico->find('.thumb-info-inner', 0)->plaintext);
            $time = trim($ico->find('.thumb-info-type', 0)->plaintext);
            $url = $icoage_domain.trim($ico->find('a', 0)->href);

            if ($time == '尚未确定') {
                continue;
            } else {
                $time_tmp = explode(' - ', $time);
                $from = strtotime($time_tmp[0]);
                $to = strtotime($time_tmp[1]);
            }

            if (! db_simple_query_first(crawl_ico_table(), ['url' => $url])) {
                db_simple_insert(crawl_ico_table(), [
                    'title' => $title,
                    'url' => $url,
                    'web' => 'icoage',
                    'at' => time(),
                    'from' => $from,
                    'to' => $to,
                ]);
                slack_say_to_smarty_dc('[icoage] 新确定的众筹 '.$title.' '.$url);
            }
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
        $icoinfo_domain = 'https://ico.info';

        $html = remote_get($icoinfo_domain.'/projects?status=comming_soon', 3, 3, ['Accept-Language: zh-CN,zh;q=0.8'], ['lang'=>'cn']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

        $icos = $dom->find('.project-item');
        $icos = array_reverse($icos);

        foreach ($icos as $ico) {


            $title = trim($ico->find('.project-detail h3 a', 0)->plaintext);
            $url = $icoinfo_domain.trim($ico->find('.project-detail h3 a', 0)->href);
            $time_str = trim($ico->find('script', 0)->innertext);

            foreach (explode(';', $time_str) as $line) {
                if (stristr($line, 'var icoStartAt')) {
                    $str = explode('"', $line);
                    $from = $str[1];
                } elseif (stristr($line, 'var icoEndAt')) {
                    $str = explode('"', $line);
                    $to = $str[1];
                }
            }

            if (! db_simple_query_first(crawl_ico_table(), ['url' => $url])) {
                db_simple_insert(crawl_ico_table(), [
                    'title' => $title,
                    'url' => $url,
                    'web' => 'icoinfo',
                    'at' => time(),
                    'from' => strtotime($from),
                    'to' => strtotime($to),
                ]);
                slack_say_to_smarty_dc('[icoinfo] 新确定的众筹 '.$title.' '.$url);
            }
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
        $renrenico_domain = 'https://renrenico.com';

        $html = remote_get($renrenico_domain.'/', 10, 3, ['Accept-Language: zh-CN,zh;q=0.8'], ['lang'=>'cn']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

        $icos = $dom->find('#icoWill .item-box');
        $icos = array_reverse($icos);

        foreach ($icos as $ico) {

            $time_str = trim($ico->find('.item-state', 0)->plaintext);
            if (stristr($time_str, '待定')) {
                continue;
            }

            $title = trim($ico->find('.ico-item-title', 0)->plaintext);
            $url = $renrenico_domain.trim($ico->find('.ico-item-title', 0)->href);

            $time_str = strtotime(str_replace([
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
                's',
            ], $time_str));

            if (! db_simple_query_first(crawl_ico_table(), ['url' => $url])) {
                db_simple_insert(crawl_ico_table(), [
                    'title' => $title,
                    'url' => $url,
                    'web' => 'renrenico',
                    'at' => time(),
                    'from' => $time_str,
                    'to' => $time_str + 7200,
                ]);
                slack_say_to_smarty_dc('[renrenico] 新确定的众筹 '.$title.' '.$url);
            }
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
        $icooo_domain = 'http://www.icooo.com';

        $html = remote_get($icooo_domain.'/Issue/index/status/begin.html', 3, 3, ['Accept-Language: zh-CN,zh;q=0.8'], ['lang'=>'cn']);

        if (! $html) {
            return false;
        }

        $html = mb_convert_encoding($html, 'utf8', 'auto');
        $dom = str_get_html($html);

        $icos = $dom->find('.item-li');
        $icos = array_reverse($icos);

        foreach ($icos as $ico) {

            $title = trim($ico->find('.item-title', 0)->plaintext);
            $url = $icooo_domain.trim($ico->find('.item-title a', 0)->href);
            $time_str = trim($ico->find('.fore3 .num', 0)->plaintext);

            $time_str = strtotime('+'.str_replace([
                '天',
                '小时',
            ], [
                'days +',
                'hours',
            ], $time_str));

            if (! db_simple_query_first(crawl_ico_table(), ['url' => $url])) {
                db_simple_insert(crawl_ico_table(), [
                    'title' => $title,
                    'url' => $url,
                    'web' => 'icooo',
                    'at' => time(),
                    'from' => $time_str,
                    'to' => $time_str + 7200,
                ]);
                slack_say_to_smarty_dc('[icooo] 新确定的众筹 '.$title.' '.$url);
            }
        }
    } catch (Exception $ex) {
        slack_say_to_smarty_dc('[icooo] 数据抓取出问题了');
        throw $ex;
    }

    return true;
}, $priority = 10, $retry = [3, 3, 3], $tube = 'default', $config_key = 'default');/*}}}*/
