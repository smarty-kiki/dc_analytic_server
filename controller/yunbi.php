<?php

if_get('/yunbi/*', function ($table)
{
    return storage_query($table);
});
