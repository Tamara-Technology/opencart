<?php

namespace TMS\Http\Client\Exception;

use TMS\Psr\Http\Message\RequestInterface;
trait RequestAwareTrait
{
    /**
     * @var RequestInterface
     */
    private $request;
    private function setRequest(\TMS\Psr\Http\Message\RequestInterface $request)
    {
        $this->request = $request;
    }
    /**
     * {@inheritdoc}
     */
    public function getRequest() : \TMS\Psr\Http\Message\RequestInterface
    {
        return $this->request;
    }
}
