<?php

require "../vendor/autoload.php";
//
go(function () {
    $client = \Hooklife\SwooleDockerApi\Docker::createClient("https://161.117.84.76:9588", [
//        'ssl_cert_file' => '/Users/hooklife/Projects/debug-docker-php/tls/cert.pem',
//        'ssl_key_file'  => '/Users/hooklife/Projects/debug-docker-php/tls/key.pem'
    ]);
    ['Id' => $execId] = $client->createExec('nginx', [
        "AttachStdin"  => true,
        "AttachStdout" => true,
        "AttachStderr" => true,
        "Tty"          => true,
        "Cmd"          => ['/bin/sh'],
    ]);

    /** @var \Swoole\Coroutine\Client $socket * */
    $socket = $client->startExec($execId, ['Detach' => false]);

    $socket->send("echo 123\n");
    while (null !== $chunk = $socket->recv()) {
        echo implode(unpack("H*", $chunk));
        break;
    }

});