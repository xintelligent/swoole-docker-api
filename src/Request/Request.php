<?php


namespace MobingiLabs\SwooleDockerApi\Request;

use Amp\Artax\ParseException;
use MobingiLabs\SwooleDockerApi\Exception\SocketConnectException;
use MobingiLabs\SwooleDockerApi\Parser\Parser;
use MobingiLabs\SwooleDockerApi\Exception\BadResponseException;
use MobingiLabs\SwooleDockerApi\Exception\ServerException;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Client;

/**
 * Trait HasHttpRequest.
 */
class Request
{
    public $options = [];
    public $uri = [];
    public $socket = null;
    public $requestRaw;


    public function __construct($uri, array $options)
    {
        $this->options = $options;
        $this->uri = parse_url($uri);


    }

    public function get($endpoint, $query = [], $headers = [])
    {
        return $this->doRequest('get', $endpoint, [
            'headers' => $headers,
            'query'   => $query,
        ]);
    }

    public function delete($endpoint, $query = [], $headers = [])
    {
        return $this->doRequest('delete', $endpoint, [
            'headers' => $headers,
        ]);
    }

    public function postJson($endpoint, $params = [], $headers = [])
    {
        return $this->doRequest('post', $endpoint, [
            'headers' => array_merge(['Accept' => "application/json"], $headers),
            'json'    => $params,
        ]);
    }

    public function postHijack($endpoint, $params = [], $headers = [])
    {
        return $this->doRequest('post', $endpoint, [
            'headers' => array_merge(['Accept' => "application/json"], $headers),
            'json'    => $params,
        ]);
    }


    public function doRequest($method, $endpoint, $options = [])
    {

        $requestCreate = new RequestCreate();
        $requestCreate->setOption($method, $endpoint, $options);
        $requestCreate->setHost($this->uri['host']);
        $raw = $requestCreate->toRaw();

        $this->getSocket()->send($raw);

        $responseChan = new Channel();
        $parser = new Parser(function ($data) use ($responseChan) {
            $responseChan->push($data);
        });
        $exceptionChan = new Channel();
        go(function () use ($parser, $responseChan, $exceptionChan) {
            while (null !== $chunk = $this->getSocket()->recv()) {
                $parseResult = $parser->parse($chunk);
                if (!$parseResult) {
                    continue;
                }
                if ($parseResult["headersOnly"]) {
                    if ($parseResult['status'] > 201) {
                        $this->closeSocket();
                        if ($parseResult['status'] > 400) {
                            $exceptionChan->push([ServerException::class,$parseResult]);
                        }else{
                            $exceptionChan->push([BadResponseException::class,$parseResult]);
                        }
                        $responseChan->close();
                        $exceptionChan->close();
                        break;
                    }

                    $chunk = null;
                    do {
                        $parseResult = $parser->parse($chunk);
                        if ($parseResult) {
                            break;
                        }
                    } while (null !== $chunk = $this->socket->recv());
                }
                break;
            }
            $exceptionChan->close();
            $responseChan->close();
        });
        return new Response($exceptionChan, $responseChan);
    }

    public function closeSocket()
    {
        $this->socket->close();
        $this->socket = null;
    }


    public function getSocket(): Client
    {
        if ($this->socket !== null) {
            return $this->socket;
        }

        $this->socket = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        $this->socket->set($this->options);
        if (!$this->socket->connect($this->uri['host'], $this->uri['port'], 200)) {
            throw new SocketConnectException("connect failed. Error: {$this->socket->errCode}\n");
        }

        // enable ssl
        if (isset($this->options['ssl_cert_file']) || isset($this->options['ssl_key_file'])) {
            $this->socket->enableSSL();
        }
        return $this->socket;
    }
}