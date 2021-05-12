<?php

declare (strict_types=1);
namespace TMS\Tamara\Response\Webhook;

use TMS\Tamara\Model\Webhook;
use TMS\Tamara\Response\ClientResponse;
class RegisterWebhookResponse extends \TMS\Tamara\Response\ClientResponse
{
    /**
     * @var string
     */
    private $webhookId;
    public function getWebhookId() : string
    {
        return $this->webhookId;
    }
    protected function parse(array $responseData) : void
    {
        $this->webhookId = $responseData[\TMS\Tamara\Model\Webhook::WEBHOOK_ID];
    }
}
