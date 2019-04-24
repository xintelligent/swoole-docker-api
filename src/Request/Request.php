<?php


namespace Hooklife\SwooleDockerApi\Request;

use Amp\Artax\Internal\Parser;
use Amp\Artax\ParseException;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Hooklife\SwooleDockerApi\Exception\SocketConnectException;
use Hooklife\SwooleDockerApi\Request\Exceptions\NeedMoreDataException;
use Mobingilabs\SwooleGuzzle\SwooleHandler;
use Psr\Http\Message\ResponseInterface;

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
        return $this->request('get', $endpoint, [
            'headers' => $headers,
            'query'   => $query,
        ]);
    }

    public function delete($endpoint, $query = [], $headers = [])
    {
        return $this->request('delete', $endpoint, [
            'headers' => $headers,
        ]);
    }

    public function postJson($endpoint, $params = [], $headers = [])
    {
        return $this->request('post', $endpoint, [
            'headers' => array_merge(['Accept' => "application/json"], $headers),
            'json'    => $params,
        ]);
    }


    /**
     * Make a http request.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $options http://docs.guzzlephp.org/en/latest/request-options.html
     *
     * @return mixed
     * @throws ParseException
     */
    public function request($method, $endpoint, $options = [])
    {
        $requestCreate = new RequestCreate();
        $requestCreate->setHost($this->uri['host']);
        $requestCreate->setEndpoint($endpoint);
        $requestCreate->setMethod($method);
        if (isset($options['headers'])) {
            $requestCreate->setHeaders($options['headers']);
        }
        $requestCreate->setPayload($options);
        $raw = $requestCreate->toRaw();
        $this->socket->send($raw);


        $responseBody = '';
        $parser = new Parser(function ($data) use (&$responseBody) {
            $responseBody .= $data;
        });

        while (null !== $chunk = $this->socket->recv()) {
            $parseResult = $parser->parse($chunk);
            if (!$parseResult) {
                continue;
            }
            if ($parseResult["headersOnly"]) {
                do {
                    $parseResult = $parser->parse($chunk);
                    if ($parseResult) {
                        break;
                    }
                } while (null !== $chunk = $this->socket->recv());
            }
            break;
        }

        return $this->wrapResponse($responseBody);
    }


    public function wrapResponse($responseBody)
    {
        return json_decode($responseBody, true);
    }

}