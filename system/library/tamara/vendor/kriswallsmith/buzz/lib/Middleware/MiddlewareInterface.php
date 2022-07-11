<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware;

use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
/**
 * A middleware gets called twice per request. One time before we send the request
 * and once after the response is received. A middleware may modify/change the
 * request and the response. Just be aware that they are immutable.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface MiddlewareInterface
{
    /**
     * Handle a request.
     *
     * End this function by calling:
     *   <code>
     *      return $next($request);
     *   </code
     *
     * @param callable $next next middleware
     */
    public function handleRequest(\TMS\Psr\Http\Message\RequestInterface $request, callable $next);
    /**
     * Handle a response.
     *
     * End this function by calling:
     *   <code>
     *      return $next($request, $response);
     *   </code
     *
     * @param callable $next next middleware
     */
    public function handleResponse(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response, callable $next);
}
