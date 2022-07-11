<?php

declare (strict_types=1);
namespace TMS\Nyholm\Psr7;

use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\StreamInterface;
use TMS\Psr\Http\Message\UriInterface;
/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class Request implements \TMS\Psr\Http\Message\RequestInterface
{
    use MessageTrait;
    use RequestTrait;
    /**
     * @param string $method HTTP method
     * @param string|UriInterface $uri URI
     * @param array $headers Request headers
     * @param string|resource|StreamInterface|null $body Request body
     * @param string $version Protocol version
     */
    public function __construct(string $method, $uri, array $headers = [], $body = null, string $version = '1.1')
    {
        if (!$uri instanceof \TMS\Psr\Http\Message\UriInterface) {
            $uri = new \TMS\Nyholm\Psr7\Uri($uri);
        }
        $this->method = $method;
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;
        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }
        // If we got no body, defer initialization of the stream until Request::getBody()
        if ('' !== $body && null !== $body) {
            $this->stream = \TMS\Nyholm\Psr7\Stream::create($body);
        }
    }
}
