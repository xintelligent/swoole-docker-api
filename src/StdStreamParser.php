<?php


namespace Xintelligent\SwooleDockerApi;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class StdStreamParser
{

    const STD_ID = 0;
    const STD_OUT = 1;
    const STD_ERR = 2;
    protected $size, $type = null;
    /**
     * @var string
     */
    private $stream;
    /**
     * @var StreamInterface
     */
    private $streamInterface;


    public function __construct(StreamInterface $stream)
    {
        $this->streamInterface = $stream;
    }

    public function read($size = 1024)
    {
        $chunk = $this->streamInterface->read($size);


        $this->stream .= $chunk;
        if ($this->stream == '' || strlen($this->stream) < 8) {
            $this->streamInterface->close();
            return false;
        }
        if (null == $this->size) {
            $header = substr($this->stream, 0, 8);
            $decoded = \unpack('C1type/C3/N1size', $header);
            $this->size = $decoded['size'];
            $this->type = $decoded['type'];
        }

        return $this->shiftData();
    }

    public function shiftData()
    {
        if (strlen($this->stream) < ($this->size + 8)) {
            return true;
        }
        $data = [
            'data' => (substr($this->stream, 8, $this->size)),
            'type' => $this->type
        ];
        $this->stream = substr($this->stream, $this->size + 8);
        $this->size = null;
        $this->type = null;
        return $data;
    }
}