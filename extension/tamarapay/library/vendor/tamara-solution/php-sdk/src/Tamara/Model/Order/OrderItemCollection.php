<?php

declare (strict_types=1);
namespace TMS\Tamara\Model\Order;

use ArrayIterator;
use Countable;
use IteratorAggregate;
class OrderItemCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array|OrderItem[]
     */
    private $data = [];
    public static function create(array $data) : \TMS\Tamara\Model\Order\OrderItemCollection
    {
        $self = new self();
        foreach ($data as $itemData) {
            $self->data[] = \TMS\Tamara\Model\Order\OrderItem::fromArray($itemData);
        }
        return $self;
    }
    public function append(\TMS\Tamara\Model\Order\OrderItem $item) : \TMS\Tamara\Model\Order\OrderItemCollection
    {
        $this->data[] = $item;
        return $this;
    }
    public function getItems() : array
    {
        return $this->data;
    }
    public function toArray() : array
    {
        $ret = [];
        /** @var OrderItem $item */
        foreach ($this->data as $item) {
            $ret[] = $item->toArray();
        }
        return $ret;
    }
    public function getIterator() : \ArrayIterator
    {
        return new \ArrayIterator($this->data);
    }
    /**
     * @inheritDoc
     */
    public function count() : int
    {
        return \count($this->data);
    }
}
