<?php

namespace PlacetoPay\Kount\Messages\Requests;

use PlacetoPay\Kount\Exceptions\KountServiceException;

class UpdateOrder extends BaseOrder
{
    public function method(): string
    {
        return 'PATCH';
    }

    public function body(): array
    {
        $this->requestData['merchantOrderId'] = $this->data['payment']['reference'] ?? null;
        $this->requestData['deviceSessionId'] = $this->data['kountSessionId'] ?? null;

        if (isset($this->data['riskInquiry'])) {
            $this->requestData['riskInquiry'] = [
                'decision' => $this->data['riskInquiry']['decision'] ?? null,
                'reasonCode' => $this->data['riskInquiry']['reasonCode'] ?? null,
            ];
        }

        return $this->requestData;
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
