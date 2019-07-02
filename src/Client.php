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
                $this->baseUri . '/containers/json{?all,size}', [
                    'all'  => $this->boolArg($all),
                    'size' => $this->boolArg($size)
                ]
            )
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $container
     * @param bool $v
     * @param bool $force
     * @return mixed
     * @throws \Http\Client\Exception
     */
    public function containerRemove($container, $v = false, $force = false)
    {
        $result = $this->delete(
            $this->uriParse->expand(
                $this->baseUri . '/containers/{container}{?v,force}',
                [
                    'container' => $container,
                    'v'         => $this->boolArg($v),
                    'force'     => $this->boolArg($force)
                ]
            )
        );
        return json_decode($result->toString(), true);
    }

    public function containerLogs($container, $config = [])
    {
        $response = $this->get(
            $this->uriParse->expand($this->baseUri . "/containers/{container}/logs?a=1", compact('container')),
            $config
        );
        return $response;
    }

    /**
     * @param $fromImage
     * @param $fromSrc
     * @param $repo
     * @param $tag
     * @param $registry
     * @return Response
     * @throws \Http\Client\Exception
     */
    public function imagePull($fromImage, $fromSrc, $repo, $tag, $registry)
    {
        /** @var Response $response */
        $response = $this->jsonPost(
            $this->uriParse->expand($this->baseUri . '/images/create{?fromImage,fromSrc,repo,tag,registry}',
                compact('fromImage', 'fromSrc', 'repo', 'tag', 'registry')
            )
        );
        return $response;
    }

    /**
     * @param string $container
     * @param array $payload {"AttachStdin":false,"AttachStdout":true,"AttachStderr":true,"DetachKeys":"ctrl-p,ctrl-q","Tty":false,"Cmd":["date"],"Env":["FOO=bar","BAZ=quux"]}
     * @return mixed
     * @throws \Http\Client\Exception
     */
    public function createExec(string $container, array $payload)
    {
        $result = $this->jsonPost(
            $this->uriParse->expand($this->baseUri . '/containers/{container}/exec', ['container' => $container]),
            $payload
        );
        return json_decode($result->toString(), true);
    }

    /**
     * @param string $execId
     * @param array $payload
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function startExec(string $execId, array $payload)
    {
        $uri = $this->uriParse->expand($this->baseUri . '/exec/{execId}/start', compact('execId'));
        if ($payload['Detach']) {
            return $this->jsonPost($uri, $payload);
        }

        $this->jsonPost($uri, $payload, [
            'Connection' => 'Upgrade',
            'Upgrade'    => 'tcp'
        ]);
        return;

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
            $this->uriParse->expand($this->baseUri . '/containers/create{?name}', compact('name')),
            $payload
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param $container
     * @param array $config
     * @return mixed
     * @throws \Http\Client\Exception
     */
    public function containerStart($container, $config = [])
    {
        $result = $this->jsonPost(
            $this->uriParse->expand(
                $this->baseUri . '/containers/{container}/start', compact('container')
            ),
            $config
        );
        return json_decode($result->toString(), true);
    }

    /**
     * @param string $container
     * @return mixed
     * @throws \Http\Client\Exception
     */
    public function attachWS(string $container)
    {
        $result = $this->jsonPost(
            $this->uriParse->expand($this->baseUri . '/containers/{container}/attach/ws', ['container' => $container])
        );
        return json_decode($result->toString(), true);
    }

    /**
     * @param array $payload
     * @return mixed
     * @throws \Http\Client\Exception
     */
    public function createVolume(array $payload)
    {
        $result = $this->jsonPost(
            $this->uriParse->expand($this->baseUri . '/volumes/create'),
            $payload
        );
        return json_decode($result->toString(), true);
    }

    /**
     * @param string $name
     * @param int $force
     * @return mixed
     * @throws \Http\Client\Exception
     */
    public function removeVolume(string $name, int $force = 0)
    {
        $result = $this->delete(
            $this->uriParse->expand($this->baseUri . '/volumes/{name}{?force}', compact('name', 'force'))
        );
        return json_decode($result->toString(), true);
    }

    public function waitContainer(string $containerID, $condition = 'not-running')
    {
        $result = $this->request->postJson(
            $this->uriParse->expand($this->baseUri . '/containers/{containerID}/wait{?condition}', compact('containerID', 'condition'))
        );
        return json_decode($result->toString(), true);
    }

    /**
     * @param string $containerID
     * @param bool $size
     * @return mixed
     * @throws \Http\Client\Exception
     */
    function inspectContainer(string $containerID, $size = false)
    {
        $result = $this->get(
            $this->uriParse->expand($this->baseUri . '/containers/{containerID}/json{?size}', compact('containerID', 'size'))
        );
        return json_decode($result->toString(), true);
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