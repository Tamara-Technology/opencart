<?php

namespace TMS\Http\Client;

use TMS\Psr\Http\Client\ClientExceptionInterface as PsrClientException;
/**
 * Every HTTP Client related Exception must implement this interface.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
interface Exception extends \TMS\Psr\Http\Client\ClientExceptionInterface
{
}
