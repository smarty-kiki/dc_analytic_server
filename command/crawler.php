<?php

command('crawler:announcement', '抓取各交易平台公告', function ()
{/*{{{*/

    queue_push('crawl_jubi_announcement');
    queue_push('crawl_bter_announcement');
    queue_push('crawl_yunbi_announcement');
    queue_push('crawl_szzc_announcement');
    queue_push('crawl_btc9_announcement');
    queue_push('crawl_btc38_announcement');
    queue_push('crawl_btop_announcement');
    queue_push('crawl_binance_announcement');
    queue_push('crawl_okcoin_announcement');
    queue_push('crawl_huobi_announcement');
    queue_push('crawl_yuanbao_announcement');

});/*}}}*/

command('crawler:ico', '抓取各 ico 平台上新', function ()
{/*{{{*/

    queue_push('crawl_icoage_ico');
    queue_push('crawl_icoinfo_ico');
    queue_push('crawl_renrenico_ico');
    queue_push('crawl_icooo_ico');
    queue_push('crawl_3ico_ico');
    queue_push('crawl_aimwise_ico');
    queue_push('crawl_binance_ico');

});/*}}}*/
