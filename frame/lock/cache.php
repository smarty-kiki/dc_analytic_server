<?php

function lock($key, $expire_second, closure $closure)
{
    $res = cache_increment($key, 1, $expire_second);

    $locked = ($res > 1);

    if (! $locked) {
        call_user_func($closure);
    }
}

function serialize_call($key, $expire_second, closure $closure)
{

}
