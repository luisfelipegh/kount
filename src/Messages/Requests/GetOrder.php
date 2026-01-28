<?php

namespace PlacetoPay\Kount\Messages\Requests;

use PlacetoPay\Kount\Exceptions\KountServiceException;

class GetOrder extends BaseOrder
{
    public function method(): string
    {
        return 'GET';
    }

    public function body(): array
    {
        return [];
    }

    /**
     * @throws KountServiceException
     */
    public function url(): string
    {
        if (!isset($this->data['orderId'])) {
            throw new KountServiceException('The orderId is required to update an order.');
        }

        return parent::url() . '/' . $this->data['orderId'];
    }
}
