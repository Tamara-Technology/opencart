<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Payment;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Payment\CaptureResponse;
class CaptureRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const CAPTURE_ENDPOINT = '/payments/capture';
    public function __invoke(\TMS\Tamara\Request\Payment\CaptureRequest $request)
    {
        $response = $this->httpClient->post(self::CAPTURE_ENDPOINT, $request->getCapture()->toArray());
        return new \TMS\Tamara\Response\Payment\CaptureResponse($response);
    }
}
