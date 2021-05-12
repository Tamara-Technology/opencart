<?php

namespace TMS\Psr\Http\Message;

interface UriFactoryInterface
{
    /**
     * Create a new URI.
     *
     * @param string $uri
     *
     * @return UriInterface
     *
     * @throws \InvalidArgumentException If the given URI cannot be parsed.
     */
    public function createUri(string $uri = '') : \TMS\Psr\Http\Message\UriInterface;
}
