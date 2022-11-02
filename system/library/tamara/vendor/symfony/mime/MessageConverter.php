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

use TMS\Symfony\Component\Mime\Exception\RuntimeException;
use TMS\Symfony\Component\Mime\Part\DataPart;
use TMS\Symfony\Component\Mime\Part\Multipart\AlternativePart;
use TMS\Symfony\Component\Mime\Part\Multipart\MixedPart;
use TMS\Symfony\Component\Mime\Part\Multipart\RelatedPart;
use TMS\Symfony\Component\Mime\Part\TextPart;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class MessageConverter
{
    /**
     * @throws RuntimeException when unable to convert the message to an email
     */
    public static function toEmail(\TMS\Symfony\Component\Mime\Message $message) : \TMS\Symfony\Component\Mime\Email
    {
        if ($message instanceof \TMS\Symfony\Component\Mime\Email) {
            return $message;
        }
        // try to convert to a "simple" Email instance
        $body = $message->getBody();
        if ($body instanceof \TMS\Symfony\Component\Mime\Part\TextPart) {
            return self::createEmailFromTextPart($message, $body);
        }
        if ($body instanceof \TMS\Symfony\Component\Mime\Part\Multipart\AlternativePart) {
            return self::createEmailFromAlternativePart($message, $body);
        }
        if ($body instanceof \TMS\Symfony\Component\Mime\Part\Multipart\RelatedPart) {
            return self::createEmailFromRelatedPart($message, $body);
        }
        if ($body instanceof \TMS\Symfony\Component\Mime\Part\Multipart\MixedPart) {
            $parts = $body->getParts();
            if ($parts[0] instanceof \TMS\Symfony\Component\Mime\Part\Multipart\RelatedPart) {
                $email = self::createEmailFromRelatedPart($message, $parts[0]);
            } elseif ($parts[0] instanceof \TMS\Symfony\Component\Mime\Part\Multipart\AlternativePart) {
                $email = self::createEmailFromAlternativePart($message, $parts[0]);
            } elseif ($parts[0] instanceof \TMS\Symfony\Component\Mime\Part\TextPart) {
                $email = self::createEmailFromTextPart($message, $parts[0]);
            } else {
                throw new \TMS\Symfony\Component\Mime\Exception\RuntimeException(\sprintf('Unable to create an Email from an instance of "%s" as the body is too complex.', \get_class($message)));
            }
            return self::attachParts($email, \array_slice($parts, 1));
        }
        throw new \TMS\Symfony\Component\Mime\Exception\RuntimeException(\sprintf('Unable to create an Email from an instance of "%s" as the body is too complex.', \get_class($message)));
    }
    private static function createEmailFromTextPart(\TMS\Symfony\Component\Mime\Message $message, \TMS\Symfony\Component\Mime\Part\TextPart $part) : \TMS\Symfony\Component\Mime\Email
    {
        if ('text' === $part->getMediaType() && 'plain' === $part->getMediaSubtype()) {
            return (new \TMS\Symfony\Component\Mime\Email(clone $message->getHeaders()))->text($part->getBody(), $part->getPreparedHeaders()->getHeaderParameter('Content-Type', 'charset') ?: 'utf-8');
        }
        if ('text' === $part->getMediaType() && 'html' === $part->getMediaSubtype()) {
            return (new \TMS\Symfony\Component\Mime\Email(clone $message->getHeaders()))->html($part->getBody(), $part->getPreparedHeaders()->getHeaderParameter('Content-Type', 'charset') ?: 'utf-8');
        }
        throw new \TMS\Symfony\Component\Mime\Exception\RuntimeException(\sprintf('Unable to create an Email from an instance of "%s" as the body is too complex.', \get_class($message)));
    }
    private static function createEmailFromAlternativePart(\TMS\Symfony\Component\Mime\Message $message, \TMS\Symfony\Component\Mime\Part\Multipart\AlternativePart $part) : \TMS\Symfony\Component\Mime\Email
    {
        $parts = $part->getParts();
        if (2 === \count($parts) && $parts[0] instanceof \TMS\Symfony\Component\Mime\Part\TextPart && 'text' === $parts[0]->getMediaType() && 'plain' === $parts[0]->getMediaSubtype() && $parts[1] instanceof \TMS\Symfony\Component\Mime\Part\TextPart && 'text' === $parts[1]->getMediaType() && 'html' === $parts[1]->getMediaSubtype()) {
            return (new \TMS\Symfony\Component\Mime\Email(clone $message->getHeaders()))->text($parts[0]->getBody(), $parts[0]->getPreparedHeaders()->getHeaderParameter('Content-Type', 'charset') ?: 'utf-8')->html($parts[1]->getBody(), $parts[1]->getPreparedHeaders()->getHeaderParameter('Content-Type', 'charset') ?: 'utf-8');
        }
        throw new \TMS\Symfony\Component\Mime\Exception\RuntimeException(\sprintf('Unable to create an Email from an instance of "%s" as the body is too complex.', \get_class($message)));
    }
    private static function createEmailFromRelatedPart(\TMS\Symfony\Component\Mime\Message $message, \TMS\Symfony\Component\Mime\Part\Multipart\RelatedPart $part) : \TMS\Symfony\Component\Mime\Email
    {
        $parts = $part->getParts();
        if ($parts[0] instanceof \TMS\Symfony\Component\Mime\Part\Multipart\AlternativePart) {
            $email = self::createEmailFromAlternativePart($message, $parts[0]);
        } elseif ($parts[0] instanceof \TMS\Symfony\Component\Mime\Part\TextPart) {
            $email = self::createEmailFromTextPart($message, $parts[0]);
        } else {
            throw new \TMS\Symfony\Component\Mime\Exception\RuntimeException(\sprintf('Unable to create an Email from an instance of "%s" as the body is too complex.', \get_class($message)));
        }
        return self::attachParts($email, \array_slice($parts, 1));
    }
    private static function attachParts(\TMS\Symfony\Component\Mime\Email $email, array $parts) : \TMS\Symfony\Component\Mime\Email
    {
        foreach ($parts as $part) {
            if (!$part instanceof \TMS\Symfony\Component\Mime\Part\DataPart) {
                throw new \TMS\Symfony\Component\Mime\Exception\RuntimeException(\sprintf('Unable to create an Email from an instance of "%s" as the body is too complex.', \get_class($email)));
            }
            $headers = $part->getPreparedHeaders();
            $method = 'inline' === $headers->getHeaderBody('Content-Disposition') ? 'embed' : 'attach';
            $name = $headers->getHeaderParameter('Content-Disposition', 'filename');
            $email->{$method}($part->getBody(), $name, $part->getMediaType() . '/' . $part->getMediaSubtype());
        }
        return $email;
    }
}
