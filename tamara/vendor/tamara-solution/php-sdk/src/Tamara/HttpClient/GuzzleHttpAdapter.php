<?php

namespace TMS\Tamara\HttpClient;

use TMS\GuzzleHttp\Client;
use TMS\GuzzleHttp\ClientInterface as GuzzleHttpClient;
use TMS\GuzzleHttp\Exception\GuzzleException;
use TMS\GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use TMS\GuzzleHttp\Psr7\Request;
use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
use TMS\Psr\Log\LoggerInterface;
use TMS\Tamara\Exception\RequestException;
use Throwable;
class GuzzleHttpAdapter implements \TMS\Tamara\HttpClient\ClientInterface
{
    /**
     * @var GuzzleHttpClient
     */
    protected $client;
    /**
     * @var int
     */
    protected $requestTimeout;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @param int $requestTimeout
     * @param LoggerInterface|null $logger
     */
    public function __construct(int $requestTimeout, \TMS\Psr\Log\LoggerInterface $logger = null)
    {
        $this->client = new \TMS\GuzzleHttp\Client();
        $this->requestTimeout = $requestTimeout;
        $this->logger = $logger;
    }
    /** {@inheritDoc} */
    public function createRequest(string $method, $uri, array $headers = [], $body = null, $version = '1.1') : \TMS\Psr\Http\Message\RequestInterface
    {
        return new \TMS\GuzzleHttp\Psr7\Request($method, $uri, $headers, $body);
    }
    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws RequestException
     */
    public function sendRequest(\TMS\Psr\Http\Message\RequestInterface $request) : \TMS\Psr\Http\Message\ResponseInterface
    {
        try {
            return $this->client->send($request, ['timeout' => $this->requestTimeout]);
        } catch (\Throwable|\TMS\GuzzleHttp\Exception\GuzzleException|\TMS\GuzzleHttp\Exception\RequestException $exception) {
            if (null !== $this->logger) {
                $this->logger->error($exception->getMessage(), $exception->getTrace());
            }
            throw new \TMS\Tamara\Exception\RequestException($exception->getMessage(), $exception->getCode(), $request, $exception instanceof \TMS\GuzzleHttp\Exception\GuzzleException ? $exception->getResponse() : null, $exception->getPrevious());
        }
    }
}
