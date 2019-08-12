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
     * @var StreamInterface
     */
    private $streamInterface;


    public function __construct(StreamInterface $stream)
    {
        $this->streamInterface = $stream;
    }


    public function read()
    {
        if (null === $this->size) {
            $chunk = $this->streamInterface->read(8);
            if (!$chunk) {
                return false;
            }
            $this->parseSize($chunk);
        }
        $chunk = $this->streamInterface->read($this->size);
        $this->size = null;
        return [
            'data' => $chunk,
            'type' => $this->type
        ];
    }


    public function parseSize($chunk)
    {
        // remove first 8 char
        $decoded = unpack('C1type/C3/N1size', $chunk);
        $this->size = $decoded['size'];
        $this->type = $decoded['type'];
    }
}