<?php

namespace TMS\GuzzleHttp;

use TMS\Psr\Http\Message\MessageInterface;
final class BodySummarizer implements \TMS\GuzzleHttp\BodySummarizerInterface
{
    /**
     * @var int|null
     */
    private $truncateAt;
    public function __construct(int $truncateAt = null)
    {
        $this->truncateAt = $truncateAt;
    }
    /**
     * Returns a summarized message body.
     */
    public function summarize(\TMS\Psr\Http\Message\MessageInterface $message) : ?string
    {
        return $this->truncateAt === null ? \TMS\GuzzleHttp\Psr7\Message::bodySummary($message) : \TMS\GuzzleHttp\Psr7\Message::bodySummary($message, $this->truncateAt);
    }
}
