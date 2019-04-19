<?php


namespace Hooklife\SwooleDockerApi;


use GuzzleHttp\HandlerStack;
use Hooklife\SwooleDockerApi\Request\Request;
use Mobingilabs\SwooleGuzzle\SwooleHandler;

class Docker
{
    public static function createClient($options)
    {
        $request = new Request();
        $client->set([
            'ssl_cert_file' => '/Users/hooklife/Projects/debug-docker-php/tls/cert.pem',
            'ssl_key_file'  => '/Users/hooklife/Projects/debug-docker-php/tls/key.pem'
        ]);
        $request->options = $options;
        return new Client($request);
    }
}