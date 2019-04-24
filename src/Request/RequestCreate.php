<?php


namespace Hooklife\SwooleDockerApi\Request;


class RequestCreate
{
    public $headers = [];
    public $host, $method, $endpoint, $body;

    public function setHost($host)
    {
        $this->host = $host;
    }

    public function setMethod(string $method = "GET")
    {
        $this->method = strtoupper($method);
    }

    public function setEndpoint(string $endpoint)
    {
        $this->endpoint = $endpoint;
    }

    public function setPayload($options)
    {
        if (isset($options['json'])) {
            $this->headers['Content-Type'] = "application/json";
            $this->body = ['json', $options['json']];
            return true;
        }
    }

    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    protected function createBaseHeader($contentLength)
    {
        $headers = [
            "Host"           => $this->host,
            "Connection"     => 'Keep-Alive',
            'Content-Length' => $contentLength
        ];
        $this->headers = array_merge($this->headers, $headers);
    }


    public function toRaw()
    {
        $method = RequestRaw::method($this->method, $this->endpoint);
        $body = RequestRaw::body($this->body);
        // Fix Header content-length
        $this->createBaseHeader(strlen($body));
        $header = RequestRaw::header($this->headers);

        $raw =
            $method . "\r\n" .
            $header . "\r\n" .
            $body . "\r\n\r\n";
        return $raw;
    }
}