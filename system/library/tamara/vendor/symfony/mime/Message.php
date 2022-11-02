<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace TMS\Symfony\Component\Mime;

use TMS\Symfony\Component\Mime\Exception\LogicException;
use TMS\Symfony\Component\Mime\Header\Headers;
use TMS\Symfony\Component\Mime\Part\AbstractPart;
use TMS\Symfony\Component\Mime\Part\TextPart;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Message extends \TMS\Symfony\Component\Mime\RawMessage
{
    private $headers;
    private $body;
    public function __construct(\TMS\Symfony\Component\Mime\Header\Headers $headers = null, \TMS\Symfony\Component\Mime\Part\AbstractPart $body = null)
    {
        $this->headers = $headers ? clone $headers : new \TMS\Symfony\Component\Mime\Header\Headers();
        $this->body = $body;
    }
    public function __clone()
    {
        $this->headers = clone $this->headers;
        if (null !== $this->body) {
            $this->body = clone $this->body;
        }
    }
    /**
     * @return $this
     */
    public function setBody(\TMS\Symfony\Component\Mime\Part\AbstractPart $body = null)
    {
        $this->body = $body;
        return $this;
    }
    public function getBody() : ?\TMS\Symfony\Component\Mime\Part\AbstractPart
    {
        return $this->body;
    }
    /**
     * @return $this
     */
    public function setHeaders(\TMS\Symfony\Component\Mime\Header\Headers $headers)
    {
        $this->headers = $headers;
        return $this;
    }
    public function getHeaders() : \TMS\Symfony\Component\Mime\Header\Headers
    {
        return $this->headers;
    }
    public function getPreparedHeaders() : \TMS\Symfony\Component\Mime\Header\Headers
    {
        $headers = clone $this->headers;
        if (!$headers->has('From')) {
            if (!$headers->has('Sender')) {
                throw new \TMS\Symfony\Component\Mime\Exception\LogicException('An email must have a "From" or a "Sender" header.');
            }
            $headers->addMailboxListHeader('From', [$headers->get('Sender')->getAddress()]);
        }
        $headers->addTextHeader('MIME-Version', '1.0');
        if (!$headers->has('Date')) {
            $headers->addDateHeader('Date', new \DateTimeImmutable());
        }
        // determine the "real" sender
        if (!$headers->has('Sender') && \count($froms = $headers->get('From')->getAddresses()) > 1) {
            $headers->addMailboxHeader('Sender', $froms[0]);
        }
        if (!$headers->has('Message-ID')) {
            $headers->addIdHeader('Message-ID', $this->generateMessageId());
        }
        // remove the Bcc field which should NOT be part of the sent message
        $headers->remove('Bcc');
        return $headers;
    }
    public function toString() : string
    {
        if (null === ($body = $this->getBody())) {
            $body = new \TMS\Symfony\Component\Mime\Part\TextPart('');
        }
        return $this->getPreparedHeaders()->toString() . $body->toString();
    }
    public function toIterable() : iterable
    {
        if (null === ($body = $this->getBody())) {
            $body = new \TMS\Symfony\Component\Mime\Part\TextPart('');
        }
        (yield $this->getPreparedHeaders()->toString());
        yield from $body->toIterable();
    }
    public function ensureValidity()
    {
        if (!$this->headers->has('To') && !$this->headers->has('Cc') && !$this->headers->has('Bcc')) {
            throw new \TMS\Symfony\Component\Mime\Exception\LogicException('An email must have a "To", "Cc", or "Bcc" header.');
        }
        if (!$this->headers->has('From') && !$this->headers->has('Sender')) {
            throw new \TMS\Symfony\Component\Mime\Exception\LogicException('An email must have a "From" or a "Sender" header.');
        }
        parent::ensureValidity();
    }
    public function generateMessageId() : string
    {
        if ($this->headers->has('Sender')) {
            $sender = $this->headers->get('Sender')->getAddress();
        } elseif ($this->headers->has('From')) {
            $sender = $this->headers->get('From')->getAddresses()[0];
        } else {
            throw new \TMS\Symfony\Component\Mime\Exception\LogicException('An email must have a "From" or a "Sender" header.');
        }
        return \bin2hex(\random_bytes(16)) . \strstr($sender->getAddress(), '@');
    }
    public function __serialize() : array
    {
        return [$this->headers, $this->body];
    }
    public function __unserialize(array $data) : void
    {
        [$this->headers, $this->body] = $data;
    }
}
