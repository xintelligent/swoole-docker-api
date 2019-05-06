<?php


namespace MobingiLabs\SwooleDockerApi\Exception;


use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Client;
use Throwable;

/**
 * Exception when an HTTP error occurs (4xx or 5xx error)
 */
class BadResponseException extends \Exception
{

    private $result;

    public function __construct($result)
    {
        $this->result = $result;
        parent::__construct();
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    public function getStatus()
    {
        return $this->result['status'];
    }
}