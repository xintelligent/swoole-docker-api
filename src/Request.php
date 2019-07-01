<?php


namespace Greadog\SwooleDockerApi;

use GuzzleHttp\Psr7\Uri;
use Http\Client\Socket\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Message\UriFactory\GuzzleUriFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rize\UriTemplate;

trait Request
{

    /**
     * @param $uri
     * @param array $query
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function get($uri, $query = [])
    {
        $uri = (new GuzzleUriFactory())->createUri($this->baseUri . $uri);
        $uri = Uri::withQueryValues($uri, $query);
        $request = (new GuzzleMessageFactory())->createRequest('GET', $uri);
        return $this->request($request);
    }

    /**
     * @param $uri
     * @param $body
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function jsonPost($uri, $body)
    {
        $request = (new GuzzleMessageFactory())->createRequest(
            'POST',
            $uri,
            ['Accept' => "application/json"],
            $body
        );
        return $this->request($request);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    protected function request(RequestInterface $request)
    {

        return $this->unwrapResponse(
            $this->getHttpClient($this->getBaseOptions())->sendRequest($request)
        );
    }

    /**
     * Return base Guzzle options.
     *
     * @return array
     */
    protected function getBaseOptions(): array
    {
        return [
        ];
    }

    protected function getBaseUri(): string
    {
        return property_exists($this, 'baseUrl') ? $this->baseUrl : '';
    }

    protected function getHttpClient(array $options = [])
    {
        return new Client(null, $options);
    }

    protected function getUriParse()
    {
        return new UriTemplate();
    }

    /**
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function unwrapResponse(ResponseInterface $response): ResponseInterface
    {
        return $response;
    }
}