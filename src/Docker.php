<?php

namespace Xintelligent\SwooleDockerApi;

class Docker
{
    public static function createClient($uri, $options)
    {
        return new Client($uri);
    }
}