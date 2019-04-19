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

    protected function createBaseHeader()
    {
        $headers = [
            "Host"       => $this->host,
            "Connection" => 'Keep-Alive'
        ];
        $this->headers = array_merge($this->headers, $headers);
    }


    public function toRaw()
    {
        $this->createBaseHeader();
        $raw = RequestRaw::method($this->method, $this->endpoint) .
            RequestRaw::header($this->headers) .
            RequestRaw::body($this->body) .
            "\r\n";
        return $raw;
    }
}