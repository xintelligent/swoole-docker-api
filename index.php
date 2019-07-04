<?php

require "vendor/autoload.php";

$client = new \Greadog\SwooleDockerApi\Client();

$logs = $client->containerCreate([
    'stdout' => 1,
    'stderr' => 1,
    'follow' => 1
]);
var_dump($logs);