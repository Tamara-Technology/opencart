<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Webhook;

use TMS\Tamara\Model\Webhook;
class RegisterWebhookRequest
{
    /**
     * @var string
     */
    private $url;
    /**
     * @var array
     */
    private $events;
    /**
     * @var array|null
     */
    private $headers;
    public function __construct(string $url, array $events)
    {
        $this->url = $url;
        $this->events = $events;
    }
    public function getUrl() : string
    {
        return $this->url;
    }
    public function getEvents() : array
    {
        return $this->events;
    }
    public function getHeaders() : array
    {
        return $this->headers ?? [];
    }
    public function addHeaders(string $key, $value) : void
    {
        $this->headers[$key] = $value;
    }
    public function toArray() : array
    {
        return [\TMS\Tamara\Model\Webhook::URL => $this->getUrl(), \TMS\Tamara\Model\Webhook::EVENTS => $this->getEvents(), \TMS\Tamara\Model\Webhook::HEADERS => $this->getHeaders()];
    }
}
