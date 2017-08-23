<?php

function slack_say_to_smarty_dc($message)
{
    return remote_post('https://hooks.slack.com/services/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', json_encode([
        'text' => $message
    ]));
}

function slack_say_to_smarty_ds($message)
{
    return remote_post('https://hooks.slack.com/services/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', json_encode([
        'text' => $message
    ]));
}
