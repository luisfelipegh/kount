<?php

namespace PlacetoPay\Kount\Messages\Requests;

use PlacetoPay\Kount\Constants\PaymentTypes;
use PlacetoPay\Kount\Helpers\AmountHelper;
use PlacetoPay\Kount\Helpers\ArrayHelper;

class CreateOrder extends Base
{
    public function body(): array
    {
        $this->requestData = [
            'merchantOrderId' => $this->data['payment']['reference'] ?? null,
            'channel' => $this->channel,
            'merchantCategoryCode' => $this->data['merchantCategoryCode'] ?? null,
            'deviceSessionId' => $this->data['kountSessionId'] ?? null,
            'creationDateTime' => $this->data['payment']['created_at'] ?? null,
            'items' => [],
        ];

        if (isset($this->data['ipAddress'])) {
            $this->requestData['userIp'] = $this->data['ipAddress'];
        }

        $this->setAccountInformation();
        $this->setItemsInformation();
        $this->setTransactionInformation();
        $this->setPromotions();
        $this->setLoyalty();
        $this->setShippingInformation();
        $this->setCustomAttributes();

        return ArrayHelper::filterValues($this->requestData);
    }

    private function setAccountInformation(): void
    {
        if (isset($this->data['account'])) {
            $this->requestData['account'] = [
                'id' => $this->data['account']['id'] ?? null,
                'type' => $this->data['account']['type'] ?? null,
                'creationDateTime' => $this->data['account']['creationDateTime'] ?? null,
                'username' => $this->data['account']['username'] ?? null,
                'accountIsActive' => isset($this->data['account']['accountIsActive']) ? filter_var($this->data['account']['accountIsActive'], FILTER_VALIDATE_BOOLEAN) : null,
            ];
        }
    }

    private function setItemsInformation(): void
    {
        foreach ($this->data['payment']['items'] ?? [] as $item) {
            $this->requestData['items'][] = [
                'id' => $item['id'] ?? null,
                'price' => isset($item['price']) && $this->getCurrency() ? AmountHelper::parseAmount($item['price'], $this->getCurrency(), $this->amountIsDecimal()) : null,
                'description' => $item['description'] ?? null,
                'quantity' => $item['qty'] ?? null,
                'sku' => $item['sku'] ?? null,
                'category' => $item['category'] ?? null,
                'isDigital' => isset($item['category']) ? $item['category'] == 'digital' : ($item['isDigital'] ?? null),
                'subCategory' => $item['subCategory'] ?? null,
                'upc' => $item['upc'] ?? null,
                'brand' => $item['brand'] ?? null,
                'url' => $item['url'] ?? null,
                'imageUrl' => $item['imageUrl'] ?? null,
                'physicalAttributes' => [
                    'color' => $item['attributes']['color'] ?? null,
                    'size' => $item['attributes']['size'] ?? null,
                    'weight' => $item['attributes']['weight'] ?? null,
                    'height' => $item['attributes']['height'] ?? null,
                    'width' => $item['attributes']['width'] ?? null,
                    'depth' => $item['attributes']['depth'] ?? null,
                ],
                'descriptors' => $item['descriptors'] ?? null,
                'isService' => $item['isService'] ?? null,
            ];
        }
    }

