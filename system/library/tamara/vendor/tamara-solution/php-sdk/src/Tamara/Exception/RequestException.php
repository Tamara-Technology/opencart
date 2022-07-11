<?php

namespace TMS\Tamara\Exception;

use TMS\Psr\Http\Client\RequestExceptionInterface;
use TMS\Psr\Http\Message\RequestInterface;
use TMS\Psr\Http\Message\ResponseInterface;
use Exception;
class RequestException extends \Exception implements \TMS\Psr\Http\Client\RequestExceptionInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var ResponseInterface|null
     */
    protected $response;
    /**
     * @param string                 $message
     * @param int                    $code
     * @param RequestInterface       $request
     * @param null|ResponseInterface $response
     * @param Exception|null        $previous
     */
    public function __construct(string $message, int $code, \TMS\Psr\Http\Message\RequestInterface $request, ?\TMS\Psr\Http\Message\ResponseInterface $response, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
        $this->response = $response;
    }
    public function getRequest() : \TMS\Psr\Http\Message\RequestInterface
    {
        return $this->request;
    }
    /**
     * @return ResponseInterface|null
     */
    public function getResponse() : ?\TMS\Psr\Http\Message\ResponseInterface
    {
        return $this->response;
    }
}
