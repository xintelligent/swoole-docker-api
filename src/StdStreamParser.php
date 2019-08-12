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

    /** @deprecated  $size */
    public function read($size = null)
    {
        if (null === $this->size) {
            $chunk = $this->streamInterface->read(8);
            $this->parseSize($chunk);
        }
        return $this->streamInterface->read($this->size);
    }


    public function parseSize($chunk)
    {
        // remove first 8 char
        $decoded = unpack('C1type/C3/N1size', $chunk);
        $this->size = $decoded['size'] - 8;
        $this->type = $decoded['type'];
    }
}