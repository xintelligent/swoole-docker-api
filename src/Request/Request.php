<?php


namespace MobingiLabs\SwooleDockerApi\Request;

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
        $socket = $this->getSocket();
        $socket->send($raw);

        $responseChan = new Channel();
        $parser = new Parser(function ($data) use ($responseChan) {
            $responseChan->push(["type" => 1, "data" => $data]);
        });

        go(function () use ($parser, $responseChan, $socket) {
            while (null !== $chunk = $socket->recv()) {
                $parseResult = $parser->parse($chunk);
                if (!$parseResult) {
                    continue;
                }
                if ($parseResult["headersOnly"]) {
                    if ($parseResult['status'] > 201) {
                        if ($parseResult['status'] > 400) {
                            $responseChan->push(['type' => 0, 'data' => [ServerException::class, $parseResult]]);
                        } else {
                            $responseChan->push(['type' => 0, 'data' => [BadResponseException::class, $parseResult]]);
                        }
                        break;
                    }

                    $chunk = null;
                    do {
                        $parseResult = $parser->parse($chunk);
                        if ($parseResult) {
                            break;
                        }
                    } while (null !== $chunk = $socket->recv());
                }
                break;
            }
            $socket->close();
            $responseChan->close();
        });
        return new Response($responseChan);
    }


    public function getSocket(): Client
    {
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