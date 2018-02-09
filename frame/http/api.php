<?php

/**
 * if is https.
 *
 * @return bool
 */
function is_https()
{
    if (isset($_SERVER['HTTPS']) && (strtolower($_SERVER['HTTPS']) == 'on' || $_SERVER['HTTPS'] == 1)) {
        return true;
    }

    if (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') {
        return true;
    }

    return false;
}

/**
 * Get the current URI.
 *
 * @return string
 */
function uri()
{
    $url = is_https() ? 'https://' : 'http://';

    return $url.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
}

/**
 * Get the specified URI info.
 *
 * @param string $name
 *
 * @return mixed
 */
function uri_info($name = null)
{
    static $container = [];

    if (empty($container)) {
        $url = uri();

        $container = parse_url($url);
    }

    return (null === $name) ? $container : $container[$name];
}

/**
 * Route.
 *
 * @param string $rule
 *
 * @return array
 */
function route($rule)
{
    $reg = '/^'.str_replace('\*', '([^\/]+?)', preg_quote($rule, '/')).'$/';

    preg_match_all($reg, uri_info('path'), $matches);

    $args = [];

    if ($matched = !empty($matches[0])) {
        unset($matches[0]);

        foreach ($matches as $v) {
            $args[] = $v[0];
        }
    }

    return [$matched, $args];
}

/**
 * Flush the action result.
 *
 * @param closure $action
 * @param array   $args
 */
function flush_action(closure $action, $args = [], closure $verify = null)
{
    if (is_null($verify)) {
        $output = call_user_func_array($action, $args);
    } else {
        $output = $verify($action, $args);
    }

    if (! is_null($output)) {
        echo $output;
        flush();
    }
}

/**
 * Route for all method.
 *
 * @param string  $rule
 * @param closure $action
 */
function if_any($rule, closure $action)
{
    list($matched, $args) = route($rule);

    if ($matched) {

        flush_action($action, $args, if_verify());
        exit;
    }
}

/**
 * Route for get method.
 *
 * @param string  $rule
 * @param closure $action
 */
function if_get($rule, closure $action)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        return;
    }

    if_any($rule, $action);
}

/**
 * Route for post method.
 *
 * @param string  $rule
 * @param closure $action
 */
function if_post($rule, closure $action)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if_any($rule, $action);
}

/**
 * Route for put method.
 *
 * @param string  $rule
 * @param closure $action
 */
function if_put($rule, closure $action)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
        return;
    }

    if_any($rule, $action);
}

/**
 * Route for delete method.
 *
 * @param string  $rule
 * @param closure $action
 */
function if_delete($rule, closure $action)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        return;
    }

    if_any($rule, $action);
}

/**
 * Get or set the verify closure.
 *
 * @param closure $action
 */
function if_verify(closure $action = null)
{
    static $container = null;

    if (!empty($action)) {
        return $container = $action;
    }

    return $container;
}

/**
 * Get or set the 404 handler.
 *
 * @param closure $action
 */
function if_not_found(closure $action = null)
{
    static $container = null;

    if (!empty($action)) {
        return $container = $action;
    }

    return $container;
}

/**
 * Redirect to 404.
 *
 * @param mix $action
 */
function not_found($action = null)
{
    header('HTTP/1.1 404 Not Found');
    header('status: 404 Not Found');

    if ($action instanceof closure) {
        flush_action($action);
        exit;
    }

    $action = if_not_found();

    if ($action instanceof closure) {
        flush_action($action, func_get_args());
        exit;
    }
}

/**
 * Redirect to a URI.
 *
 * @param string $uri
 * @param bool   $forever
 */
function redirect($uri, $forever = false)
{
    if (empty($uri) || !is_string($uri)) {
        return $uri;
    }

    if ($forever) {
        header('HTTP/1.1 301 Moved Permanently');
    }

    header('Location: '.$uri);

    exit;
}

/**
 * Get specified _GET/_POST without filte XSS.
 *
 * @param string $name
 * @param mix    $default
 *
 * @return mixed
 */
