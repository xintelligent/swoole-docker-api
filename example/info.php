<?php

require "../vendor/autoload.php";
//
go(function () {
    $client = \Hooklife\SwooleDockerApi\Docker::createClient("https://47.92.249.208:2015", [
//        'ssl_cert_file' => '/Users/hooklife/Projects/debug-docker-php/tls/cert.pem',
//        'ssl_key_file'  => '/Users/hooklife/Projects/debug-docker-php/tls/key.pem'
    ]);
    var_dump($client->info());
});