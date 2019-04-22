<?php


namespace Hooklife\SwooleDockerApi\Request;


class RequestRaw
{
    public static function method($method,$endpoint)
    {
        return sprintf(
            "%s %s HTTP/1.1\r\n", $method, $endpoint
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
        [$type, $payload] = $body;
        switch (strtolower($type)) {
            case "json":
                return json_encode($payload);
            default:
                return $payload;
        }
    }

}