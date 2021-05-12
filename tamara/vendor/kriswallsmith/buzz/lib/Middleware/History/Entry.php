<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware\History;

use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
class Entry
{
    private $request;
    private $response;
    private $duration;
    /**
     * @param float|null $duration The duration in seconds
     */
    public function __construct(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response, float $duration = null)
    {
        $this->request = $request;
        $this->response = $response;
        $this->duration = $duration;
    }
    public function getRequest() : \TMS\Psr\Http\Message\RequestInterface
    {
        return $this->request;
    }
    public function getResponse() : \TMS\Psr\Http\Message\ResponseInterface
    {
        return $this->response;
    }
    public function getDuration() : ?float
    {
        return $this->duration;
    }
}
