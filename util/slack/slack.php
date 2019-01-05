<?php

function slack_say_to_smarty_coin($message)
{
    return remote_post('https://hooks.slack.com/services/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', json_encode([
        'text' => $message
    ]), 10);
}
