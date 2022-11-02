<?php

declare (strict_types=1);
namespace TMS\Tamara\Request;

use TMS\Tamara\HttpClient\HttpClient;
abstract class AbstractRequestHandler
{
    /**
     * @var HttpClient
     */
    protected $httpClient;
    public function __construct(\TMS\Tamara\HttpClient\HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }
}
