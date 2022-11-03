<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Webhook;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\ClientResponse;
use TMS\Tamara\Response\Webhook\RemoveWebhookResponse;
class RemoveWebhookRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const DELETE_WEBHOOK_ENDPOINT = '/webhooks/%s';
    public function __invoke(\TMS\Tamara\Request\Webhook\RemoveWebhookRequest $request)
    {
        $response = $this->httpClient->delete(\sprintf(self::DELETE_WEBHOOK_ENDPOINT, $request->getWebhookId()));
        return new \TMS\Tamara\Response\Webhook\RemoveWebhookResponse($response);
    }
}
