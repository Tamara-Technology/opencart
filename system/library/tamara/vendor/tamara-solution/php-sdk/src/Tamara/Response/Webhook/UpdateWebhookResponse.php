<?php

declare (strict_types=1);
namespace TMS\Tamara\Response\Webhook;

use TMS\Tamara\Model\Webhook;
use TMS\Tamara\Response\ClientResponse;
class UpdateWebhookResponse extends \TMS\Tamara\Response\ClientResponse
{
    /**
     * @var string
     */
    private $webhookId;
    /**
     * @var string
     */
    private $url;
    /**
     * @var array
     */
    private $events;
    /**
     * @var array
     */
    private $headers;
    public function getWebhookId() : ?string
    {
        return $this->webhookId;
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
        return $this->headers;
    }
    protected function parse(array $responseData) : void
    {
        $this->webhookId = $responseData[\TMS\Tamara\Model\Webhook::WEBHOOK_ID];
        $this->url = $responseData[\TMS\Tamara\Model\Webhook::URL];
        $this->events = $responseData[\TMS\Tamara\Model\Webhook::EVENTS];
        $this->headers = $responseData[\TMS\Tamara\Model\Webhook::HEADERS];
    }
}
