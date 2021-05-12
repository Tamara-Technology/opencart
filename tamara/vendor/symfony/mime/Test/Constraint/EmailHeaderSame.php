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
use TMS\Symfony\Component\Mime\Header\UnstructuredHeader;
use TMS\Symfony\Component\Mime\RawMessage;
final class EmailHeaderSame extends \TMS\PHPUnit\Framework\Constraint\Constraint
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
        return \sprintf('has header "%s" with value "%s"', $this->headerName, $this->expectedValue);
    }
    /**
     * @param RawMessage $message
     *
     * {@inheritdoc}
     */
    protected function matches($message) : bool
    {
        if (\TMS\Symfony\Component\Mime\RawMessage::class === \get_class($message)) {
            throw new \LogicException('Unable to test a message header on a RawMessage instance.');
        }
        return $this->expectedValue === $this->getHeaderValue($message);
    }
    /**
     * @param RawMessage $message
     *
     * {@inheritdoc}
     */
    protected function failureDescription($message) : string
    {
        return \sprintf('the Email %s (value is %s)', $this->toString(), $this->getHeaderValue($message));
    }
    private function getHeaderValue($message) : string
    {
        $header = $message->getHeaders()->get($this->headerName);
        return $header instanceof \TMS\Symfony\Component\Mime\Header\UnstructuredHeader ? $header->getValue() : $header->getBodyAsString();
    }
}
