<?php

declare (strict_types=1);
namespace TMS\Buzz\Exception;

use TMS\Psr\Http\Client\RequestExceptionInterface as PsrRequestException;
use TMS\Psr\Http\Message\RequestInterface;
/**
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
class CallbackException extends \TMS\Buzz\Exception\ClientException implements \TMS\Psr\Http\Client\RequestExceptionInterface
{
    private $request;
    public function __construct(\TMS\Psr\Http\Message\RequestInterface $request, string $message = '', int $code = 0, \Throwable $previous = null)
    {
        $this->request = $request;
        parent::__construct($message, $code, $previous);
    }
    public function getRequest() : \TMS\Psr\Http\Message\RequestInterface
    {
        return $this->request;
    }
}
