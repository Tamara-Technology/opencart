<?php

namespace TMS\Http\Client\Exception;

use TMS\Psr\Http\Client\RequestExceptionInterface as PsrRequestException;
use TMS\Psr\Http\Message\RequestInterface;
/**
 * Exception for when a request failed, providing access to the failed request.
 *
 * This could be due to an invalid request, or one of the extending exceptions
 * for network errors or HTTP error responses.
 *
 * @author Márk Sági-Kazár <mark.sagikazar@gmail.com>
 */
class RequestException extends \TMS\Http\Client\Exception\TransferException implements \TMS\Psr\Http\Client\RequestExceptionInterface
{
    use RequestAwareTrait;
    /**
     * @param string $message
     */
    public function __construct($message, \TMS\Psr\Http\Message\RequestInterface $request, \Exception $previous = null)
    {
        $this->setRequest($request);
        parent::__construct($message, 0, $previous);
    }
}
