<?php

declare (strict_types=1);
namespace TMS\Tamara\Request\Webhook;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Webhook\RegisterWebhookResponse;
class RegisterWebhookRequestHandler extends \TMS\Tamara\Request\AbstractRequestHandler
{
    private const REGISTER_WEBHOOK_ENDPOINT = '/webhooks';
    public function __invoke(\TMS\Tamara\Request\Webhook\RegisterWebhookRequest $request)
    {
        $response = $this->httpClient->post(self::REGISTER_WEBHOOK_ENDPOINT, $request->toArray());
        return new \TMS\Tamara\Response\Webhook\RegisterWebhookResponse($response);
    }
}
