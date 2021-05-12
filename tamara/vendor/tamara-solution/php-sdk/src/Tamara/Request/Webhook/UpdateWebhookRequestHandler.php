<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Webhook;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Webhook\UpdateWebhookResponse;
class UpdateWebhookRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const UPDATE_WEBHOOK_ENDPOINT = '/webhooks/%s';
    public function __invoke(\TMS\Tamara\Request\Webhook\UpdateWebhookRequest $request)
    {
        $response = $this->httpClient->put(\sprintf(self::UPDATE_WEBHOOK_ENDPOINT, $request->getWebhookId()), $request->toArray());
        return new \TMS\Tamara\Response\Webhook\UpdateWebhookResponse($response);
    }
}
