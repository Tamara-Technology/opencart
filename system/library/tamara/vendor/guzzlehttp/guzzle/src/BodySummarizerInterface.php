<?php

namespace TMS\GuzzleHttp;

use TMS\Psr\Http\Message\MessageInterface;
interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(\TMS\Psr\Http\Message\MessageInterface $message) : ?string;
}
