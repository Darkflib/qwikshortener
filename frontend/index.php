<?php
$start=microtime(TRUE);

if (($_SERVER['DOCUMENT_URI']=='/') or ($_SERVER['DOCUMENT_URI']=='/index.php')) {
$direct=TRUE;
}


$redis = new Redis();
$redis->pconnect('127.0.0.1', 6379);

if (!$direct) {
  $key='url:'.$_SERVER['HTTP_HOST'].':'.substr($_SERVER['DOCUMENT_URI'],1);
  $url=$redis->get($key);
  
  if ($url) {
    $metrickey='urlmetric:'.$_SERVER['HTTP_HOST'].':'.substr($_SERVER['DOCUMENT_URI'],1);
    $redis->incr($metrickey);
    $end=microtime(TRUE);
    header('x-profiletime: '.($end-$start));
    header('Location: '.$url);
    exit;
  }

}

//no url
//get error url
$key='errurl:'.$_SERVER['HTTP_HOST'].':nourl';
$url=$redis->get($key);

$end=microtime(TRUE);

if ($url) {
  header('x-profiletime: '.($end-$start));
  header('Location: '.$url);
  exit;
} else {
  header('x-profiletime: '.($end-$start));
  $url='http://www.sysdom.com/?fallthru';
  header('Location: '.$url);
  exit;
}

