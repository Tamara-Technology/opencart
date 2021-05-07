<?php

namespace TMS\Tamara\HttpClient;

use TMS\Buzz\Client\Curl;
use TMS\Nyholm\Psr7\Factory\Psr17Factory;
use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
use TMS\Psr\Log\LoggerInterface;
use TMS\Tamara\Exception\RequestException;
class NyholmHttpAdapter implements \TMS\Tamara\HttpClient\ClientInterface
{
    private $client;
    /**
     * @var int
     */
    protected $requestTimeout;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    public function __construct(int $requestTimeout, \TMS\Psr\Log\LoggerInterface $logger = null)
    {
        $this->requestTimeout = $requestTimeout;
        $this->logger = $logger;
        $this->client = new \TMS\Buzz\Client\Curl(new \TMS\Nyholm\Psr7\Factory\Psr17Factory());
    }
    /** {@inheritDoc} */
    public function createRequest(string $method, $uri, array $headers = [], $body = null, $version = '1.1') : \TMS\Psr\Http\Message\RequestInterface
    {
        return new \TMS\Nyholm\Psr7\Request($method, $uri, $headers, $body);
    }
    public function sendRequest(\TMS\Psr\Http\Message\RequestInterface $request) : \TMS\Psr\Http\Message\ResponseInterface
    {
        try {
            return $this->client->sendRequest($request, ['timeout' => $this->requestTimeout]);
        } catch (\Throwable $exception) {
            if (null !== $this->logger) {
                $this->logger->error($exception->getMessage(), $exception->getTrace());
            }
            throw new \TMS\Tamara\Exception\RequestException($exception->getMessage(), $exception->getCode(), $request, null, $exception->getPrevious());
        }
    }
}
