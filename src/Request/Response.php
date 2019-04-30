<?php


namespace Hooklife\SwooleDockerApi\Request;


use Swoole\Coroutine\Channel;

class Response
{
    /**
     * @var Channel $chan
     */
    public $chan;

    public function __construct($chan)
    {
        $this->chan = $chan;
    }

    public function __toString()
    {
        $data = '';
        while ($chunk = $this->chan->pop()) {
            $data .= $chunk;
        }
        return $data;
    }




}