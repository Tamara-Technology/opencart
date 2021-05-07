<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware;

use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
class WsseAuthMiddleware implements \TMS\Buzz\Middleware\MiddlewareInterface
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
        $nonce = \substr(\sha1(\uniqid('', \true)), 0, 16);
        $created = \date('c');
        $digest = \base64_encode(\sha1(\base64_decode($nonce) . $created . $this->password, \true));
        $wsse = \sprintf('UsernameToken Username="%s", PasswordDigest="%s", Nonce="%s", Created="%s"', $this->username, $digest, $nonce, $created);
        $request = $request->withHeader('Authorization', 'WSSE profile="UsernameToken"')->withHeader('X-WSSE', $wsse);
        return $next($request);
    }
    public function handleResponse(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response, callable $next)
    {
        return $next($request, $response);
    }
}
