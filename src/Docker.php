<?php


namespace MobingiLabs\SwooleDockerApi;


use MobingiLabs\SwooleDockerApi\Request\Request;

class Docker
{
    public static function createClient($uri, $options)
    {
        $request = new Request($uri, $options);
        return new Client($request);
    }
}