    private function setTransactionInformation(): void
    {
        $transaction = [
            'processor' => $this->data['transaction']['processor'] ?? null,
            'processorMerchantId' => $this->data['transaction']['id'] ?? null,
            'payment' => [
                'type' => $this->data['instrument']['type'] ?? null,
            ],
            'subtotal' => isset($this->data['payment']['amount']['total']) && $this->getCurrency() ? AmountHelper::parseAmount($this->data['payment']['amount']['subtotal'] ?? null, $this->getCurrency(), $this->amountIsDecimal()) : null,
            'orderTotal' => isset($this->data['payment']['amount']['total']) && $this->getCurrency() ? AmountHelper::parseAmount($this->data['payment']['amount']['total'], $this->getCurrency(), $this->amountIsDecimal()) : null,
            'currency' => $this->getCurrency(),
            'merchantTransactionId' => $this->data['payment']['reference'] ?? null,
        ];

        if (isset($this->data['payment']['amount']['tax'])) {
            $transaction['tax'] = [
                'isTaxable' => true,
            ];

            if (isset($this->data['payment']['amount']['tax']['total'])) {
                $transaction['tax']['taxAmount'] = AmountHelper::parseAmount($this->data['payment']['amount']['tax']['total'], $this->getCurrency(), $this->amountIsDecimal());
            }

            if (isset($this->data['payment']['amount']['tax']['country'])) {
                $transaction['tax']['taxableCountryCode'] = $this->data['payment']['amount']['tax']['country'];
            }

            if (isset($this->data['payment']['amount']['tax']['outOfStateTotal'])) {
                $transaction['tax']['outOfStateTotal'] = AmountHelper::parseAmount($this->data['payment']['amount']['tax']['outOfStateTotal'], $this->getCurrency(), $this->amountIsDecimal());
            }
        }

        if (isset($transaction['payment']['type']) && PaymentTypes::TOKEN == $transaction['payment']['type']) {
            $transaction['payment']['paymentToken'] = $this->data['instrument']['card']['pan_hash'] ?? null;
        }

        if (isset($transaction['payment']['type']) && PaymentTypes::CARD == $transaction['payment']['type']) {
            $transaction['payment']['bin'] = $this->data['instrument']['card']['bin'] ?? null;
            $transaction['payment']['last4'] = $this->data['instrument']['card']['last_4'] ?? null;
            $transaction['payment']['cardBrand'] = $this->data['instrument']['card']['card_brand'] ?? null;
        }

        if (isset($this->data['payer'])) {
            $transaction['billedPerson'] = $this->getPerson('payer');
        }

        if ($this->requestData['items']) {
            $transaction['items'] = $this->getResumeOfItems();
        }

        if (PaymentTypes::NONE != $transaction['payment']['type']) {
            $transaction['payment'] = [
                'type' => $this->data['instrument']['type'] ?? null,
                'paymentToken' => $this->data['instrument']['card']['pan_hash'] ?? null,
                'bin' => $this->data['instrument']['card']['bin'] ?? null,
                'last4' => $this->data['instrument']['card']['last_4'] ?? null,
                'cardBrand' => $this->data['instrument']['card']['card_brand'] ?? null,
            ];
        }

        $transaction['transactionStatus'] = $this->data['transaction']['status'] ?? null;

        $transaction['authorizationStatus'] = [
            'authResult' => $this->data['transaction']['authResult'] ?? null,
        ];

        if (isset($this->data['transaction']['updated_at'])) {
            $transaction['authorizationStatus']['dateTime'] = $this->data['transaction']['updated_at'];
        }

        if (isset($this->data['transaction']['verification'])) {
            if (isset($this->data['transaction']['verification']['cvvStatus'])) {
                $transaction['authorizationStatus']['verificationResponse']['cvvStatus'] = $this->data['transaction']['verification']['cvvStatus'];
            }

            if (isset($this->data['transaction']['verification']['avsStatus'])) {
                $transaction['authorizationStatus']['verificationResponse']['avsStatus'] = $this->data['transaction']['verification']['avsStatus'];
            }
        }

        if (isset($this->data['transaction']['declineCode'])) {
            $transaction['authorizationStatus']['declineCode'] = $this->data['transaction']['declineCode'];
        }
        if (isset($this->data['transaction']['processorAuthCode'])) {
            $transaction['authorizationStatus']['processorAuthCode'] = $this->data['transaction']['processorAuthCode'];
        }
        if (isset($this->data['transaction']['processorTransactionId'])) {
            $transaction['authorizationStatus']['processorTransactionId'] = $this->data['transaction']['processorTransactionId'];
        }
        if (isset($this->data['transaction']['acquirerReferenceNumber'])) {
            $transaction['authorizationStatus']['acquirerReferenceNumber'] = $this->data['transaction']['acquirerReferenceNumber'];
        }

        $this->requestData['transactions'][] = $transaction;
    }

    protected function getPerson(string $type): array
    {
        return [
            'name' => [
                'first' => $this->data[$type]['name'] ?? null,
                'family' => $this->data[$type]['surname'] ?? null,
                'preferred' => $this->data[$type]['preferred'] ?? null,
                'middle' => $this->data[$type]['middle'] ?? null,
                'prefix' => $this->data[$type]['prefix'] ?? null,
                'suffix' => $this->data[$type]['suffix'] ?? null,
            ],
            'emailAddress' => $this->data[$type]['email'] ?? null,
            'phoneNumber' => $this->data[$type]['mobile'] ?? null,
            'dateOfBirth' => $this->data[$type]['dateOfBirth'] ?? null,
            'address' => [
                'line1' => $this->data[$type]['address']['street'] ?? null,
                'line2' => $this->data[$type]['address']['street2'] ?? null,
                'city' => $this->data[$type]['address']['city'] ?? null,
                'region' => $this->data[$type]['address']['state'] ?? null,
                'countryCode' => $this->data[$type]['address']['country'] ?? null,
                'postalCode' => $this->data[$type]['address']['postalCode'] ?? null,
            ],
        ];
    }

