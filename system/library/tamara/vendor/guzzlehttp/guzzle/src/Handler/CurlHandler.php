<?php

namespace TMS\GuzzleHttp\Handler;

use TMS\GuzzleHttp\Promise\PromiseInterface;
use TMS\Psr\Http\Message\RequestInterface;
/**
 * HTTP handler that uses cURL easy handles as a transport layer.
 *
 * When using the CurlHandler, custom curl options can be specified as an
 * associative array of curl option constants mapping to values in the
 * **curl** key of the "client" key of the request.
 *
 * @final
 */
class CurlHandler
{
    /**
     * @var CurlFactoryInterface
     */
    private $factory;
    /**
     * Accepts an associative array of options:
     *
     * - factory: Optional curl factory used to create cURL handles.
     *
     * @param array $options Array of options to use with the handler
     */
    public function __construct(array $options = [])
    {
        $this->factory = $options['handle_factory'] ?? new \TMS\GuzzleHttp\Handler\CurlFactory(3);
    }
    public function __invoke(\TMS\Psr\Http\Message\RequestInterface $request, array $options) : \TMS\GuzzleHttp\Promise\PromiseInterface
    {
        if (isset($options['delay'])) {
            \usleep($options['delay'] * 1000);
        }
        $easy = $this->factory->create($request, $options);
        \curl_exec($easy->handle);
        $easy->errno = \curl_errno($easy->handle);
        return \TMS\GuzzleHttp\Handler\CurlFactory::finish($this, $easy, $this->factory);
    }
}
