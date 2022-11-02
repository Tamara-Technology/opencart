<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware;

use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
class ContentLengthMiddleware implements \TMS\Buzz\Middleware\MiddlewareInterface
{
    public function handleRequest(\TMS\Psr\Http\Message\RequestInterface $request, callable $next)
    {
        $body = $request->getBody();
        if (!$request->hasHeader('Content-Length')) {
            $request = $request->withAddedHeader('Content-Length', (string) $body->getSize());
        }
        return $next($request);
    }
    public function handleResponse(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response, callable $next)
    {
        return $next($request, $response);
    }
}
