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
