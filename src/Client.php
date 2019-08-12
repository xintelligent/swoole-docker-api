<?php

namespace Xintelligent\SwooleDockerApi;

use Psr\Http\Message\ResponseInterface;
use Rize\UriTemplate\UriTemplate;

class Client
{
    use Request;

    protected $baseUri;

    /**
     * @var UriTemplate
     */
    private $uriParse;

    public function __construct($baseUri)
    {
        $this->uriParse = new UriTemplate();
        $this->baseUri = $baseUri;
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
            $this->uriParse->expand('/containers/json{?all,size}', [
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
            $this->uriParse->expand('/containers/{container}{?v,force}',
                [
                    'container' => $container,
                    'v'         => $this->boolArg($v),
                    'force'     => $this->boolArg($force)
                ]
            )
        );
        return json_decode($result->getBody()->getContents(), true);
    }

    public function containerLogs($container, $config = [])
    {
        $response = $this->get(
            $this->uriParse->expand("/containers/{container}/logs?a=1", compact('container')),
            $config
        );
        return new StdStreamParser($response->getBody());
    }

    /**
     * @param $fromImage
     * @param $fromSrc
     * @param $repo
     * @param $tag
     * @param $registry
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function imagePull($fromImage, $fromSrc, $repo, $tag, $registry)
    {

        $response = $this->post(
            $this->uriParse->expand('/images/create{?fromImage,fromSrc,repo,tag,registry}',
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
        $response = $this->jsonPost(
            $this->uriParse->expand('/containers/{container}/exec', ['container' => $container]),
            $payload
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $execId
     * @param array $payload
     * @return ResponseInterface
     * @throws \Http\Client\Exception
     */
    public function startExec(string $execId, array $payload)
    {
        $uri = $this->uriParse->expand('/exec/{execId}/start', compact('execId'));
        if ($payload['Detach']) {
            return $this->jsonPost($uri, $payload);
        }

        return $this->jsonPost($uri, $payload, [
            'Connection' => 'Upgrade',
            'Upgrade'    => 'tcp'
        ]);
    }

    /**
     * @param string $execId
     * @param int $h
     * @param int $w
     * @return ResponseInterface
     */
    public function resizeExec(string $execId, $h = 25, $w = 80)
    {
        $uri = $this->uriParse->expand(
            '/exec/{execId}/resize{?h,w}',compact('execId','h','w')
        );
        return $this->jsonPost($uri);
    }

    /**
     * @param $execId
     * @return mixed
     */
    public function inspectExec($execId)
    {
        $response = $this->get(
            $this->uriParse->expand('/exec/{execId}/json', compact('execId'))
        );
        return json_decode($response->getBody()->getContents(), true);
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
     * @param $container
     * @param array $config
     * @return mixed
     * @throws \Http\Client\Exception
     */
    public function containerStart($container, $config = [])
    {
        $response = $this->jsonPost(
            $this->uriParse->expand('/containers/{container}/start', compact('container')),
            $config
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $container
     * @return mixed
     * @throws \Http\Client\Exception
     */
    public function attachWS(string $container)
    {
        $response = $this->jsonPost(
            $this->uriParse->expand('/containers/{container}/attach/ws', ['container' => $container])
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param array $payload
     * @return mixed
     * @throws \Http\Client\Exception
     */
    public function createVolume(array $payload)
    {
        $response = $this->jsonPost(
            $this->uriParse->expand('/volumes/create'),
            $payload
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $name
     * @param int $force
     * @return mixed
     * @throws \Http\Client\Exception
     */
    public function removeVolume(string $name, int $force = 0)
    {
        $response = $this->delete(
            $this->uriParse->expand('/volumes/{name}{?force}', compact('name', 'force'))
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    public function waitContainer(string $containerID, $condition = 'not-running')
    {
        $response = $this->jsonPost(
            $this->uriParse->expand('/containers/{containerID}/wait{?condition}', compact('containerID', 'condition'))
        );
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @param string $containerID
     * @param bool $size
     * @return mixed
     * @throws \Http\Client\Exception
     */
    function inspectContainer(string $containerID, $size = false)
    {
        $response = $this->get(
            $this->uriParse->expand('/containers/{containerID}/json{?size}', compact('containerID', 'size'))
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