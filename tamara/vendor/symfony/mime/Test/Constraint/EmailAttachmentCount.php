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
use TMS\Symfony\Component\Mime\Message;
use TMS\Symfony\Component\Mime\RawMessage;
final class EmailAttachmentCount extends \TMS\PHPUnit\Framework\Constraint\Constraint
{
    private $expectedValue;
    private $transport;
    public function __construct(int $expectedValue, string $transport = null)
    {
        $this->expectedValue = $expectedValue;
        $this->transport = $transport;
    }
    /**
     * {@inheritdoc}
     */
    public function toString() : string
    {
        return \sprintf('has sent "%d" attachment(s)', $this->expectedValue);
    }
    /**
     * @param RawMessage $message
     *
     * {@inheritdoc}
     */
    protected function matches($message) : bool
    {
        if (\TMS\Symfony\Component\Mime\RawMessage::class === \get_class($message) || \TMS\Symfony\Component\Mime\Message::class === \get_class($message)) {
            throw new \LogicException('Unable to test a message attachment on a RawMessage or Message instance.');
        }
        return $this->expectedValue === \count($message->getAttachments());
    }
    /**
     * @param RawMessage $message
     *
     * {@inheritdoc}
     */
    protected function failureDescription($message) : string
    {
        return 'the Email ' . $this->toString();
    }
}
