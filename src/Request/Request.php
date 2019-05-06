<?php


namespace Hooklife\SwooleDockerApi\Request;

use Amp\Artax\ParseException;
use Hooklife\SwooleDockerApi\Exception\SocketConnectException;
use Hooklife\SwooleDockerApi\Parser\Parser;
use mobingilabs\SwooleDockerApi\Exception\BadResponseException;
use mobingilabs\SwooleDockerApi\Exception\ServerException;
use Swoole\Coroutine\Channel;

/**
 * Trait HasHttpRequest.
 */
class Request
{
    public $options = [];
    public $uri = [];
    public $socket;
    public $requestRaw;


    public function __construct($uri, array $options)
    {
        $this->options = $options;
        $this->uri = parse_url($uri);


        $this->socket = new \Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        $this->socket->set($this->options);
        if (!$this->socket->connect($this->uri['host'], $this->uri['port'], 200)) {
            throw new SocketConnectException("connect failed. Error: {$this->socket->errCode}\n");
        }

        // enable ssl
        if (isset($this->options['ssl_cert_file']) || isset($this->options['ssl_key_file'])) {
            $this->socket->enableSSL();
        }

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

        $this->socket->send($raw);

        $responseChan = new Channel();
        $parser = new Parser(function ($data) use ($responseChan) {
            $responseChan->push($data);
        });
        go(function () use ($parser, $responseChan) {
            while (null !== $chunk = $this->socket->recv()) {
                $parseResult = $parser->parse($chunk);
                if (!$parseResult) {
                    continue;
                }
                if ($parseResult["headersOnly"]) {
                    if ($parseResult['status'] > 201) {
                        if ($parseResult['status'] > 400) {
                            throw new ServerException($responseChan, $parseResult);
                        }
                        throw new BadResponseException($responseChan, $parseResult);
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
            $responseChan->close();
        });
        return new Response($responseChan);
    }


}