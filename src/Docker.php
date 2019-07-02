<?php

namespace Greadog\SwooleDockerApi;

use Greadog\SwooleDockerApi\Client;

class Docker
{
    public static function createClient($uri, $options)
    {

        $config = ['timeout' => 5];
        $client = new Client();
        return $client;
    }
}