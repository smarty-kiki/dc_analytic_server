<?php

define('DIALOGUE_POOL_TUBE', 'dialogue_pool');
define('DIALOGUE_WAITING_USER_TUBE_PREFIX', 'dialogue_waiting_');

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

/**
 * dispatch
 */
function _dialogue_waiting_user_tubes($user_id)
{/*{{{*/
    $tubes = cache_keys(DIALOGUE_WAITING_USER_TUBE_PREFIX.$user_id.'_*');

    sort($tubes);

    return $tubes;
}/*}}}*/

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
    $tubes = _dialogue_waiting_user_tubes($user_id);

    $tube = reset($tubes);

    $tube = $tube? $tube: DIALOGUE_POOL_TUBE;

    return _dialogue_push($user_id, $content, $delay, $priority, $tube, $config_key);
}/*}}}*/

function dialogue_push_to_other_operator($user_id, $content, $delay = 0, $priority = 10, $config_key = 'default')
{/*{{{*/
    $tubes = _dialogue_waiting_user_tubes($user_id);

    $now_tube = _dialogue_waiting_user_tube();

    $now_index = array_search($now_tube, $tubes);

    $tube = array_key_exists($now_index + 1, $tubes)? $tubes[$now_index + 1]: DIALOGUE_POOL_TUBE;

    return _dialogue_push($user_id, $content, $delay, $priority, $tube, $config_key);
}/*}}}*/

/**
 * operator
 */

function _dialogue_pull($tube, $timeout = null, $config_key = 'default')
{/*{{{*/
    $fp = _beanstalk_connection($config_key);
    _beanstalk_watch($fp, $tube);

    $job_instance = _beanstalk_reserve($fp, $timeout);
    $id = $job_instance['id'];

    _beanstalk_delete($fp, $id);
    _beanstalk_ignore($fp, $tube);

    return unserialize($job_instance['body']);
}/*}}}*/

function _dialogue_content_match($content, $pattern)
{/*{{{*/
    static $match_key = 0;
    static $catch_key = 1;

    $count = preg_match_all($pattern, $content, $matches);

    if (array_key_exists($match_key, $matches)) {
        if (array_key_exists($catch_key, $matches)) {
            $res = $matches[$catch_key];
            if ($res) {
                return $res;
            } else {
                return false;
            }
        } else {
            if ($matches[$match_key]) {
                return true;
            } else {
                return false;
            }
        }
    } else {
        return false;
    }
}/*}}}*/

function _dialogue_operator_topic_user($user_id = null)
{/*{{{*/
    static $container = null;

    if (! is_null($user_id)) {
        $container = $user_id;
    }

    return $container;
}/*}}}*/

function _dialogue_operator_talking_with_user($user_id, closure $action)
{/*{{{*/
    _dialogue_operator_topic_user($user_id);

    $res = call_user_func($action);

    _dialogue_operator_topic_user(false);

    return $res;
}/*}}}*/

function _dialogue_waiting_user_tube($user_id = null)
{/*{{{*/
    static $container = null;

    if (! is_null($user_id)) {
        if ($user_id) {
            $container = DIALOGUE_WAITING_USER_TUBE_PREFIX.$user_id.'_'.microtime(true);
        } else {
            $container = null;
        }
    }

    return $container;
}/*}}}*/

function _dialogue_operator_waiting_with_user($user_id, $timeout, closure $action)
{/*{{{*/
    $user_tube = _dialogue_waiting_user_tube($user_id);

    cache_increment($user_tube, 1, $timeout);

    $res = null;

    try {

        $res = call_user_func($action, $user_tube);

    } catch (Exception $ex) {
    } finally {

        cache_delete($user_tube);
        _dialogue_waiting_user_tube(false);

        return $res;
    }
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

    for (;;) {

        if (memory_get_usage(true) > $memory_limit) {
            throw new Exception('dialogue_watch out of memory');
        }

        if ($received_signal) {
            break;
        }

        $message = _dialogue_pull(DIALOGUE_POOL_TUBE, null, $config_key);

        $user_id = $message['user_id'];
        $content = $message['content'];
        $time = $message['time'];

        if (now($time." +1 min") > now()) {

            $matched_topic = false;

            foreach ($topics as $info) {

                foreach ((array) $info['topic'] as $topic) {

                    list($matched_topic, $args) = dialogue_topic_match($content, $topic);

                    if ($matched_topic) {

                        _dialogue_operator_talking_with_user($user_id, function () use ($info, $user_id, $content, $time, $args) {
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

function dialogue_ask_and_wait($user_id, $ask, $pattern = null, $timeout = 60, $config_key = 'default')
{/*{{{*/
    $timeout_time = time() + $timeout;

    return _dialogue_operator_waiting_with_user($user_id, $timeout, function ($user_tube) use ($timeout_time, $user_id, $ask, $pattern, $config_key) {

        dialogue_say($user_id, $ask);

        for (;;) {

            $timeout = $timeout_time - time();

            if ($timeout <= 0) {
                return null;
            }

            $message = _dialogue_pull($user_tube, $timeout, $config_key);

            $content = $message['content'];

            if (is_null($pattern)) {
                return $content;
            }

            $matched = _dialogue_content_match($content, $pattern);

            if ($matched) {
                return $matched;
            } else {
                /**kiki*/error_log(print_r($content.'未匹配 pattern ', true)."\n", 3, '/tmp/error_user.log');
            }
        }
    });
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
