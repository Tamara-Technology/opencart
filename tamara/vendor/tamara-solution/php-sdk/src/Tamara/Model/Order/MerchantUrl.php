<?php

declare (strict_types=1);
namespace TMS\Tamara\Model\Order;

class MerchantUrl
{
    public const SUCCESS = 'success', FAILURE = 'failure', CANCEL = 'cancel', NOTIFICATION = 'notification';
    /**
     * @var string
     */
    private $successUrl;
    /**
     * @var string
     */
    private $failureUrl;
    /**
     * @var string
     */
    private $cancelUrl;
    /**
     * @var string
     */
    private $notificationUrl;
    public function setSuccessUrl(string $successUrl) : \TMS\Tamara\Model\Order\MerchantUrl
    {
        $this->successUrl = $successUrl;
        return $this;
    }
    public function setFailureUrl(string $failureUrl) : \TMS\Tamara\Model\Order\MerchantUrl
    {
        $this->failureUrl = $failureUrl;
        return $this;
    }
    public function setCancelUrl(string $cancelUrl) : \TMS\Tamara\Model\Order\MerchantUrl
    {
        $this->cancelUrl = $cancelUrl;
        return $this;
    }
    public function setNotificationUrl(string $notificationUrl) : \TMS\Tamara\Model\Order\MerchantUrl
    {
        $this->notificationUrl = $notificationUrl;
        return $this;
    }
    public function getSuccessUrl() : string
    {
        return $this->successUrl;
    }
    public function getFailureUrl() : string
    {
        return $this->failureUrl;
    }
    public function getCancelUrl() : string
    {
        return $this->cancelUrl;
    }
    public function getNotificationUrl() : string
    {
        return $this->notificationUrl;
    }
    public function toArray() : array
    {
        return [self::SUCCESS => $this->getSuccessUrl(), self::FAILURE => $this->getFailureUrl(), self::CANCEL => $this->getCancelUrl(), self::NOTIFICATION => $this->getNotificationUrl()];
    }
}
