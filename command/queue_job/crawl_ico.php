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
                $from = now($time_tmp[0]);
                $to = now($time_tmp[1]);
            }

            if (! db_simple_query_first(crawl_ico_table(), ['url' => $url])) {
                db_simple_insert(crawl_ico_table(), [
                    'title' => $title,
                    'url' => $url,
                    'web' => 'icoage',
                    'at' => now(),
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
                    'at' => now(),
                    'from' => $from,
                    'to' => $to,
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
