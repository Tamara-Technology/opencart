<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware;

use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
use TMS\Psr\Log\LoggerInterface;
use TMS\Psr\Log\NullLogger;
class LoggerMiddleware implements \TMS\Buzz\Middleware\MiddlewareInterface
{
    private $logger;
    private $level;
    private $prefix;
    private $startTime;
    /**
     * @param LoggerInterface $logger
     * @param string          $level
     * @param string|null     $prefix
     */
    public function __construct(\TMS\Psr\Log\LoggerInterface $logger = null, $level = 'info', $prefix = null)
    {
        $this->logger = $logger ?: new \TMS\Psr\Log\NullLogger();
        $this->level = $level;
        $this->prefix = $prefix;
    }
    public function handleRequest(\TMS\Psr\Http\Message\RequestInterface $request, callable $next)
    {
        $this->startTime = \microtime(\true);
        return $next($request);
    }
    public function handleResponse(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response, callable $next)
    {
        $seconds = \microtime(\true) - $this->startTime;
        $this->logger->log($this->level, \sprintf('%sSent "%s %s" in %dms', $this->prefix, $request->getMethod(), $request->getUri(), \round($seconds * 1000)));
        return $next($request, $response);
    }
}
