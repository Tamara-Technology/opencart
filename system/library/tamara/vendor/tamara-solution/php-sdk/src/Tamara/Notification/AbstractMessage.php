<?php

declare (strict_types=1);
namespace TMS\Tamara\Notification;

use TMS\Tamara\Notification\Exception\NotificationException;
abstract class AbstractMessage
{
    public const ORDER_ID = 'order_id', ORDER_REFERENCE_ID = 'order_reference_id', DATA = 'data';
    /**
     * @var string the Tamara unique order id
     */
    private $orderId;
    /**
     * @var string the merchant unique order id
     */
    private $orderReferenceId;
    /**
     * @var array
     */
    private $data;
    public function __construct(string $orderId, string $orderReferenceId, array $data)
    {
        $this->orderId = $orderId;
        $this->orderReferenceId = $orderReferenceId;
        $this->data = $data;
    }
    public function getOrderId() : string
    {
        return $this->orderId;
    }
    public function getOrderReferenceId() : string
    {
        return $this->orderReferenceId;
    }
    public function getData() : array
    {
        return $this->data;
    }
    public function getDataByKey(string $key)
    {
        if (!isset($this->data[$key])) {
            throw new \TMS\Tamara\Notification\Exception\NotificationException(\sprintf('Invalid key %s', $key));
        }
        return $this->data[$key];
    }
    public static abstract function fromArray(array $data) : \TMS\Tamara\Notification\AbstractMessage;
}
