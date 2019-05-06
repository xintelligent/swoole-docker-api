<?php


namespace MobingiLabs\SwooleDockerApi\Parser;

use MobingiLabs\SwooleDockerApi\Request\Response;
use Swoole\Coroutine;

/**
 * Class StreamResultParse
 * @package MobingiLabs\SwooleDockerApi\Parser
 * @link https://docs.docker.com/engine/api/v1.39/#operation/ContainerAttach
 */
class StdStreamParser
{

    const STD_ID = 0;
    const STD_OUT = 1;
    const STD_ERR = 2;

    protected $size, $type = null;
    protected $parseNeedMoreData = false;


    private $response;
    public $chan;
    /**
     * @var string
     */
    private $stream;

    public function __construct(Response $response)
    {
        $this->chan = new Coroutine\Channel();
        $this->response = $response;
    }

    public function start()
    {
        while ($data = $this->response->chan->pop()) {
            $this->parse($data);
        }
        $this->chan->close();
    }

    public function parse($stream)
    {
        $this->stream .= $stream;

        if ($this->parseNeedMoreData) {
            $this->shiftData();
            return true;
        }

        if ($this->stream == '' || strlen($this->stream) < 8) {
            return false;
        }
        $header = substr($this->stream, 0, 8);
        $decoded = \unpack('C1type/C3/N1size', $header);
        $this->size = $decoded['size'];
        $this->type = $decoded['type'];
        $this->shiftData();
    }

    public function shiftData()
    {
        if (strlen($this->stream) < ($this->size + 8)) {
            $this->parseNeedMoreData = true;
        }
        $this->parseNeedMoreData = false;
        $this->chan->push([
            'data' => (substr($this->stream, 8, $this->size)),
            'type' => $this->type
        ]);
        $this->stream = substr($this->stream, $this->size + 8);
    }

}