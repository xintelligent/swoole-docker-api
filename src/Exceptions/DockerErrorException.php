<?php

namespace Xintelligent\SwooleDockerApi\Exceptions;

use Http\Client\Exception\HttpException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class DockerErrorException extends HttpException
{


    /**
     * DockerErrorException constructor.
     * @param $message
     * @param RequestInterface $request
     * @param ResponseInterface $response
     */
    public function __construct($message, RequestInterface $request, ResponseInterface $response)
    {
        parent::__construct($message, $request, $response);
    }


    public function getJsonMessage()
    {
        return json_decode($this->response->getBody()->getContents(), true);
    }
}