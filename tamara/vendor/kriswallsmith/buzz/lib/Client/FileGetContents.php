<?php

declare (strict_types=1);
namespace TMS\Buzz\Client;

use TMS\Buzz\Configuration\ParameterBag;
use TMS\Buzz\Exception\NetworkException;
use TMS\Buzz\Message\HeaderConverter;
use TMS\Buzz\Message\ResponseBuilder;
use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
class FileGetContents extends \TMS\Buzz\Client\AbstractClient implements \TMS\Buzz\Client\BuzzClientInterface
{
    public function sendRequest(\TMS\Psr\Http\Message\RequestInterface $request, array $options = []) : \TMS\Psr\Http\Message\ResponseInterface
    {
        $options = $this->validateOptions($options);
        $context = \stream_context_create($this->getStreamContextArray($request, $options));
        $level = \error_reporting(0);
        $content = \file_get_contents($request->getUri()->__toString(), \false, $context);
        \error_reporting($level);
        if (\false === $content) {
            if ($error = \error_get_last()) {
                throw new \TMS\Buzz\Exception\NetworkException($request, $error['message']);
            }
            throw new \TMS\Buzz\Exception\NetworkException($request, 'Failed to get contents from ' . $request->getUri()->__toString());
        }
        $requestBuilder = new \TMS\Buzz\Message\ResponseBuilder($this->responseFactory);
        $requestBuilder->parseHttpHeaders($this->filterHeaders((array) $http_response_header));
        $requestBuilder->writeBody($content);
        return $requestBuilder->getResponse();
    }
    /**
     * Converts a request into an array for stream_context_create().
     *
     * @param RequestInterface $request A request object
     *
     * @return array An array for stream_context_create()
     */
    protected function getStreamContextArray(\TMS\Psr\Http\Message\RequestInterface $request, \TMS\Buzz\Configuration\ParameterBag $options) : array
    {
        $headers = $request->getHeaders();
        unset($headers['Host']);
        $context = ['http' => [
            // values from the request
            'method' => $request->getMethod(),
            'header' => \implode("\r\n", \TMS\Buzz\Message\HeaderConverter::toBuzzHeaders($headers)),
            'content' => $request->getBody()->__toString(),
            'protocol_version' => $request->getProtocolVersion(),
            // values from the current client
            'ignore_errors' => \true,
            'follow_location' => $options->get('allow_redirects') && $options->get('max_redirects') > 0,
            'max_redirects' => $options->get('max_redirects') + 1,
        ], 'ssl' => ['verify_peer' => $options->get('verify'), 'verify_host' => $options->get('verify')]];
        if (0 < $options->get('timeout')) {
            $context['http']['timeout'] = $options->get('timeout');
        }
        if (null !== $options->get('proxy')) {
            $context['http']['proxy'] = $options->get('proxy');
            $context['http']['request_fulluri'] = \true;
        }
        return $context;
    }
    private function filterHeaders(array $headers) : array
    {
        $filtered = [];
        foreach ($headers as $header) {
            if (0 === \stripos($header, 'http/')) {
                $filtered = [];
            }
            $filtered[] = $header;
        }
        return $filtered;
    }
}
