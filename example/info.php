<?php

require "../vendor/autoload.php";
//
go(function () {
//    $client = \Hooklife\SwooleDockerApi\Docker::createClient([
//        'base_uri' => "https://47.92.249.208:2015",
//        'cert'     => '/Users/hooklife/Projects/debug-docker-php/tls/cert.pem',
//        'ssl_key'  => '/Users/hooklife/Projects/debug-docker-php/tls/key.pem',
//        'verify'   => false
//    ]);
//    $data = $client->info();

//    $client = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
//    $client->set([
//        'ssl_cert_file' => '/Users/hooklife/Projects/debug-docker-php/tls/cert.pem',
//        'ssl_key_file' => '/Users/hooklife/Projects/debug-docker-php/tls/key.pem'
//    ]);
//    if (!$client->connect('47.92.249.208', 2015, 0.5)) {
//        exit("connect failed. Error: {$client->errCode}\n");
//    }
//    $client->enableSSL();
//    $in = "GET /info HTTP/1.1\r\n";
//    $in .= "Host: 47.92.249.208\r\n";
//    $in .= "Connection: Close\r\n\r\n";
//    $client->send($in);
//    echo $client->recv();
//    $client->close();



    $client = new \Hooklife\SwooleDockerApi\Request\Request("https://47.92.249.208:2015", [
        'ssl_cert_file' => '/Users/hooklife/Projects/debug-docker-php/tls/cert.pem',
        'ssl_key_file'  => '/Users/hooklife/Projects/debug-docker-php/tls/key.pem'
    ]);
    $client->request("GET", '/info');
});




//// 設定連線 URL
//$url = 'http://127.0.0.1/GetTimeWebService.php';
//preg_match('/^(.+:\/\/)([^:\/]+):?(\d*)(\/.+)/', $url, $matches);
//$protocol = $matches[1];
//$host = $matches[2];
//$port = $matches[3];
//$uri = $matches[4];
//
//// 設定等等要傳送的 XML 資料
//$xml  = '';
//$xml .= '';
//
//// 開啟一個 TCP/IP Socket
//$fp = fsockopen($host, 80, $errno, $errstr, 5);
//if ($fp) {
//    // 設定 header 與 body
//    $httpHeadStr  = "POST {$url} HTTP/1.1\r\n";
//    $httpHeadStr .= "Content-type: application/xml\r\n";
//    $httpHeadStr .= "Host: {$host}:{$port}\r\n";
////    $httpHeadStr .= "Content-Length: ".strlen($xml)."\r\n";
//    $httpHeadStr .= "Connection: close\r\n";
//    $httpHeadStr .= "\r\n";
//    $httpBody ="\r\n";
//
//    // 呼叫 WebService
//    fputs($fp, $httpHeadStr.$httpBody);
//    $response = '';
//    while (!feof($fp)) {
//        $response .= fgets($fp, 2048);
//    }
//    fclose($fp);
//
//    // 顯示回傳資料
//    echo $response;
//} else {
//    die('Error:'.$errno.$errstr);
//}