function input_safe($name, $default = null)
{
    if (isset($_POST[$name])) {
        return filter_input(INPUT_POST, $name, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    if (isset($_GET[$name])) {
        return filter_input(INPUT_GET, $name, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    return $default;
}

/**
 * Get specified _GET, _POST.
 *
 * @param string $name
 * @param mix    $default
 *
 * @return mixed
 */
function input($name, $default = null)
{
    if (isset($_POST[$name])) {
        return $_POST[$name];
    }

    if (isset($_GET[$name])) {
        return $_GET[$name];
    }

    return $default;
}

/**
 * Get specified _GET/_POST array.
 *
 * @param string $name ..
 *
 * @return array
 */
function input_list()
{
    $names = func_get_args();

    if (empty($names)) {
        return [];
    }

    $values = [];

    foreach ($names as $name) {
        $values[] = input($name);
    }

    return $values;
}

/**
 * Get an item from Json Decode _POST using "dot" notation.
 * 
 * @param mixed $name 
 * @param mixed $default 
 * @access public
 * @return mix
 */
function input_json($name, $default = null)
{
    static $post_data = null;

    if (is_null($default)) {
        $post_data = json_decode(input_post_raw(), true);
    }

    return array_get($post_data, $name, $default);
}

function input_post_raw()
{/*{{{*/
    return file_get_contents('php://input');
}/*}}}*/

/**
 * Get items from Json Decode _POST using "dot" notation.
 * 
 * @access public
 * @return array
 */
function input_json_list()
{
    $names = func_get_args();

    if (empty($names)) {
        return [];
    }

    $values = [];

    foreach ($names as $name) {
        $values[] = input_json($name);
    }

    return $values;
}

/**
 * Get specified cookie without filte XSS.
 *
 * @param string $name
 *
 * @return mixed
 */
function cookie_safe($name, $default = null)
{
    if (isset($_COOKIE[$name])) {
        return filter_input(INPUT_COOKIE, $name, FILTER_SANITIZE_SPECIAL_CHARS);
    }

    return $default;
}

/**
 * Get specified _COOKIE.
 *
 * @param string $name
 *
 * @return mixed
 */
function cookie($name, $default = null)
{
    if (isset($_COOKIE[$name])) {
        return $_COOKIE[$name];
    }

    return $default;
}

/**
 * Get specified _COOKIE array.
 *
 * @param string $name ..
 *
 * @return mixed
 */
function cookie_list()
{
    $names = func_get_args();

    if (empty($names)) {
        return [];
    }

    $values = [];

    foreach ($names as $name) {
        $values[] = cookie($name);
    }

    return $values;
}

/**
 * Convert data to json
 *
 * @param array  $data
 * @param string $callback
 *
 * @return string
 */
function json($data = [])
{
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

/**
 * Response 304 with ETag.
 *
 * @param string
 */
function cache_with_etag($etag)
{
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
        header('HTTP/1.1 304 Not Modified');

        exit;
    }

    header('ETag: '.$etag);
}

/**
 * get client ip.
 *
 * @return string
 */
function ip()
{
    static $container = null;

    if (is_null($container)) {
        if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
            $ip = getenv('REMOTE_ADDR');
        } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
            $ip = $_SERVER['REMOTE_ADDR'];
        } else {
            $ip = 'unknown';
        }

        return $container = preg_replace('/^[^0-9]*?(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}).*$/', '\1', $ip);
    }

    return $container;
}

/**
 * Get or set the exception handler.
 *
 * @param closure $action
 */
function if_has_exception(closure $action = null)
{
    static $container = null;

    if (!empty($action)) {
        return $container = $action;
    }

    return $container;
}

function http_err_action($error_type, $error_message, $error_file, $error_line, $error_context = null)
{
    $message = $error_message.' '.$error_file.' '.$error_line;

    http_ex_action(new Exception($message));
}

function http_ex_action($ex)
{
    $action = if_has_exception();

    if ($action instanceof closure) {
        flush_action($action, [$ex]);
        exit;
    }

    throw $ex;
}

function http_fatel_err_action()
{
    $err = error_get_last();

    if (not_empty($err)) {
        http_err_action($err['type'], $err['message'], $err['file'], $err['line']);
    }
}
