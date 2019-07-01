<?php

namespace Greadog\SwooleDockerApi;

use GuzzleHttp\Client as GuzzleClient;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Http\Client\Socket\Client;

class Docker
{
    public static function createClient($uri, $options)
    {

        $config = ['timeout' => 5];
        $client = new Client();
        $adapter = new GuzzleAdapter($client);
    }
}