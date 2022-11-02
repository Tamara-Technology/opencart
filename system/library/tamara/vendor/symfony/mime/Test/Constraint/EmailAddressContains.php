<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace TMS\Symfony\Component\Mime\Test\Constraint;

use TMS\PHPUnit\Framework\Constraint\Constraint;
use TMS\Symfony\Component\Mime\Header\MailboxHeader;
use TMS\Symfony\Component\Mime\Header\MailboxListHeader;
use TMS\Symfony\Component\Mime\RawMessage;
final class EmailAddressContains extends \TMS\PHPUnit\Framework\Constraint\Constraint
{
    private $headerName;
    private $expectedValue;
    public function __construct(string $headerName, string $expectedValue)
    {
        $this->headerName = $headerName;
        $this->expectedValue = $expectedValue;
    }
    /**
     * {@inheritdoc}
     */
    public function toString() : string
    {
        return \sprintf('contains address "%s" with value "%s"', $this->headerName, $this->expectedValue);
    }
    /**
     * @param RawMessage $message
     *
     * {@inheritdoc}
     */
    protected function matches($message) : bool
    {
        if (\TMS\Symfony\Component\Mime\RawMessage::class === \get_class($message)) {
            throw new \LogicException('Unable to test a message address on a RawMessage instance.');
        }
        $header = $message->getHeaders()->get($this->headerName);
        if ($header instanceof \TMS\Symfony\Component\Mime\Header\MailboxHeader) {
            return $this->expectedValue === $header->getAddress()->getAddress();
        } elseif ($header instanceof \TMS\Symfony\Component\Mime\Header\MailboxListHeader) {
            foreach ($header->getAddresses() as $address) {
                if ($this->expectedValue === $address->getAddress()) {
                    return \true;
                }
            }
            return \false;
        }
        throw new \LogicException('Unable to test a message address on a non-address header.');
    }
    /**
     * @param RawMessage $message
     *
     * {@inheritdoc}
     */
    protected function failureDescription($message) : string
    {
        return \sprintf('the Email %s (value is %s)', $this->toString(), $message->getHeaders()->get($this->headerName)->getBodyAsString());
    }
}
