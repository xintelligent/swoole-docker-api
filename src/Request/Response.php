<?php


namespace MobingiLabs\SwooleDockerApi\Request;


use Swoole\Coroutine\Channel;

class Response
{
    private $exceptionChan;
    private $responseChan;

    public function __construct($exceptionChan, $responseChan)
    {
        $this->exceptionChan = $exceptionChan;
        $this->responseChan = $responseChan;


        $exception = $this->exceptionChan->pop();
        if ($exception){
            throw new $exception[0]($exception[1]);
        }
    }


    public function __toString()
    {
        $data = '';
        while ($chunk = $this->responseChan->pop()) {
            $data .= $chunk;
        }
        return $data;
    }




}