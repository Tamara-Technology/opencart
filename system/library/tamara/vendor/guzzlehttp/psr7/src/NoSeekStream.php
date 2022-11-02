<?php

namespace TMS\GuzzleHttp\Psr7;

use TMS\Psr\Http\Message\StreamInterface;
/**
 * Stream decorator that prevents a stream from being seeked.
 *
 * @final
 */
class NoSeekStream implements \TMS\Psr\Http\Message\StreamInterface
{
    use StreamDecoratorTrait;
    public function seek($offset, $whence = \SEEK_SET)
    {
        throw new \RuntimeException('Cannot seek a NoSeekStream');
    }
    public function isSeekable()
    {
        return \false;
    }
}
