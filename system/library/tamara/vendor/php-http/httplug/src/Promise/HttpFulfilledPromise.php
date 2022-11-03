<?php

namespace TMS\Http\Client\Promise;

use TMS\Http\Client\Exception;
use TMS\Http\Promise\Promise;
use TMS\Psr\Http\Message\ResponseInterface;
final class HttpFulfilledPromise implements \TMS\Http\Promise\Promise
{
    /**
     * @var ResponseInterface
     */
    private $response;
    public function __construct(\TMS\Psr\Http\Message\ResponseInterface $response)
    {
        $this->response = $response;
    }
    /**
     * {@inheritdoc}
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null)
    {
        if (null === $onFulfilled) {
            return $this;
        }
        try {
            return new self($onFulfilled($this->response));
        } catch (\TMS\Http\Client\Exception $e) {
            return new \TMS\Http\Client\Promise\HttpRejectedPromise($e);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function getState()
    {
        return \TMS\Http\Promise\Promise::FULFILLED;
    }
    /**
     * {@inheritdoc}
     */
    public function wait($unwrap = \true)
    {
        if ($unwrap) {
            return $this->response;
        }
    }
}
