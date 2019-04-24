<?php


namespace Hooklife\SwooleDockerApi;


use Hooklife\SwooleDockerApi\Request\Request;
use Hooklife\SwooleDockerApi\Request\RequestInterface;
use React\Promise\PromiseInterface;
use Rize\UriTemplate;

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


    public function containerStart($container, $config = [])
    {
        return $this->request->postJson(
            $this->uriParse->expand(
                '/containers/{container}/start',
                ['container' => $container]
            ),
            $config
        );
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