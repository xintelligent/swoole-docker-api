<?php


namespace MobingiLabs\SwooleDockerApi\Exception;


use Throwable;

class SocketConnectException extends \RuntimeException
{

    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}