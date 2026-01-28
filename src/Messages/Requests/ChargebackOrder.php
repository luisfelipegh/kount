<?php

namespace PlacetoPay\Kount\Messages\Requests;

use PlacetoPay\Kount\Helpers\AmountHelper;

class ChargebackOrder extends BaseOrder
{
    public function method(): string
    {
        return 'PATCH';
    }

    public function body(): array
    {
        $reversalUpdate = [
            'orderId' => $this->data['orderId'] ?? null,
            'fraudReportType' => $this->data['fraudReportType'] ?? null,
        ];

        if (isset($this->data['chargeback'])) {
            $reversalUpdate['chargeback'] = [
                'isChargeback' => true,
                'transactionId' => $this->data['chargeback']['transactionId'] ?? null,
                'reasonCode' => $this->data['chargeback']['reasonCode'] ?? null,
                'cardType' => $this->data['chargeback']['cardType'] ?? null,
            ];
        }

        if (isset($this->data['refund'])) {
            $reversalUpdate['refund'] = [
                'isRefund' => true,
                'transactionId' => $this->data['refund']['transactionId'] ?? null,
                'dateTime' => $this->data['refund']['date'] ?? null,
                'amount' => isset($this->data['refund']['amount']['total'], $this->data['refund']['amount']['currency']) ?
                    AmountHelper::parseAmount(
                        $this->data['refund']['amount']['total'],
                        $this->data['refund']['amount']['currency'],
                        $this->data['refund']['amount']['isDecimal'] ?? true
                    ) : null,
                'currency' => $this->data['refund']['amount']['currency'] ?? null,
                'gatewayReceipt' => $this->data['refund']['receipt'] ?? null,
            ];
        }

        $this['reversalsUpdates'][] = $reversalUpdate;

        return $this->array_filter_recursive($this->requestData);
    }

    public function url(): string
    {
        return parent::url() . ':batchUpdateReversals';
    }
}
