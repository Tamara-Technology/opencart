<?php

namespace TMS\Tamara\HttpClient;

use TMS\GuzzleHttp\Psr7\Request;
use TMS\Psr\Log\LoggerInterface;
class AdapterFactory
{
    public static function create(int $requestTimeout, \TMS\Psr\Log\LoggerInterface $logger = null) : \TMS\Tamara\HttpClient\ClientInterface
    {
        // have an issue with psr7 stream (empty request body)
        if (\class_exists(\TMS\GuzzleHttp\Psr7\Request::class)) {
            return new \TMS\Tamara\HttpClient\GuzzleHttpAdapter($requestTimeout, $logger);
        }
        return new \TMS\Tamara\HttpClient\NyholmHttpAdapter($requestTimeout, $logger);
    }
}
