<?php


namespace mobingilabs\SwooleDockerApi\Exception;


use Swoole\Coroutine\Channel;
use Throwable;

/**
 * Exception when an HTTP error occurs (4xx or 5xx error)
 */
class BadResponseException extends \RuntimeException
{

    /**
     * @var Channel
     */
    private $chan;
    private $result;

    public function __construct(Channel $chan, $result)
    {
        $this->chan = $chan;
        $this->result = $result;

        $chan->close();

        parent::__construct();
    }




}