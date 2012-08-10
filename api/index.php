<?php
require_once ('stdinc.php');
header('Content-type: text/plain');
if (!isset($_REQUEST['username']) or !isset($_REQUEST['apikey'])) {
    echo 'Error: username and key are required';
    die();
}
if (!isset($_REQUEST['endpoint'])) {
    echo 'Error: an endpoint must be specified';
    die();
}
if (!isset($_REQUEST['domain'])) {
    $domain = $_SERVER['HTTP_HOST'];
    if (substr($domain, 0, 4) == 'api.') $domain = substr($domain, 4);
} else {
    $domain = $_REQUEST['domain'];
}
//check auth
$key = 'urluser:' . $domain . ':' . $_REQUEST['username'];
$user = $redis->get($key);
$user = json_decode($user, TRUE);
if (!isset($_REQUEST['apikey']) or ($user['key'] != $_REQUEST['apikey'])) {
    echo 'Error: Authentication failed';
    die();
}
$endpoint = $_REQUEST['endpoint'];
$method = strtoupper(isset($_REQUEST['method']) ? $_REQUEST['method'] : $_SERVER['REQUEST_METHOD']);
if (isset($_REQUEST['debug'])) {
    echo "\nendpoint: " . $endpoint;
    echo "\nmethod: " . $method;
    echo "\nusername: " . $_REQUEST['username'];
    echo "\nkey: " . $_REQUEST['apikey'];
    echo "\ndomain: " . $domain;
    echo "\nshortcode: " . (isset($_REQUEST['shortcode']) ? $_REQUEST['shortcode'] : '');
    echo "\n\n";
}
function gen_shortcode() {
    $rand = mt_rand(0, 33554431);
    return base_convert($rand, 10, 36);
}
if ($endpoint == 'list') {
    switch ($method) {
        case 'GET':
            $return = array();
            $keys = $redis->keys('url:' . $domain . '*');
            foreach ($keys as $key) {
                $url = $redis->get($key);
                list(, $domain, $shortcode) = explode(':', $key);
                $return[] = array('http://' . $domain . '/' . $shortcode => $url);
            }
            echo json_encode($return);
            exit;
    }
    echo 'Error: method unknown for this endpoint';
    die();
}
if ($endpoint == 'url') {
    switch ($method) {
        case 'GET':
            if (!isset($_REQUEST['shortcode'])) {
                die('Error: shortcode not set');
            }
            $key = 'url:' . $domain . ':' . $_REQUEST['shortcode'];
            $url = $redis->get($key);
            if ($url) {
                $return = array('shortcode' => $_REQUEST['shortcode'], 'url' => $url, 'domain' => $domain);
                $key = 'urlmetric:' . $domain . ':' . $_REQUEST['shortcode'];
                $hits = $redis->get($key);
                if (!$hits) $hits = 0;
                $return['hits'] = $hits;
                echo json_encode($return);
                exit;
            } else {
                echo json_encode('unknown');
                exit;
            }
        case 'POST':
            //we assume that the user wants to update/create the shortcode
            if (!isset($_REQUEST['shortcode'])) {
                die('Error: shortcode not set');
            }
            if (!isset($_REQUEST['destination'])) {
                die('Error: destination not set');
            }
            $key = 'url:' . $domain . ':' . $_REQUEST['shortcode'];
            if (isset($_REQUEST['expires'])) {
                $result = $redis->setex($key, $_REQUEST['expires'], $_REQUEST['destination']);
                echo json_encode($result ? 'set' : 'failed');
                exit;
            } else {
                $result = $redis->set($key, $_REQUEST['destination']);
                echo json_encode($result ? 'set' : 'failed');
                exit;
            }
        case 'DELETE':
            if (!isset($_REQUEST['shortcode'])) {
                die('Error: shortcode not set');
            }
            $key = 'url:' . $domain . ':' . $_REQUEST['shortcode'];
            $result = $redis->del($key);
            echo json_encode($result ? 'deleted' : 'failed');
            exit;
        case 'PUT':
            if (!isset($_REQUEST['shortcode'])) {
                $shortcode = gen_shortcode();
            } else {
                $shortcode = $_REQUEST['shortcode'];
            }
            if (!isset($_REQUEST['destination'])) {
                die('Error: destination not set');
            }
            $key = 'url:' . $domain . ':' . $shortcode;
            $result = $redis->setnx($key, $_REQUEST['destination']);
            if (!$result and !isset($REQUEST['shortcode'])) { //retry one more time
                $shortcode = gen_shortcode();
                $key = 'url:' . $domain . ':' . $shortcode;
                $result = $redis->setnx($key, $_REQUEST['destination']);
            }
            if (!$result) {
                echo json_encode('failed');
            } else {
                if (isset($_REQUEST['expires'])) {
                    $result = $redis->expire($key, $_REQUEST['expires']);
                }
                echo json_encode(array('shortcode' => 'http://' . $domain . '/' . $shortcode));
            }
            exit;
    }
    echo 'Error: method unknown for this endpoint';
    die();
}
