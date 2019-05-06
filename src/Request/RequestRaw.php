<?php


namespace MobingiLabs\SwooleDockerApi\Request;


class RequestRaw
{
    public static function method($method, $endpoint)
    {
        return sprintf(
            "%s %s HTTP/1.1", $method, $endpoint
        );
    }

    public static function header($headers)
    {
        $raw = '';

        foreach ($headers as $key => $value) {
            $raw .= sprintf("%s: %s\r\n", ucfirst($key), $value);
        }
        return $raw;
    }


    public static function body($body)
    {
        return $body;
    }

}