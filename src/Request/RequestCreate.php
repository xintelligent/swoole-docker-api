<?php


namespace MobingiLabs\SwooleDockerApi\Request;


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

    public function setBody($body = '')
    {
        $this->body = $body;
    }

    public function setHeaders(array $headers)
    {
        $this->headers = array_merge($this->headers, $headers);
    }

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    protected function createBaseHeader($contentLength = null)
    {
        $headers = [
            "Host" => $this->host,
        ];
        if (null !== $contentLength) {
            $this->headers['Content-Length'] = $contentLength;
        }
        if (!isset($this->headers['Connection'])) {
//            $this->headers["Connection"] = 'Keep-Alive';
        }

        $this->headers = array_merge($this->headers, $headers);
    }

    public function setOption($method, $endpoint, $options)
    {
        $parsedUrl = parse_url($endpoint);
        $this->setMethod($method);
        // query parse
        if (isset($options['query'])) {
            if (isset($parsedUrl['query'])) {
                $endpoint .= '&' . http_build_query($options['query']);
            } else {
                $endpoint .= '?' . http_build_query($options['query']);
            }
        }
        $this->setEndpoint($endpoint);
        if (isset($options['headers'])) {
            $this->setHeaders($options['headers']);
        }
        if (isset($options['json']) && $options['json']) {
            $this->headers['Content-Type'] = "application/json";
            $this->setBody(json_encode($options['json']));
        }
    }

    public function toRaw()
    {
        $method = RequestRaw::method($this->method, $this->endpoint);
        $body = RequestRaw::body($this->body);
        // Fix Header content-length
        $this->createBaseHeader($this->method === 'GET' ? null : strlen($body));
        $header = RequestRaw::header($this->headers);

        $raw =
            $method . "\r\n" .
            $header . "\r\n" .
            $body;
        return $raw;
    }
}