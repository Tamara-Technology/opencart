<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware;

use TMS\Buzz\Exception\InvalidArgumentException;
use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class CallbackMiddleware implements \TMS\Buzz\Middleware\MiddlewareInterface
{
    private $callable;
    /**
     * The callback should expect either one or two arguments, depending on
     * whether it is receiving a pre or post send notification.
     *
     *     $middleware = new CallbackMiddleware(function($request, $response = null) {
     *         if ($response) {
     *             // postSend
     *         } else {
     *             // preSend
     *         }
     *     });
     *
     * @param mixed $callable A PHP callable
     *
     * @throws InvalidArgumentException If the argument is not callable
     */
    public function __construct($callable)
    {
        if (!\is_callable($callable)) {
            throw new \TMS\Buzz\Exception\InvalidArgumentException('The argument is not callable.');
        }
        $this->callable = $callable;
    }
    public function handleRequest(\TMS\Psr\Http\Message\RequestInterface $request, callable $next)
    {
        $request = \call_user_func($this->callable, $request);
        return $next($request);
    }
    public function handleResponse(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response, callable $next)
    {
        $response = \call_user_func($this->callable, $request, $response);
        return $next($request, $response);
    }
}
