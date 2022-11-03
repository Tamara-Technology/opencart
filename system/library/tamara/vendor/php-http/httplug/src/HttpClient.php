<?php

namespace TMS\Http\Client;

use TMS\Psr\Http\Client\ClientInterface;
/**
 * {@inheritdoc}
 *
 * Provide the Httplug HttpClient interface for BC.
 * You should typehint Psr\Http\Client\ClientInterface in new code
 */
interface HttpClient extends \TMS\Psr\Http\Client\ClientInterface
{
}
