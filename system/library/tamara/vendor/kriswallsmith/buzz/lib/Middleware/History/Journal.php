<?php

declare (strict_types=1);
namespace TMS\Buzz\Middleware\History;

use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
class Journal implements \Countable, \IteratorAggregate
{
    /** @var Entry[] */
    private $entries = [];
    private $limit = 10;
    public function __construct(int $limit = 10)
    {
        $this->limit = $limit;
    }
    /**
     * Records an entry in the journal.
     *
     * @param RequestInterface  $request  The request
     * @param ResponseInterface $response The response
     * @param float|null        $duration The duration in seconds
     */
    public function record(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Psr\Http\Message\ResponseInterface $response, float $duration = null) : void
    {
        $this->addEntry(new \TMS\Buzz\Middleware\History\Entry($request, $response, $duration));
    }
    public function addEntry(\TMS\Buzz\Middleware\History\Entry $entry) : void
    {
        \array_push($this->entries, $entry);
        $this->entries = \array_slice($this->entries, $this->getLimit() * -1);
        \end($this->entries);
    }
    /**
     * @return Entry[]
     */
    public function getEntries() : array
    {
        return $this->entries;
    }
    public function getLast() : ?\TMS\Buzz\Middleware\History\Entry
    {
        $entry = \end($this->entries);
        return \false === $entry ? null : $entry;
    }
    public function getLastRequest() : ?\TMS\Psr\Http\Message\RequestInterface
    {
        $entry = $this->getLast();
        if (null === $entry) {
            return null;
        }
        return $entry->getRequest();
    }
    public function getLastResponse() : ?\TMS\Psr\Http\Message\ResponseInterface
    {
        $entry = $this->getLast();
        if (null === $entry) {
            return null;
        }
        return $entry->getResponse();
    }
    public function clear() : void
    {
        $this->entries = [];
    }
    public function count() : int
    {
        return \count($this->entries);
    }
    public function setLimit(int $limit) : void
    {
        $this->limit = $limit;
    }
    public function getLimit() : int
    {
        return $this->limit;
    }
    public function getIterator() : \ArrayIterator
    {
        return new \ArrayIterator(\array_reverse($this->entries));
    }
}
