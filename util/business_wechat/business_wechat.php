<?php

function _business_wechat_access_token()
{/*{{{*/
    static $cache_key = 'business_wechat_access_token';

    static $corp_id = 'wwf3effabd6b904bb2';
    static $secret = 'z_ol9gewn1L9wVexQACEv64hFhG9LshLf2Uby9wfiDw';

    static $access_token = null;

    if (is_null($access_token)) {
        $access_token = cache_get($cache_key);

        if (! $access_token) {
            $info = remote_get_json("https://qyapi.weixin.qq.com/cgi-bin/gettoken?corpid=$corp_id&corpsecret=$secret", 3, 3);

            if (array_key_exists('access_token', $info)) {
                $access_token = $info['access_token'];
                cache_set($cache_key, $access_token, $info['expires_in'] - 5);
            } else {
                throw new Exception($info['errmsg']);
            }
        }
    }

    return $access_token;
}/*}}}*/

function business_wechat_send_message($user_ids, $party_ids, $tag_ids, $message)
{/*{{{*/
    $access_token = _business_wechat_access_token();

    $res = remote_post_json("https://qyapi.weixin.qq.com/cgi-bin/message/send?access_token=$access_token", json_encode([
        'touser' => implode('|', (array) $user_ids),
        'toparty' => implode('|', (array) $party_ids),
        'totag' => implode('|', (array) $tag_ids),
        'msgtype' => "text",
        'agentid' => 1000002,
        'text' => [
            'content' => $message,
        ],
        'safe' => 0
    ]));

    return ! $res['errcode'];
}/*}}}*/

function business_wechat_get_department()
{/*{{{*/
    $access_token = _business_wechat_access_token();

    return remote_get_json("https://qyapi.weixin.qq.com/cgi-bin/department/list?access_token=$access_token");
}/*}}}*/

function business_wechat_get_department_user_list($department_id)
{/*{{{*/
    $access_token = _business_wechat_access_token();

    return remote_get_json("https://qyapi.weixin.qq.com/cgi-bin/user/list?access_token=$access_token&department_id=$department_id&fetch_child=1");
}/*}}}*/
