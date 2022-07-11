<?php

namespace TMS\Tamara;

use TMS\Psr\Http\Client\ClientInterface;
use TMS\Psr\Log\LoggerInterface;
use TMS\Tamara\HttpClient\AdapterFactory;
use TMS\Tamara\HttpClient\HttpClient;
class Configuration
{
    /**
     * @var string
     */
    protected $apiUrl;
    /**
     * @var string
     */
    protected $apiToken;
    /**
     * @var int in seconds
     */
    protected $apiRequestTimeout = 120;
    /**
     * @var ClientInterface
     */
    protected $transport;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @param string               $apiUrl
     * @param string               $apiToken
     * @param int|null             $apiRequestTimeout
     * @param LoggerInterface|null $logger
     * @param ClientInterface|null $transport
     *
     * @return Configuration
     */
    public static function create(string $apiUrl, string $apiToken, int $apiRequestTimeout = null, \TMS\Psr\Log\LoggerInterface $logger = null, \TMS\Psr\Http\Client\ClientInterface $transport = null) : \TMS\Tamara\Configuration
    {
        return new static($apiUrl, $apiToken, $apiRequestTimeout, $logger, $transport);
    }
    /**
     * @param string               $apiUrl
     * @param string               $apiToken
     * @param int|null             $apiRequestTimeout
     * @param LoggerInterface|null $logger
     * @param ClientInterface|null $transport
     */
    public function __construct(string $apiUrl, string $apiToken, int $apiRequestTimeout = null, \TMS\Psr\Log\LoggerInterface $logger = null, \TMS\Psr\Http\Client\ClientInterface $transport = null)
    {
        $this->apiUrl = $apiUrl;
        $this->apiToken = $apiToken;
        if (null !== $apiRequestTimeout) {
            $this->apiRequestTimeout = $apiRequestTimeout;
        }
        $this->logger = $logger;
        $this->transport = $transport;
    }
    /**
     * @return HttpClient
     */
    public function createHttpClient() : \TMS\Tamara\HttpClient\HttpClient
    {
        $transport = $this->transport ?? $this->createDefaultTransport();
        return new \TMS\Tamara\HttpClient\HttpClient($this->getApiUrl(), $this->getApiToken(), $transport);
    }
    /**
     * @return string
     */
    public function getApiUrl() : string
    {
        return $this->apiUrl;
    }
    /**
     * @return string
     */
    public function getApiToken() : string
    {
        return $this->apiToken;
    }
    /**
     * @return int
     */
    public function getApiRequestTimeout() : int
    {
        return $this->apiRequestTimeout;
    }
    /**
     * @return LoggerInterface|null
     */
    public function getLogger() : ?\TMS\Psr\Log\LoggerInterface
    {
        return $this->logger ?? null;
    }
    /**
     * @return ClientInterface
     */
    protected function createDefaultTransport() : \TMS\Psr\Http\Client\ClientInterface
    {
        return \TMS\Tamara\HttpClient\AdapterFactory::create($this->getApiRequestTimeout(), $this->getLogger());
    }
}