    private function setPromotions(): void
    {
        if (!isset($this->data['promotions'])) {
            return;
        }
        $promotions = [];

        foreach ($this->data['promotions'] as $promotion) {
            $newPromotion = [];
            $newPromotion['id'] = $promotion['id'] ?? null;
            $newPromotion['description'] = $promotion['description'] ?? null;
            $newPromotion['status'] = $promotion['status'] ?? null;
            $newPromotion['statusReason'] = $promotion['statusReason'] ?? null;

            if (isset($promotion['discount'])) {
                $discountCurrency = $promotion['discount']['currency'] ?? $this->getCurrency() ?? null;

                $newPromotion['discount'] = [
                    'percentage' => $promotion['discount']['percentage'] ?? null,
                    'amount' => isset($promotion['discount']['amount']) && $discountCurrency ? AmountHelper::parseAmount($promotion['discount']['amount'], $discountCurrency, $this->amountIsDecimal()) : null,
                    'currency' => $discountCurrency,
                ];
            }

            if (isset($promotion['credit'])) {
                $creditCurrency = $promotion['credit']['currency'] ?? $this->getCurrency() ?? null;

                $newPromotion['credit'] = [
                    'creditType' => $promotion['credit']['creditType'] ?? null,
                    'amount' => isset($promotion['credit']['amount']) && $creditCurrency ? AmountHelper::parseAmount($promotion['credit']['amount'], $creditCurrency, $this->amountIsDecimal()) : null,
                    'currency' => $creditCurrency,
                ];
            }

            $promotions[] = $newPromotion;
        }

        $this->requestData['promotions'] = $promotions;
    }

    private function setLoyalty(): void
    {
        if (!isset($this->data['loyalty'])) {
            return;
        }

        $currency = $this->data['loyalty']['credit']['currency'] ?? $this->getCurrency() ?? '';

        $this->requestData['loyalty'] = [
            'id' => $this->data['loyalty']['id'] ?? null,
            'description' => $this->data['loyalty']['description'] ?? null,
            'credit' => [
                'creditType' => $this->data['loyalty']['credit']['creditType'] ?? null,
                'amount' => AmountHelper::parseAmount($this->data['loyalty']['credit']['amount'], $currency, $this->amountIsDecimal()),
                'currency' => $currency,
            ],
        ];
    }

    protected function amountIsDecimal(): bool
    {
        return $this->data['payment']['amount']['isDecimal'] ?? true;
    }

    private function setShippingInformation(): void
    {
        $fulfillment = [
            'type' => $this->data['shipping']['type'] ?? '',
            'status' => $this->data['shipping']['status'] ?? null,
            'accessUrl' => $this->data['shipping']['accessUrl'] ?? null,
            'downloadDeviceIp' => $this->data['shipping']['downloadDeviceIp'] ?? null,
            'merchantFulfillmentId' => $this->data['shipping']['merchantFulfillmentId'] ?? null,
            'recipientPerson' => $this->getPerson('shipping'),
        ];

        if (isset($this->data['shipping']['delivery'])) {
            $fulfillment['shipping']['amount'] =
                isset($this->data['shipping']['delivery']['amount']) && $this->getCurrency() ?
                    AmountHelper::parseAmount($this->data['shipping']['delivery']['amount'], $this->getCurrency(), $this->amountIsDecimal())
                    : null;
            $fulfillment['shipping']['provider'] = $this->data['shipping']['delivery']['provider'] ?? null;
            $fulfillment['shipping']['trackingNumber'] = $this->data['shipping']['delivery']['trackingNumber'] ?? null;
            $fulfillment['shipping']['method'] = $this->data['shipping']['delivery']['method'] ?? null;
        }

        if ($this->requestData['items']) {
            $fulfillment['items'] = $this->getResumeOfItems();
        }

        if (isset($this->data['shipping']['digitalDownloaded'])) {
            $fulfillment['digitalDownloaded'] = filter_var($this->data['shipping']['digitalDownloaded'], FILTER_VALIDATE_BOOLEAN);
        }

        if (isset($this->data['store'])) {
            $fulfillment['store'] = [
                'id' => $this->data['store']['id'] ?? null,
                'name' => $this->data['store']['name'] ?? null,
            ];

            if (isset($this->data['store']['address'])) {
                $fulfillment['store']['address'] = [
                    'line1' => $this->data['store']['address']['street'] ?? null,
                    'line2' => $this->data['store']['address']['street2'] ?? null,
                    'city' => $this->data['store']['address']['city'] ?? null,
                    'region' => $this->data['store']['address']['state'] ?? null,
                    'countryCode' => $this->data['store']['address']['country'] ?? null,
                    'postalCode' => $this->data['store']['address']['postalCode'] ?? null,
                ];
            }
        }

        $this->requestData['fulfillment'][] = $fulfillment;
    }

    protected function getCurrency(): ?string
    {
        return $this->data['payment']['amount']['currency'] ?? null;
    }

    protected function getResumeOfItems(): array
    {
        return array_map(function ($item) {
            return array_intersect_key($item, array_flip(['id', 'quantity']));
        }, $this->requestData['items']);
    }

    private function setCustomAttributes(): void
    {
        if (!isset($this->data['additional'])) {
            return;
        }

        foreach ($this->data['additional'] as $key => $value) {
            $this->requestData['customFields'][$key] = $value;
        }
    }
}
