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
final class EmailTextBodyContains extends \TMS\PHPUnit\Framework\Constraint\Constraint
{
    private $expectedText;
    public function __construct(string $expectedText)
    {
        $this->expectedText = $expectedText;
    }
    /**
     * {@inheritdoc}
     */
    public function toString() : string
    {
        return \sprintf('contains "%s"', $this->expectedText);
    }
    /**
     * {@inheritdoc}
     *
     * @param RawMessage $message
     */
    protected function matches($message) : bool
    {
        if (\TMS\Symfony\Component\Mime\RawMessage::class === \get_class($message) || \TMS\Symfony\Component\Mime\Message::class === \get_class($message)) {
            throw new \LogicException('Unable to test a message text body on a RawMessage or Message instance.');
        }
        return \false !== \mb_strpos($message->getTextBody(), $this->expectedText);
    }
    /**
     * {@inheritdoc}
     *
     * @param RawMessage $message
     */
    protected function failureDescription($message) : string
    {
        return 'the Email text body ' . $this->toString();
    }
}
