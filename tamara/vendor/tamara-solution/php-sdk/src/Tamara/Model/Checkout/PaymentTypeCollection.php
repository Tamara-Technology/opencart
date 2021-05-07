<?php

declare (strict_types=1);
namespace TMS\Tamara\Model\Checkout;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use TMS\Tamara\Model\Money;
class PaymentTypeCollection implements \IteratorAggregate, \Countable
{
    private const NAME = 'name', DESCRIPTION = 'description', MIN_LIMIT = 'min_limit', MAX_LIMIT = 'max_limit';
    /**
     * @var array|PaymentType[]
     */
    private $data = [];
    public function __construct(array $paymentTypes)
    {
        foreach ($paymentTypes as $paymentType) {
            $minLimit = $paymentType[self::MIN_LIMIT];
            $maxLimit = $paymentType[self::MAX_LIMIT];
            $this->data[] = new \TMS\Tamara\Model\Checkout\PaymentType($paymentType[self::NAME], $paymentType[self::DESCRIPTION], new \TMS\Tamara\Model\Money((float) $minLimit[\TMS\Tamara\Model\Money::AMOUNT], $minLimit[\TMS\Tamara\Model\Money::CURRENCY]), new \TMS\Tamara\Model\Money((float) $maxLimit[\TMS\Tamara\Model\Money::AMOUNT], $maxLimit[\TMS\Tamara\Model\Money::CURRENCY]));
        }
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
