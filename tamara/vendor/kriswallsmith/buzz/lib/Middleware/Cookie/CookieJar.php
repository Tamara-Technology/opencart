<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware\Cookie;

use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
class CookieJar
{
    /** @var Cookie[] */
    private $cookies = [];
    public function clear() : void
    {
        $this->cookies = [];
    }
    public function setCookies(array $cookies) : void
    {
        $this->cookies = [];
        foreach ($cookies as $cookie) {
            $this->addCookie($cookie);
        }
    }
    /**
     * @return Cookie[]
     */
    public function getCookies() : array
    {
        return $this->cookies;
    }
    /**
     * Adds a cookie to the current cookie jar.
     */
    public function addCookie(\TMS\Buzz\Middleware\Cookie\Cookie $cookie) : void
    {
        $this->cookies[$this->getHash($cookie)] = $cookie;
    }
    /**
     * Adds Cookie headers to the supplied request.
     */
    public function addCookieHeaders(\TMS\Psr\Http\Message\RequestInterface $request) : \TMS\Psr\Http\Message\RequestInterface
    {
        $cookies = [];
        foreach ($this->getCookies() as $cookie) {
            if ($cookie->matchesRequest($request)) {
                $cookies[] = $cookie->toCookieHeader();
            }
        }
        if ($cookies) {
            $request = $request->withAddedHeader('Cookie', \implode('; ', $cookies));
        }
        return $request;
    }
    /**
     * Processes Set-Cookie headers from a request/response pair.
     */
    public function processSetCookieHeaders(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response) : void
    {
        $host = $request->getUri()->getHost();
        foreach ($response->getHeader('Set-Cookie') as $header) {
            $cookie = new \TMS\Buzz\Middleware\Cookie\Cookie();
            $cookie->fromSetCookieHeader($header, $host);
            $this->addCookie($cookie);
        }
    }
    /**
     * Removes expired cookies.
     */
    public function clearExpiredCookies() : void
    {
        $cookies = $this->getCookies();
        foreach ($cookies as $i => $cookie) {
            if ($cookie->isExpired()) {
                unset($cookies[$i]);
            }
        }
        $this->clear();
        $this->setCookies(\array_values($cookies));
    }
    /**
     * Create an unique identifier for the cookie. Two cookies with the same identifier
     * may have different values.
     */
    private function getHash(\TMS\Buzz\Middleware\Cookie\Cookie $cookie) : string
    {
        return \sha1(\sprintf('%s|%s|%s', $cookie->getName(), $cookie->getAttribute(\TMS\Buzz\Middleware\Cookie\Cookie::ATTR_DOMAIN), $cookie->getAttribute(\TMS\Buzz\Middleware\Cookie\Cookie::ATTR_PATH)));
    }
}
