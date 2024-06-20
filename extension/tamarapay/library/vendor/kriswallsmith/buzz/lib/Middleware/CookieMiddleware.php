<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware;

use TMS\Buzz\Middleware\Cookie\Cookie;
use TMS\Buzz\Middleware\Cookie\CookieJar;
use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
class CookieMiddleware implements \TMS\Buzz\Middleware\MiddlewareInterface
{
    private $cookieJar;
    public function __construct()
    {
        $this->cookieJar = new \TMS\Buzz\Middleware\Cookie\CookieJar();
    }
    public function setCookies(array $cookies) : void
    {
        $this->cookieJar->setCookies($cookies);
    }
    /**
     * @return Cookie[]
     */
    public function getCookies() : array
    {
        return $this->cookieJar->getCookies();
    }
    /**
     * Adds a cookie to the current cookie jar.
     *
     * @param Cookie $cookie A cookie object
     */
    public function addCookie(\TMS\Buzz\Middleware\Cookie\Cookie $cookie) : void
    {
        $this->cookieJar->addCookie($cookie);
    }
    public function handleRequest(\TMS\Psr\Http\Message\RequestInterface $request, callable $next)
    {
        $this->cookieJar->clearExpiredCookies();
        $request = $this->cookieJar->addCookieHeaders($request);
        return $next($request);
    }
    public function handleResponse(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response, callable $next)
    {
        $this->cookieJar->processSetCookieHeaders($request, $response);
        return $next($request, $response);
    }
}
