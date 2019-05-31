<?php


namespace MobingiLabs\SwooleDockerApi;


use MobingiLabs\SwooleDockerApi\Parser\StdStreamParser;
use MobingiLabs\SwooleDockerApi\Request\Request;
use MobingiLabs\SwooleDockerApi\Request\Response;
use Rize\UriTemplate;
use Swoole\Coroutine\Channel;

class Client
{
    /**
     * @var Request
     */
    public $request;
    private $uriParse;

    public function __construct($request)
    {
        $this->request = $request;
        $this->uriParse = new UriTemplate();
    }

    public function ping()
    {
        $result = $this->request->get('/_ping');
        return json_decode($result->toString(),true);
    }

    public function info()
    {
        $result = $this->request->get('/info');
        return json_decode($result->toString(),true);
    }

    /**
     * @param bool $all
     * @param bool $size
     * @return array
     */
    public function containerList($all = false, $size = false)
    {
        $result = $this->request->get(
            $this->uriParse->expand(
                '/containers/json{?all,size}', [
                    'all'  => $this->boolArg($all),
                    'size' => $this->boolArg($size)
                ]
            )
        );
        return json_decode($result->toString(),true);
    }

    /**
     * @param array $payload
     * @param null $name
     * @return mixed
     * @link https://docs.docker.com/engine/api/v1.39/#operation/ContainerCreate
     */
    public function containerCreate(array $payload, $name = null)
    {
        $result = $this->request->postJson(
            $this->uriParse->expand('/containers/create{?name}', compact('name')),
            $payload
        );
        return json_decode($result->toString(), true);
    }


    public function containerStart($container, $config = [])
    {
        $result = $this->request->postJson(
            $this->uriParse->expand(
                '/containers/{container}/start', compact('container')
            ),
            $config
        );
        return json_decode($result->toString(), true);
    }

    public function containerLogs($container, $config = []): Channel
    {
        $response = $this->request->get(
            $this->uriParse->expand(
                '/containers/{container}/logs', compact('container')
            ),
            $config
        );
        $parser = new StdStreamParser($response);
        go(function () use ($parser) {
            $parser->start();
        });

        return $parser->chan;
    }

    /**
     * @param string $container
     * @param bool $v
     * @param bool $force
     * @return array
     */
    public function containerRemove($container, $v = false, $force = false)
    {
        $result = $this->request->delete(
            $this->uriParse->expand(
                '/containers/{container}{?v,force}',
                [
                    'container' => $container,
                    'v'         => $this->boolArg($v),
                    'force'     => $this->boolArg($force)
                ]
            )
        );
        return json_decode($result->toString(), true);
    }

    /**
     * @param string $fromImage
     * @param string $fromSrc
     * @param string $repo
     * @param string $tag
     * @param string $registry
     * @return array
     */
    public function imagePull($fromImage, $fromSrc, $repo, $tag, $registry)
    {
        /** @var Response $response */
        $response = $this->request->postJson(
            $this->uriParse->expand('/images/create{?fromImage,fromSrc,repo,tag,registry}',
                compact('fromImage', 'fromSrc', 'repo', 'tag', 'registry')
            )
        );
        return json_decode($response->toString(), true);
    }

    /**
     * Run a command inside a running container.
     * @param string $container
     * @param array $payload {"AttachStdin":false,"AttachStdout":true,"AttachStderr":true,"DetachKeys":"ctrl-p,ctrl-q","Tty":false,"Cmd":["date"],"Env":["FOO=bar","BAZ=quux"]}
     * @return array
     * @link https://docs.docker.com/engine/api/v1.39/#tag/Exec
     */
    public function createExec(string $container, array $payload)
    {
        $result = $this->request->postJson(
            $this->uriParse->expand('/containers/{container}/exec', ['container' => $container]),
            $payload
        );
        return json_decode($result->toString(), true);
    }

    public function startExec(string $execId, array $payload)
    {
        $uri = $this->uriParse->expand('/exec/{execId}/start', compact('execId'));
        if ($payload['Detach']) {
            return $this->request->postJson($uri, $payload);
        }

        $response = $this->request->postJson($uri, $payload, [
            'Connection' => 'Upgrade',
            'Upgrade'    => 'tcp'
        ]);
        return $this->request->socket;

    }

    /**
     * @param string $container
     * @return array
     */
    public function attachWS(string $container)
    {
        $result = $this->request->postJson(
            $this->uriParse->expand('/containers/{container}/attach/ws', ['container' => $container])
        );
        return json_decode($result->toString(), true);
    }

    /**
     * @param array $payload
     * @return array
     */
    public function createVolume(array $payload)
    {
        $result = $this->request->postJson(
            $this->uriParse->expand('/volumes/create'),
            $payload
        );
        return json_decode($result->toString(), true);
    }

    /**
     * @param string $name
     * @param int $force 1 or 0
     * @return Response
     */
    public function removeVolume(string $name, int $force = 0)
    {
        $result = $this->request->delete(
            $this->uriParse->expand('/volumes/{name}{?force}', compact('name', 'force'))
        );
        return json_decode($result->toString(), true);
    }

    /**
     * Block until a container stops, then returns the exit code.
     * @param string $containerID
     * @param string $condition Wait until a container state reaches the given condition,
     * either 'not-running' (default), 'next-exit', or 'removed'.
     * @return array
     */
    function waitContainer(string $containerID, $condition = 'not-running')
    {
        $result = $this->request->postJson(
            $this->uriParse->expand('/containers/{containerID}/wait{?condition}', compact('containerID', 'condition'))
        );
        return json_encode($result->toString(), true);
    }

    /**
     * Return low-level information about a container.
     * @param string $containerID
     * @param bool $size Return the size of container as fields SizeRw and SizeRootFs
     * @return array
     */
    function inspectContainer(string $containerID, $size = false)
    {
        $result = $this->request->get(
            $this->uriParse->expand('/containers/{containerID}/json{?size}', compact('containerID', 'size'))
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