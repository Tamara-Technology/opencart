<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Payment;

use TMS\Tamara\Model\Payment\Capture;
class CaptureRequest
{
    /**
     * @var Capture
     */
    private $capture;
    public function __construct(\TMS\Tamara\Model\Payment\Capture $capture)
    {
        $this->capture = $capture;
    }
    public function getCapture() : \TMS\Tamara\Model\Payment\Capture
    {
        return $this->capture;
    }
}
