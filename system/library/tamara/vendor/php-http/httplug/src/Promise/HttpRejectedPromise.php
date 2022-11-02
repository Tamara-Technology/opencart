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
            $result = $onRejected($this->exception);
            if ($result instanceof \TMS\Http\Promise\Promise) {
                return $result;
            }
            return new \TMS\Http\Client\Promise\HttpFulfilledPromise($result);
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
