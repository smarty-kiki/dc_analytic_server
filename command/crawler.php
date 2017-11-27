<?php

command('crawler:abnormal-volume', '抓取各交易平台异常交易量', function ()
{/*{{{*/

    queue_push('crawler_bittrex_abnormal_volume');

});/*}}}*/
