<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Webhook;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Webhook\RetrieveWebhookResponse;
class RetrieveWebhookRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const RETRIEVE_WEBHOOK_ENDPOINT = '/webhooks/%s';
    public function __invoke(\TMS\Tamara\Request\Webhook\RetrieveWebhookRequest $request)
    {
        $response = $this->httpClient->get(\sprintf(self::RETRIEVE_WEBHOOK_ENDPOINT, $request->getWebhookId()));
        return new \TMS\Tamara\Response\Webhook\RetrieveWebhookResponse($response);
    }
}
