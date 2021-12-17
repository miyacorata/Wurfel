<?php
require_once __DIR__.'/vendor/autoload.php';

if(!file_exists(__DIR__.'/config.php')){
    http_response_code(500);
    die('Config file not exits.');
}else{
    require_once __DIR__.'/config.php';
}

/* $client = new \GuzzleHttp\Client([
    'base_uri' => 'https://',
    'timeout' => 2
]); */

$headers = getallheaders();

$input = file_get_contents('php://input');
$payload = json_decode($input, true);
$target = $payload['repository']['name'] ?? '';
$hmac = (!empty($config[$target]['key']))
    ? str_replace('sha256=', '',hash_hmac('sha256', $input, $config[$target]['key']))
    : '';

$output = '# '.date('Y-m-d H:i:s').' '.uniqid().PHP_EOL;
$output .= '= = = = = = = HEADER = = = = = = ='.PHP_EOL;
foreach ($headers as $key => $value){
    $output .= $key.' : '.$value.PHP_EOL;
}
$output .= '= = = = = = = INPUT  = = = = = = ='.PHP_EOL.$input.PHP_EOL;

if(empty($config[$target])){
    $output .= '[Error] Config not found. Aborted.';
    writeLog($output);
    http_response_code(404);
    die('Config not found.');
}

if(($headers['X-Hub-Signature-256'] ?? '') === $hmac){
    $return = array(date('Y-m-d H:i:s'));
    writeLog($output);
    exec($config[$target]['do'], $return);
    writeLog(implode(PHP_EOL, $return).PHP_EOL.'[Info] exec() call maybe finished.'.PHP_EOL);
}else{
    $output .= '[Error] Signature not match. Aborted.';
    writeLog($output);
    http_response_code(400);
    die('Signature not match.');
}

function writeLog($data){
    file_put_contents(__DIR__.'/log.txt', $data.PHP_EOL, FILE_APPEND);
}