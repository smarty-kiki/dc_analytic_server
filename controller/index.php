<?php

if_get('/', function ()
{
    return coinmarketcap_get_tickers();
});
