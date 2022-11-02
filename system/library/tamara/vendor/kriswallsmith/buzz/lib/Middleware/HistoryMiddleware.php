<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware;

use TMS\Buzz\Middleware\History\Journal;
use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
class HistoryMiddleware implements \TMS\Buzz\Middleware\MiddlewareInterface
{
    private $journal;
    private $startTime;
    public function __construct(\TMS\Buzz\Middleware\History\Journal $journal)
    {
        $this->journal = $journal;
    }
    public function getJournal() : \TMS\Buzz\Middleware\History\Journal
    {
        return $this->journal;
    }
    public function handleRequest(\TMS\Psr\Http\Message\RequestInterface $request, callable $next)
    {
        $this->startTime = \microtime(\true);
        return $next($request);
    }
    public function handleResponse(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response, callable $next)
    {
        $this->journal->record($request, $response, \microtime(\true) - $this->startTime);
        return $next($request, $response);
    }
}
