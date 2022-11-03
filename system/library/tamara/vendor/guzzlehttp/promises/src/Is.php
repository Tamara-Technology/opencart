<?php

namespace TMS\GuzzleHttp\Promise;

final class Is
{
    /**
     * Returns true if a promise is pending.
     *
     * @return bool
     */
    public static function pending(\TMS\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \TMS\GuzzleHttp\Promise\PromiseInterface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled or rejected.
     *
     * @return bool
     */
    public static function settled(\TMS\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() !== \TMS\GuzzleHttp\Promise\PromiseInterface::PENDING;
    }
    /**
     * Returns true if a promise is fulfilled.
     *
     * @return bool
     */
    public static function fulfilled(\TMS\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \TMS\GuzzleHttp\Promise\PromiseInterface::FULFILLED;
    }
    /**
     * Returns true if a promise is rejected.
     *
     * @return bool
     */
    public static function rejected(\TMS\GuzzleHttp\Promise\PromiseInterface $promise)
    {
        return $promise->getState() === \TMS\GuzzleHttp\Promise\PromiseInterface::REJECTED;
    }
}
