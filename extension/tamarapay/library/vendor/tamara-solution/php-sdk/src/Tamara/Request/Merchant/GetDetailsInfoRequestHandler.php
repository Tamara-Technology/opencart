<?php

declare(strict_types=1);

namespace TMS\Tamara\Request\Merchant;

use TMS\Tamara\Request\AbstractRequestHandler;
use TMS\Tamara\Response\Merchant\GetDetailsInfoResponse;

class GetDetailsInfoRequestHandler extends AbstractRequestHandler
{
    private const MERCHANT_PROFILE_ENDPOINT = '/merchants/configs';

    public function __invoke(GetDetailsInfoRequest $request)
    {
        $response = $this->httpClient->get(
            self::MERCHANT_PROFILE_ENDPOINT,
            []
        );

        return new GetDetailsInfoResponse($response);
    }
}
