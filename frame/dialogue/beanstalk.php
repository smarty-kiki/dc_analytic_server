<?php

define('DIALOGUE_POOL_TUBE', 'dialogue_pool');

/**
 * delegate
 */

function dialogue_send_action(closure $action = null)
{/*{{{*/
    static $container = null;

    if (!empty($action)) {
        return $container = $action;
    }

    return $container;
}/*}}}*/

function dialogue_topic_miss(closure $action = null)
{/*{{{*/
    static $container = null;

    if (!empty($action)) {
        return $container = $action;
    }

    return $container;
}/*}}}*/

/**
 * tool
 */
function dialogue_topics($topics = null)
{/*{{{*/
    static $container = [];

    if (is_null($topics)) {
        return $container;
    }

    return $container = $topics;
}/*}}}*/

function dialogue_topic_match($content, $topic)
{/*{{{*/
    $reg = '/^'.str_replace('\*', '([^\/]+?)', preg_quote($topic, '/')).'$/';

    preg_match_all($reg, $content, $matches);

    $args = [];

    if ($matched = !empty($matches[0])) {
        unset($matches[0]);

        foreach ($matches as $v) {
            $args[] = $v[0];
        }
    }

    return [$matched, $args];
}/*}}}*/

function _dialogue_topic_user_tube($user_id)
{/*{{{*/
    return 'dialogue_tube_'.$user_id;
}/*}}}*/

/**
 * dispatch
 */
function _dialogue_push($user_id, $content, $delay, $priority, $tube, $config_key)
{/*{{{*/
    $fp = _beanstalk_connection($config_key);

    _beanstalk_use_tube($fp, $tube);

    $id = _beanstalk_put(
        $fp,
        $priority,
        $delay,
        $run_time = 3,
        serialize([
            'user_id' => $user_id,
            'content' => $content,
            'time' => now(),
        ])
    );

    return $id;
}/*}}}*/

function dialogue_push($user_id, $content, $delay = 0, $priority = 10, $config_key = 'default')
{/*{{{*/
    if (cache_get(_dialogue_topic_user_tube($user_id))) {
        return dialogue_push_for_exists($user_id, $content, $delay, $priority, $config_key);
    } else {
        return dialogue_push_for_new($user_id, $content, $delay, $priority, $config_key);
    }
}/*}}}*/

function dialogue_push_for_new($user_id, $content, $delay = 0, $priority = 10, $config_key = 'default')
{/*{{{*/
    return _dialogue_push($user_id, $content, $delay, $priority, DIALOGUE_POOL_TUBE, $config_key);
}/*}}}*/

function dialogue_push_for_exists($user_id, $content, $delay = 0, $priority = 10, $config_key = 'default')
{/*{{{*/
    return _dialogue_push($user_id, $content, $delay, $priority, _dialogue_topic_user_tube($user_id), $config_key);
}/*}}}*/

/**
 * operator
 */

function dialogue_operator_topic_user($user_id = null)
{/*{{{*/
    static $container = null;

    if (! is_null($user_id)) {
        $container = $user_id;
    }

    return $container;
}/*}}}*/

function dialogue_operator_talking_with_user($user_id, closure $action)
{/*{{{*/
    dialogue_operator_topic_user($user_id);

    call_user_func($action);

    dialogue_operator_topic_user(false);
}/*}}}*/

function dialogue_operator_wait_user($user_id = null)
{/*{{{*/
    static $container = null;

    if (! is_null($user_id)) {
        $container = $user_id;
    }

    return $container;
}/*}}}*/

function dialogue_watch($config_key = 'default', $memory_limit = 1048576)
{/*{{{*/
    declare(ticks=1);
    $received_signal = false;
    pcntl_signal(SIGTERM, function () use (&$received_signal) {
        $received_signal = true;
    });

    $topics = dialogue_topics();
    $missed_action = dialogue_topic_miss();

    $fp = _beanstalk_connection($config_key);

    for (;;) {

        if (memory_get_usage(true) > $memory_limit) {
            throw new Exception('dialogue_watch out of memory');
        }

        if ($received_signal) {
            break;
        }

        _beanstalk_watch($fp, DIALOGUE_POOL_TUBE);
        $job_instance = _beanstalk_reserve($fp);
        $id = $job_instance['id'];
        $body = unserialize($job_instance['body']);
        _beanstalk_delete($fp, $id);
        _beanstalk_ignore($fp, DIALOGUE_POOL_TUBE);

        $user_id = $body['user_id'];
        $content = $body['content'];
        $time = $body['time'];

        if (now($time." +1 min") > now()) {

            $matched_topic = false;

            foreach ($topics as $info) {

                foreach ((array) $info['topic'] as $topic) {

                    list($matched_topic, $args) = dialogue_topic_match($content, $topic);

                    if ($matched_topic) {

                        dialogue_operator_talking_with_user($user_id, function () use ($info, $user_id, $content, $time, $args) {
                            call_user_func_array($info['closure'], array_merge([$user_id, $content, $time], $args));
                        });

                        continue(3);
                    }
                }
            }

            if (! $matched_topic) {
                if ($missed_action instanceof closure) {
                    call_user_func($missed_action, $user_id, $content, $time);
                }
            }
        }
    }
}/*}}}*/

function dialogue_ask_and_wait($user_id, $ask, $timeout, closure $action, $config_key = 'default')
{/*{{{*/
    dialogue_say($user_id, $ask);

    $fp = _beanstalk_connection($config_key);
    $user_tube = _dialogue_topic_user_tube($user_id);

    _beanstalk_watch($fp, $user_tube);

    $job_instance = _beanstalk_reserve($fp, $timeout);

    $id = $job_instance['id'];
    $body = unserialize($job_instance['body']);

    $user_id = $body['user_id'];
    $content = $body['content'];
    $time = $body['time'];

    _beanstalk_delete($fp, $id);
    _beanstalk_ignore($fp, $user_tube);

    if (now($time." +1 min") > now()) {

        cache_increment($user_tube, 1, $timeout);

        do { } while (! call_user_func($action, $user_id, $content, $time));

        cache_delete($user_tube);

        return true;
    }
}/*}}}*/

function dialogue_choice_and_wait($user_id, $ask, array $choice, $timeout, closure $action)
{/*{{{*/

}/*}}}*/

function dialogue_form_and_wait($user_id, $ask, array $form, $timeout, closure $action)
{/*{{{*/

}/*}}}*/

function dialogue_topic($topic, closure $closure)
{/*{{{*/
    $topics = dialogue_topics();

    $topics[] = [
        'topic' => $topic,
        'closure' => $closure,
    ];

    dialogue_topics($topics);
}/*}}}*/

function dialogue_say($user_id, $content)
{/*{{{*/
    $action = dialogue_send_action();

    call_user_func($action, $user_id, $content);
}/*}}}*/
