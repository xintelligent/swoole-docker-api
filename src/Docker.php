<?php


namespace MobingiLabs\SwooleDockerApi;


use GuzzleHttp\HandlerStack;
use MobingiLabs\SwooleDockerApi\Request\Request;
use Mobingilabs\SwooleGuzzle\SwooleHandler;

class Docker
{
    public static function createClient($uri, $options)
    {
        $request = new Request($uri, $options);
        return new Client($request);
    }
}