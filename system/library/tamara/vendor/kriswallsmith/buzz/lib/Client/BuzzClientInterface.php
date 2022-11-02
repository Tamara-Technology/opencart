<?php

declare (strict_types=1);
namespace TMS\Buzz\Client;

use TMS\Http\Client\HttpClient;
use TMS\Psr\Http\Client\ClientInterface;
use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface BuzzClientInterface extends \TMS\Psr\Http\Client\ClientInterface, \TMS\Http\Client\HttpClient
{
    /**
     * {@inheritdoc}
     */
    public function sendRequest(\TMS\Psr\Http\Message\RequestInterface $request, array $options = []) : \TMS\Psr\Http\Message\ResponseInterface;
}
