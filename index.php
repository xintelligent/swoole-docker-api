<?php

require "vendor/autoload.php";

$client = new \Greadog\SwooleDockerApi\Client();

$logs = $client->containerLogs("6c74d8ec7aa5", [
    'stdout' => 1,
    'stderr' => 1,
    'follow' => 1
]);
var_dump($logs->getBody()->read(1000));