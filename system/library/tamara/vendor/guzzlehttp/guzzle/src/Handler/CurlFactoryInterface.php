<?php

namespace TMS\GuzzleHttp\Handler;

use TMS\Psr\Http\Message\RequestInterface;
interface CurlFactoryInterface
{
    /**
     * Creates a cURL handle resource.
     *
     * @param RequestInterface $request Request
     * @param array            $options Transfer options
     *
     * @throws \RuntimeException when an option cannot be applied
     */
    public function create(\TMS\Psr\Http\Message\RequestInterface $request, array $options) : \TMS\GuzzleHttp\Handler\EasyHandle;
    /**
     * Release an easy handle, allowing it to be reused or closed.
     *
     * This function must call unset on the easy handle's "handle" property.
     */
    public function release(\TMS\GuzzleHttp\Handler\EasyHandle $easy) : void;
}
