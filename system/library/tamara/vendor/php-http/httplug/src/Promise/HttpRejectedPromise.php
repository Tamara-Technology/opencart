<?php

namespace TMS\Http\Client\Promise;

use TMS\Http\Client\Exception;
use TMS\Http\Promise\Promise;
final class HttpRejectedPromise implements \TMS\Http\Promise\Promise
{
    /**
     * @var Exception
     */
    private $exception;
    public function __construct(\TMS\Http\Client\Exception $exception)
    {
        $this->exception = $exception;
    }
    /**
     * {@inheritdoc}
     */
    public function then(callable $onFulfilled = null, callable $onRejected = null)
    {
        if (null === $onRejected) {
            return $this;
        }
        try {
            return new \TMS\Http\Client\Promise\HttpFulfilledPromise($onRejected($this->exception));
        } catch (\TMS\Http\Client\Exception $e) {
            return new self($e);
        }
    }
    /**
     * {@inheritdoc}
     */
    public function getState()
    {
        return \TMS\Http\Promise\Promise::REJECTED;
    }
    /**
     * {@inheritdoc}
     */
    public function wait($unwrap = \true)
    {
        if ($unwrap) {
            throw $this->exception;
        }
    }
}
