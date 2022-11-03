<?php

declare (strict_types=1);
namespace TMS\Tamara\Request;

use TMS\Tamara\Exception\RequestDispatcherException;
use TMS\Tamara\HttpClient\HttpClient;
use TMS\Tamara\Response\ClientResponse;
class RequestDispatcher
{
    /**
     * @var HttpClient
     */
    private $httpClient;
    public function __construct(\TMS\Tamara\HttpClient\HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }
    /**
     * @param object $request
     *
     * @return mixed
     *
     * @throws RequestDispatcherException
     */
    public function dispatch($request)
    {
        $requestClass = \get_class($request);
        $handlerClass = $requestClass . 'Handler';
        if (!\class_exists($handlerClass)) {
            throw new \TMS\Tamara\Exception\RequestDispatcherException(\sprintf('Missing handler for this request, please add %s', $handlerClass));
        }
        $handler = new $handlerClass($this->httpClient);
        $response = $handler($request);
        if (!$response instanceof \TMS\Tamara\Response\ClientResponse) {
            throw new \TMS\Tamara\Exception\RequestDispatcherException(\sprintf('The response of the %s::__invoke must be type of %s', $handlerClass, \TMS\Tamara\Response\ClientResponse::class));
        }
        return $response;
    }
}
