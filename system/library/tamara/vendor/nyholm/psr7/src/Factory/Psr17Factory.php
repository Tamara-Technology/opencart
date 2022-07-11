<?php

declare (strict_types=1);
namespace TMS\Nyholm\Psr7\Factory;

use TMS\Nyholm\Psr7\Request;
use TMS\Nyholm\Psr7\Response;
use TMS\Nyholm\Psr7\ServerRequest;
use TMS\Nyholm\Psr7\Stream;
use TMS\Nyholm\Psr7\UploadedFile;
use TMS\Nyholm\Psr7\Uri;
use TMS\Psr\Http\Message\RequestFactoryInterface;
use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseFactoryInterface;
use TMS\Psr\Http\Message\ResponseInterface;
use TMS\Psr\Http\Message\ServerRequestFactoryInterface;
use TMS\Psr\Http\Message\ServerRequestInterface;
use TMS\Psr\Http\Message\StreamFactoryInterface;
use TMS\Psr\Http\Message\StreamInterface;
use TMS\Psr\Http\Message\UploadedFileFactoryInterface;
use TMS\Psr\Http\Message\UploadedFileInterface;
use TMS\Psr\Http\Message\UriFactoryInterface;
use TMS\Psr\Http\Message\UriInterface;
/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class Psr17Factory implements \TMS\Psr\Http\Message\RequestFactoryInterface, \TMS\Psr\Http\Message\ResponseFactoryInterface, \TMS\Psr\Http\Message\ServerRequestFactoryInterface, \TMS\Psr\Http\Message\StreamFactoryInterface, \TMS\Psr\Http\Message\UploadedFileFactoryInterface, \TMS\Psr\Http\Message\UriFactoryInterface
{
    public function createRequest(string $method, $uri) : \TMS\Psr\Http\Message\RequestInterface
    {
        return new \TMS\Nyholm\Psr7\Request($method, $uri);
    }
    public function createResponse(int $code = 200, string $reasonPhrase = '') : \TMS\Psr\Http\Message\ResponseInterface
    {
        if (2 > \func_num_args()) {
            // This will make the Response class to use a custom reasonPhrase
            $reasonPhrase = null;
        }
        return new \TMS\Nyholm\Psr7\Response($code, [], null, '1.1', $reasonPhrase);
    }
    public function createStream(string $content = '') : \TMS\Psr\Http\Message\StreamInterface
    {
        return \TMS\Nyholm\Psr7\Stream::create($content);
    }
    public function createStreamFromFile(string $filename, string $mode = 'r') : \TMS\Psr\Http\Message\StreamInterface
    {
        $resource = @\fopen($filename, $mode);
        if (\false === $resource) {
            if ('' === $mode || \false === \in_array($mode[0], ['r', 'w', 'a', 'x', 'c'])) {
                throw new \InvalidArgumentException('The mode ' . $mode . ' is invalid.');
            }
            throw new \RuntimeException('The file ' . $filename . ' cannot be opened.');
        }
        return \TMS\Nyholm\Psr7\Stream::create($resource);
    }
    public function createStreamFromResource($resource) : \TMS\Psr\Http\Message\StreamInterface
    {
        return \TMS\Nyholm\Psr7\Stream::create($resource);
    }
    public function createUploadedFile(\TMS\Psr\Http\Message\StreamInterface $stream, int $size = null, int $error = \UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null) : \TMS\Psr\Http\Message\UploadedFileInterface
    {
        if (null === $size) {
            $size = $stream->getSize();
        }
        return new \TMS\Nyholm\Psr7\UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }
    public function createUri(string $uri = '') : \TMS\Psr\Http\Message\UriInterface
    {
        return new \TMS\Nyholm\Psr7\Uri($uri);
    }
    public function createServerRequest(string $method, $uri, array $serverParams = []) : \TMS\Psr\Http\Message\ServerRequestInterface
    {
        return new \TMS\Nyholm\Psr7\ServerRequest($method, $uri, [], null, '1.1', $serverParams);
    }
}
