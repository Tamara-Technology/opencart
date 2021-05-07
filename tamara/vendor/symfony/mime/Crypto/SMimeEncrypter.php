<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace TMS\Symfony\Component\Mime\Crypto;

use TMS\Symfony\Component\Mime\Exception\RuntimeException;
use TMS\Symfony\Component\Mime\Message;
/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SMimeEncrypter extends \TMS\Symfony\Component\Mime\Crypto\SMime
{
    private $certs;
    private $cipher;
    /**
     * @param string|string[] $certificate The path (or array of paths) of the file(s) containing the X.509 certificate(s)
     * @param int|null        $cipher      A set of algorithms used to encrypt the message. Must be one of these PHP constants: https://www.php.net/manual/en/openssl.ciphers.php
     */
    public function __construct($certificate, int $cipher = null)
    {
        if (!\extension_loaded('openssl')) {
            throw new \LogicException('PHP extension "openssl" is required to use SMime.');
        }
        if (\is_array($certificate)) {
            $this->certs = \array_map([$this, 'normalizeFilePath'], $certificate);
        } else {
            $this->certs = $this->normalizeFilePath($certificate);
        }
        $this->cipher = $cipher ?? \OPENSSL_CIPHER_AES_256_CBC;
    }
    public function encrypt(\TMS\Symfony\Component\Mime\Message $message) : \TMS\Symfony\Component\Mime\Message
    {
        $bufferFile = \tmpfile();
        $outputFile = \tmpfile();
        $this->iteratorToFile($message->toIterable(), $bufferFile);
        if (!@\openssl_pkcs7_encrypt(\stream_get_meta_data($bufferFile)['uri'], \stream_get_meta_data($outputFile)['uri'], $this->certs, [], 0, $this->cipher)) {
            throw new \TMS\Symfony\Component\Mime\Exception\RuntimeException(\sprintf('Failed to encrypt S/Mime message. Error: "%s".', \openssl_error_string()));
        }
        $mimePart = $this->convertMessageToSMimePart($outputFile, 'application', 'pkcs7-mime');
        $mimePart->getHeaders()->addTextHeader('Content-Transfer-Encoding', 'base64')->addParameterizedHeader('Content-Disposition', 'attachment', ['name' => 'smime.p7m']);
        return new \TMS\Symfony\Component\Mime\Message($message->getHeaders(), $mimePart);
    }
}
