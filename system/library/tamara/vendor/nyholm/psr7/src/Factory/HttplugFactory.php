<?php

declare (strict_types=1);
namespace TMS\Nyholm\Psr7\Factory;

use TMS\Http\Message\MessageFactory;
use TMS\Http\Message\StreamFactory;
use TMS\Http\Message\UriFactory;
use TMS\Nyholm\Psr7\Request;
use TMS\Nyholm\Psr7\Response;
use TMS\Nyholm\Psr7\Stream;
use TMS\Nyholm\Psr7\Uri;
use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
use TMS\Psr\Http\Message\StreamInterface;
use TMS\Psr\Http\Message\UriInterface;
/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class HttplugFactory implements \TMS\Http\Message\MessageFactory, \TMS\Http\Message\StreamFactory, \TMS\Http\Message\UriFactory
{
    public function createRequest($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1') : \TMS\Psr\Http\Message\RequestInterface
    {
        return new \TMS\Nyholm\Psr7\Request($method, $uri, $headers, $body, $protocolVersion);
    }
    public function createResponse($statusCode = 200, $reasonPhrase = null, array $headers = [], $body = null, $version = '1.1') : \TMS\Psr\Http\Message\ResponseInterface
    {
        return new \TMS\Nyholm\Psr7\Response((int) $statusCode, $headers, $body, $version, $reasonPhrase);
    }
    public function createStream($body = null) : \TMS\Psr\Http\Message\StreamInterface
    {
        return \TMS\Nyholm\Psr7\Stream::create($body ?? '');
    }
    public function createUri($uri = '') : \TMS\Psr\Http\Message\UriInterface
    {
        if ($uri instanceof \TMS\Psr\Http\Message\UriInterface) {
            return $uri;
        }
        return new \TMS\Nyholm\Psr7\Uri($uri);
    }
}
