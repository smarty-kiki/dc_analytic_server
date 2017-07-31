<?php

command('crawler:yunbi', '从云币抓取数据', function () {

    $infos = remote_get_json('https://yunbi.com/api/v2/tickers.json');

    foreach ($infos as $dc_name => $info) {

        $data = $info['ticker'];
        $data['at'] = $info['at'];

        storage_insert($dc_name, $data);
    }
});

command('crawler:yunbi-clean', '清除从云币抓取的数据', function () {

    $infos = remote_get_json('https://yunbi.com/api/v2/tickers.json');

    foreach ($infos as $dc_name => $info) {

        storage_delete($dc_name);

    }

});
