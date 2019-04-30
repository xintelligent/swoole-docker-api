<?php

require "../vendor/autoload.php";
//


go(function () {
    $client = \Hooklife\SwooleDockerApi\Docker::createClient("https://161.117.84.76:9588", [
//        'ssl_cert_file' => '/Users/hooklife/Projects/debug-docker-php/tls/cert.pem',
//        'ssl_key_file'  => '/Users/hooklife/Projects/debug-docker-php/tls/key.pem'
    ]);

    $result = $client->containerCreate([
        "AttachStdin"  => false,
        "AttachStdout" => true,
        "AttachStderr" => true,
        "Tty"          => false,
        "OpenStdin"    => false,
        "StdinOnce"    => false,
//        "Env"          => [
//            "FOO=bar",
//            "BAZ=quux"
//        ],
        "Cmd"          => [
            'apt-get update',
//            'apt-get -y install netcat-traditional ',
//            'while true; do { echo -e \'HTTP/1.1 200 OK\r\n\'; echo $(date);} | nc -l 8080; done'
        ],
        "Entrypoint"   => ["/bin/sh", "-c"],
        "Image"        => "ubuntu",
    ]);
    $id = $result['Id'];
//    var_dump($result);
    $result = $client->containerStart($id);
    var_dump($result);
    $chan = $client->containerLogs($id, [
        'stdout' => 1,
        'stderr' => 1,
        'follow' => 1
    ]);

    while ($stream = $chan->pop()){
        var_dump($stream);
    }

//    var_dump($result);
});