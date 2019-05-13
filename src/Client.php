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
        return $this->request->get('/_ping');
    }

    public function info()
    {
        return $this->request->get('/info');
    }

    public function containerList($all = false, $size = false)
    {
        return $this->request->get(
            $this->uriParse->expand(
                '/containers/json{?all,size}', [
                    'all'  => $this->boolArg($all),
                    'size' => $this->boolArg($size)
                ]
            )
        );
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


    public function containerRemove($container, $v = false, $force = false)
    {
        return $this->request->delete(
            $this->uriParse->expand(
                '/containers/{container}{?v,force}',
                [
                    'container' => $container,
                    'v'         => $this->boolArg($v),
                    'force'     => $this->boolArg($force)
                ]
            )
        );
    }

    /**
     * @param $image
     * @return Response
     * https://docs.docker.com/engine/api/v1.39/#operation/BuildPrune
     */
    public function imagePull($fromImage, $fromSrc, $repo, $tag, $registry)
    {
        /** @var Response $response */
        $response = $this->request->postJson(
            $this->uriParse->expand('/images/create{?fromImage,fromSrc,repo,tag,registry}',
                compact('fromImage', 'fromSrc', 'repo', 'tag', 'registry')
            )
        );
        return $response;
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
        return $this->request->postJson(
            $this->uriParse->expand('/containers/{container}/exec', ['container' => $container]),
            $payload
        );
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


    public function attachWS(string $container)
    {
        return $this->request->postJson(
            $this->uriParse->expand('/containers/{container}/attach/ws', ['container' => $container])
        );
    }


    private function boolArg($value)
    {
        return ($value ? 1 : null);
    }
}