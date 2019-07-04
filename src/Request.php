<?php


namespace Xintelligent\SwooleDockerApi;

use GuzzleHttp\Psr7\Uri;
use Http\Client\Common\Plugin\ContentLengthPlugin;
use Http\Client\Common\PluginClient;
use Http\Client\Socket\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Http\Message\UriFactory\GuzzleUriFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Rize\UriTemplate;

trait Request
{

    public $socket = null;

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
     * @param array $query
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function delete($uri, $query = [])
    {
        $uri = (new GuzzleUriFactory())->createUri($this->baseUri . $uri);
        $uri = Uri::withQueryValues($uri, $query);
        $request = (new GuzzleMessageFactory())->createRequest('DELETE', $uri);
        return $this->request($request);
    }

    /**
     * @param $uri
     * @param $body
     * @param array $header
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function jsonPost($uri, $body = null, $header = [])
    {
        $uri = (new GuzzleUriFactory())->createUri($this->baseUri)->withPath($uri);
        $request = (new GuzzleMessageFactory())->createRequest(
            'POST',
            $uri,
            array_merge(['Accept' => "application/json",'Content-Type'=> "application/json"], $header),
            json_encode($body)
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
        return property_exists($this, 'baseUri') ? $this->baseUri : '';
    }

    protected function getHttpClient(array $options = [])
    {
        $contentLengthPlugin = new ContentLengthPlugin();
        return new PluginClient(
            new Client(null, $options),
            [$contentLengthPlugin]
        );
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