<?php


namespace Hooklife\SwooleDockerApi\Request;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Hooklife\SwooleDockerApi\Exception\SocketConnectException;
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

    /**
     * Make a get request.
     *
     * @param string $endpoint
     * @param array $query
     * @param array $headers
     *
     * @return array
     */
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

    /**
     * Make a post request.
     *
     * @param string $endpoint
     * @param array $params
     * @param array $headers
     *
     * @return array
     */
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
     * @return array
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
        $body = $this->unwrapRecv();

        return json_decode($body, true);
    }


    protected function unwrapRecv()
    {
        $recv = $this->socket->recv();

        [$headersRaw, $body] = explode("\r\n\r\n", $recv, 2);
        $headerLines = explode("\r\n", $headersRaw);

        $headers = $this->parseHttpHeader($headerLines);
        [$protocol, $statusCode] = $this->parseProtocol($headerLines);

        // TODO Support http1.1
        if (isset($headers['Transfer-Encoding']) && 'chunked' === $headers['Transfer-Encoding']) {
            while (!$this->endsWith($body, "0\r\n\r\n")) {
                $body .= $this->socket->recv();
            };
            $body = $this->unchunkHttpResponse($body);
        }


        return $body;
    }

    function unchunkHttpResponse($chunkedRaw)
    {
        $eol = "\r\n";
        $add = strlen($eol);
        $tmp = $chunkedRaw;
        $result = '';
        do {
            $chunkedRaw = ltrim($chunkedRaw);
            // get eol
            $pos = strpos($chunkedRaw, $eol);
            if ($pos === false) {
                return false;
            }
            // get len
            $len = hexdec(substr($chunkedRaw, 0, $pos));
            if (!is_numeric($len) or $len < 0) {
                return false;
            }

            $result .= substr($chunkedRaw, ($pos + $add), $len);
            $chunkedRaw = substr($chunkedRaw, ($len + $pos + $add));
            $check = trim($chunkedRaw);
        } while (!empty($check));
        return $result;
    }


    function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public function parseHttpHeader($headerLines)
    {
        // remove "HTTP/1.1 200 OK";
        array_shift($headerLines);
        $headers = [];
        foreach ($headerLines as $header) {
            [$key, $value] = explode(": ", $header);
            $headers[$key] = $value;
        }
        return $headers;
    }

    public function parseProtocol($headerLines)
    {
        [$protocol, $statusCode] = explode(" ", array_shift($headerLines), 2);
        return [$protocol, $statusCode];
    }
}