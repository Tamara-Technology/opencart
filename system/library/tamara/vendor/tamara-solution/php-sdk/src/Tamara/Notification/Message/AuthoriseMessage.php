<?php

declare (strict_types=1);
namespace TMS\Tamara\Notification\Message;

use TMS\Tamara\Notification\AbstractMessage;
class AuthoriseMessage extends \TMS\Tamara\Notification\AbstractMessage
{
    private const ORDER_STATUS = 'order_status';
    /**
     * @var string
     */
    private $orderStatus;
    public function __construct(string $orderId, string $orderReferenceId, array $data, string $orderStatus)
    {
        parent::__construct($orderId, $orderReferenceId, $data);
        $this->orderStatus = $orderStatus;
    }
    public static function fromArray(array $data) : \TMS\Tamara\Notification\AbstractMessage
    {
        return new static($data[self::ORDER_ID], $data[self::ORDER_REFERENCE_ID], $data[self::DATA], $data[self::ORDER_STATUS]);
    }
    public function getOrderStatus() : string
    {
        return $this->orderStatus;
    }
}
