<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware;

use TMS\Buzz\Exception\InvalidArgumentException;
use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
class BearerAuthMiddleware implements \TMS\Buzz\Middleware\MiddlewareInterface
{
    private $accessToken;
    public function __construct(string $accessToken)
    {
        if (empty($accessToken)) {
            throw new \TMS\Buzz\Exception\InvalidArgumentException('You must supply a non empty accessToken');
        }
        $this->accessToken = $accessToken;
    }
    public function handleRequest(\TMS\Psr\Http\Message\RequestInterface $request, callable $next)
    {
        $request = $request->withAddedHeader('Authorization', \sprintf('Bearer %s', $this->accessToken));
        return $next($request);
    }
    public function handleResponse(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response, callable $next)
    {
        return $next($request, $response);
    }
}
