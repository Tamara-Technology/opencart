<?php

declare (strict_types=1);

namespace TMS\Tamara\Response\Merchant;

use TMS\Tamara\Model\Merchant;
use TMS\Tamara\Response\ClientResponse;

class GetDetailsInfoResponse extends ClientResponse
{
    /**
     * @var Merchant
     */
    private $merchant;

    /**
     * @return Merchant|null
     */
    public function getMerchant(): ?Merchant
    {
        return $this->merchant;
    }

    protected function parse(array $responseData): void
    {
        $this->merchant = Merchant::fromArray($responseData);
    }
}
