<?php

namespace Greadog\SwooleDockerApi;

use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\HandlerStack;
use Http\Message\MessageFactory\DiactorosMessageFactory;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Psr\Http\Message\ResponseInterface;
use Rize\UriTemplate\UriTemplate;

class Client
{
    use Request;

    protected $baseUri = "http://161.117.84.76:9588";

    /**
     * @var UriTemplate
     */
    private $uriParse;

    public function __construct()
    {
        $this->uriParse = new UriTemplate();
    }

    public function ping()
    {
        $response = $this->get('/_ping');
        return $response->getBody()->getContents();
    }

    public function info()
    {
        $response = $this->get('/info');
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param bool $all
     * @param bool $size
     * @return array
     * @throws \Http\Client\Exception
     */
    public function containerList($all = false, $size = false)
    {
        $response = $this->get(
            $this->uriParse->expand(
                '/containers/json{?all,size}', [
                    'all'  => $this->boolArg($all),
                    'size' => $this->boolArg($size)
                ]
            )
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    public function containerLogs($container, $config = [])
    {
        $response = $this->get(
            $this->uriParse->expand("/containers/{container}/logs?a=1", compact('container')),
            $config
        );
        return $response;
    }

    /**
     * @param array $payload
     * @param null $name
     * @return mixed
     * @throws \Http\Client\Exception
     * @link https://docs.docker.com/engine/api/v1.39/#operation/ContainerCreate
     */
    public function containerCreate(array $payload, $name = null)
    {
        $response = $this->jsonPost(
            $this->uriParse->expand('/containers/create{?name}', compact('name')),
            $payload
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $value
     * @return int|null
     */
    private function boolArg($value)
    {
        return ($value ? 1 : null);
    }
}