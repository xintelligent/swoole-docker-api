<?php
require "../vendor/autoload.php";
go(function () {
    $client = \MobingiLabs\SwooleDockerApi\Docker::createClient("https://161.117.84.76:9588", [
//        'ssl_cert_file' => '/Users/hooklife/Projects/debug-docker-php/tls/cert.pem',
//        'ssl_key_file'  => '/Users/hooklife/Projects/debug-docker-php/tls/key.pem'
    ]);
    $response = $client->imagePull("php:7-fpm");

    while ($chunk = $response->recv()) {
        var_dump($chunk);
    }
});