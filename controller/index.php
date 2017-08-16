<?php

if_get('/', function ()
{
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
    return 'hello world';
});
