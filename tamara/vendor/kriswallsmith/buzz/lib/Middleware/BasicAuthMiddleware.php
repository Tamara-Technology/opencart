<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware;

use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
class BasicAuthMiddleware implements \TMS\Buzz\Middleware\MiddlewareInterface
{
    private $username;
    private $password;
    public function __construct(string $username, string $password)
    {
        $this->username = $username;
        $this->password = $password;
    }
    public function handleRequest(\TMS\Psr\Http\Message\RequestInterface $request, callable $next)
    {
        $request = $request->withAddedHeader('Authorization', \sprintf('Basic %s', \base64_encode($this->username . ':' . $this->password)));
        return $next($request);
    }
    public function handleResponse(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response, callable $next)
    {
        return $next($request, $response);
    }
}
