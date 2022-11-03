<?php

declare (strict_types=1);
namespace TMS\Tamara\Model\Order;

class Transactions
{
    private const CANCELS = 'cancels', CAPTURES = 'captures', REFUNDS = 'refunds';
    /**
     * @var CancelCollection
     */
    private $cancels;
    /**
     * @var CaptureCollection
     */
    private $captures;
    /**
     * @var RefundCollection
     */
    private $refunds;
    public static function fromArray(array $data) : \TMS\Tamara\Model\Order\Transactions
    {
        $self = new self();
        $self->setCancels(\TMS\Tamara\Model\Order\CancelCollection::create($data[self::CANCELS]));
        $self->setCaptures(\TMS\Tamara\Model\Order\CaptureCollection::create($data[self::CAPTURES]));
        $self->setRefunds(\TMS\Tamara\Model\Order\RefundCollection::create($data[self::REFUNDS]));
        return $self;
    }
    public function getCancels() : \TMS\Tamara\Model\Order\CancelCollection
    {
        return $this->cancels;
    }
    public function setCancels(\TMS\Tamara\Model\Order\CancelCollection $cancels) : void
    {
        $this->cancels = $cancels;
    }
    public function getCaptures() : \TMS\Tamara\Model\Order\CaptureCollection
    {
        return $this->captures;
    }
    public function setCaptures(\TMS\Tamara\Model\Order\CaptureCollection $captures) : void
    {
        $this->captures = $captures;
    }
    public function getRefunds() : \TMS\Tamara\Model\Order\RefundCollection
    {
        return $this->refunds;
    }
    public function setRefunds(\TMS\Tamara\Model\Order\RefundCollection $refunds) : void
    {
        $this->refunds = $refunds;
    }
    public function toArray() : array
    {
        return [self::CANCELS => $this->getCancels()->toArray(), self::CAPTURES => $this->getCaptures()->toArray(), self::REFUNDS => $this->getRefunds()->toArray()];
    }
}
