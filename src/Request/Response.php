<?php


namespace MobingiLabs\SwooleDockerApi\Request;

class Response
{
    public $responseChan;

    protected $data = '';
    protected $finish = false;

    public function __construct($responseChan)
    {
        $this->responseChan = $responseChan;
    }

    public function recv()
    {
        $chunk = $this->responseChan->pop();
        if (!$chunk) {
            $this->finish = true;
        }

        if ($this->finish) {
            return false;
        }

        if ($chunk["type"] = 0) {
            throw new $chunk['data'][0]($chunk['data'][1]);
        }

        if ($chunk['type'] = 1) {
            $this->data .= $chunk['data'];
            return $chunk['data'];
        }
    }

    public function __toString()
    {
        if(!$this->finish){
            while ($this->recv()) ;
        }
        return $this->data;
    }


}