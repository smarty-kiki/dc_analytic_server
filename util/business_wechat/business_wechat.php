<?php

define('BUSINESS_WECHAT_CORPID', 'wwf3effabd6b904bb2');

function _business_wechat_access_token()
{/*{{{*/
    static $cache_key = 'business_wechat_access_token';

    static $corp_id = BUSINESS_WECHAT_CORPID;
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

function business_wechat_verify_url($msg_signature, $timestamp, $nonce, $echostr)
{/*{{{*/
    static $corpid = BUSINESS_WECHAT_CORPID;
    static $token = 'beDAqA5M1Q1U';
    static $encoding_AES_key = 'MWSuUOIbQrxgjGQBHG077RHJN4GybzEOyjxhMX1TOhG';

    if (strlen($encoding_AES_key) != 43) {
        throw new Exception('IllegalAesKey');
    }

    $signature = business_wechat_sha1($token, $timestamp, $nonce, $echostr);

    if ($signature != $msg_signature) {
        throw new Exception('ValidateSignatureError');
    }

    return business_wechat_prpcrypt_decrypt($encoding_AES_key, $echostr, $corpid);
}/*}}}*/

function business_wechat_prpcrypt_decrypt($encoding_AES_key, $echostr, $corpid)
{/*{{{*/
    try {
        $key = base64_decode($encoding_AES_key);

        $ciphertext_dec = base64_decode($echostr);
        $iv = substr($key, 0, 16);
        $decrypted = openssl_decrypt($ciphertext_dec, 'aes-256-cbc', $key, $options = 1 | OPENSSL_NO_PADDING, $iv);
    } catch (Exception $e) {
        throw new Exception('DecryptAESError');
    }

    try {
        $result = business_wechat_pkcs7_decode($decrypted);
        if (strlen($result) < 16) {
            return '';
        }
        $content = substr($result, 16, strlen($result));
        $len_list = unpack("N", substr($content, 0, 4));
        $xml_len = $len_list[1];
        $xml_content = substr($content, 4, $xml_len);
        $from_corpid = substr($content, $xml_len + 4);
    } catch (Exception $e) {
        throw new Exception('IllegalBuffer');
    }

    if ($from_corpid != $corpid) {
        throw new Exception("ValidateCorpidError $from_corpid != $corpid");
    }

    return $xml_content;
}/*}}}*/

function business_wechat_pkcs7_decode($text)
{/*{{{*/
    $pad = ord(substr($text, -1));
    if ($pad < 1 || $pad > 32) {
        $pad = 0;
    }
    return substr($text, 0, (strlen($text) - $pad));
}/*}}}*/

function business_wechat_sha1($token, $timestamp, $nonce, $encrypt_msg)
{/*{{{*/
    try {
        $array = array($encrypt_msg, $token, $timestamp, $nonce);
        sort($array, SORT_STRING);
        $str = implode($array);

        return sha1($str);
    } catch (Exception $e) {
        throw new Exception('ComputeSignatureError');;
    }
}/*}}}*/
