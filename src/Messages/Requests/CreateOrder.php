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
            'deviceSessionId' => $this->data['instrument']['kount']['session'] ?? null,
            'creationDateTime' => $this->data['date'] ?? null,
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
        $this->setMerchant();
        $this->setAdvertising();
        $this->setLinks();

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
                'price' => isset($item['price']) && $this->getCurrency() ? AmountHelper::parseAmount($item['price'], $this->getCurrency(), $this->amountInMinorUnit()) : null,
                'description' => $item['description'] ?? null,
                'quantity' => $item['qty'] ?? null,
                'sku' => $item['sku'] ?? null,
                'category' => $item['category'] ?? null,
                'isDigital' => isset($item['additional']['category']) ? $item['additional']['category'] == 'digital' : ($item['additional']['isDigital'] ?? null),
                'subCategory' => $item['additional']['subCategory'] ?? null,
                'upc' => $item['additional']['upc'] ?? null,
                'brand' => $item['additional']['brand'] ?? null,
                'url' => $item['additional']['url'] ?? null,
                'imageUrl' => $item['additional']['imageUrl'] ?? null,
                'physicalAttributes' => [
                    'color' => $item['additional']['attributes']['color'] ?? null,
                    'size' => $item['additional']['attributes']['size'] ?? null,
                    'weight' => $item['additional']['attributes']['weight'] ?? null,
                    'height' => $item['additional']['attributes']['height'] ?? null,
                    'width' => $item['additional']['attributes']['width'] ?? null,
                    'depth' => $item['additional']['attributes']['depth'] ?? null,
                ],
                'descriptors' => $item['additional']['descriptors'] ?? null,
                'isService' => $item['additional']['isService'] ?? null,
            ];
        }
    }

    private function setTransactionInformation(): void
    {
        $subtotal = array_values(array_filter($this->data['payment']['amount']['details'] ?? [], function ($value) {
            return isset($value['kind']) && $value['kind'] === 'subtotal';
        }))[0] ?? [];

        $transaction = [
            'processor' => $this->data['transaction']['processor'] ?? null,
            'processorMerchantId' => $this->data['transaction']['processorId'] ?? null,
            'subtotal' => isset($subtotal['amount']) && $this->getCurrency() ? AmountHelper::parseAmount($subtotal['amount'], $this->getCurrency(), $this->amountInMinorUnit()) : null,
            'orderTotal' => isset($this->data['payment']['amount']['total']) && $this->getCurrency() ? AmountHelper::parseAmount($this->data['payment']['amount']['total'], $this->getCurrency(), $this->amountInMinorUnit()) : null,
            'currency' => $this->getCurrency(),
            'merchantTransactionId' => $this->data['payment']['reference'] ?? null,
        ];

        if (isset($this->data['payment']['amount']['taxes'])) {
            $transaction['tax'] = [
                'isTaxable' => true,
            ];

            $total = 0;
            $outOfStateTotal = 0;

            foreach ($this->data['payment']['amount']['taxes'] as $tax) {
                $total += $tax['amount'] ?? 0;
                if (isset($tax['kind']) && $tax['kind'] === 'stateTax') {
                    $outOfStateTotal += $tax['amount'];
                }
            }

            if ($total) {
                $transaction['tax']['taxAmount'] = AmountHelper::parseAmount($total, $this->getCurrency(), $this->amountInMinorUnit());
            }

            if (isset($this->data['payment']['amount']['taxCountry'])) {
                $transaction['tax']['taxableCountryCode'] = $this->data['payment']['amount']['taxCountry'];
            }

            if ($outOfStateTotal) {
                $transaction['tax']['outOfStateTotal'] = AmountHelper::parseAmount($outOfStateTotal, $this->getCurrency(), $this->amountInMinorUnit());
            }
        }

        if (isset($this->data['payer'])) {
            $transaction['billedPerson'] = $this->getPerson('payer');
        }

        if ($this->requestData['items']) {
            $transaction['items'] = $this->getResumeOfItems();
        }

        $transaction['payment']['type'] = $this->data['instrument']['type'] ?? null;

        if (isset($this->data['instrument']['card'])) {
            $transaction['payment']['type'] = $transaction['payment']['type'] ?: PaymentTypes::CARD;
            $transaction['payment']['bin'] = $this->data['instrument']['card']['bin'] ?? null;
            $transaction['payment']['last4'] = $this->data['instrument']['card']['last4'] ?? null;
            $transaction['payment']['cardBrand'] = $this->data['instrument']['card']['cardBrand'] ?? null;
        } elseif (isset($this->data['instrument']['token'])) {
            $transaction['payment']['type'] = $transaction['payment']['type'] ?: PaymentTypes::TOKEN;
            $transaction['payment']['paymentToken'] = $this->data['instrument']['token']['token'] ?? null;
        } else {
            $transaction['payment']['type'] = $transaction['payment']['type'] ?: PaymentTypes::NONE;
        }

        $transaction['transactionStatus'] = $this->data['transaction']['status'] ?? null;

        $transaction['authorizationStatus'] = [
            'authResult' => $this->data['transaction']['authResult'] ?? null,
            'dateTime' => $this->data['transaction']['date'] ?? null,
            'declineCode' => $this->data['transaction']['declineCode'] ?? null,
            'processorAuthCode' => $this->data['transaction']['authorization'] ?? null,
            'processorTransactionId' => $this->data['transaction']['processorId'] ?? null,
            'acquirerReferenceNumber' => $this->data['transaction']['receipt'] ?? null,
        ];

        if (isset($this->data['transaction']['verification'])) {
            $transaction['authorizationStatus']['verificationResponse']['cvvStatus'] = $this->data['transaction']['verification']['cvvStatus'] ?? null;
            $transaction['authorizationStatus']['verificationResponse']['avsStatus'] = $this->data['transaction']['verification']['avsStatus'] ?? null;
        }

        $this->requestData['transactions'][] = $transaction;
    }

    protected function getPerson(string $type): array
    {
        $array = ArrayHelper::get($this->data, $type);

        return [
            'name' => [
                'first' => $array['name'] ?? null,
                'family' => $array['surname'] ?? null,
                'preferred' => $array['preferred'] ?? null,
                'middle' => $array['middle'] ?? null,
                'prefix' => $array['prefix'] ?? null,
                'suffix' => $array['suffix'] ?? null,
            ],
            'emailAddress' => $array['email'] ?? null,
            'phoneNumber' => $array['mobile'] ?? null,
            'dateOfBirth' => $array['dateOfBirth'] ?? null,
            'address' => [
                'line1' => $array['address']['street'] ?? null,
                'line2' => $array['address']['street2'] ?? null,
                'city' => $array['address']['city'] ?? null,
                'region' => $array['address']['state'] ?? null,
                'countryCode' => $array['address']['country'] ?? null,
                'postalCode' => $array['address']['postalCode'] ?? null,
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
                    'amount' => isset($promotion['discount']['amount']) && $discountCurrency ? AmountHelper::parseAmount($promotion['discount']['amount'], $discountCurrency, $this->amountInMinorUnit()) : null,
                    'currency' => $discountCurrency,
                ];
            }

            if (isset($promotion['credit'])) {
                $creditCurrency = $promotion['credit']['currency'] ?? $this->getCurrency() ?? null;

                $newPromotion['credit'] = [
                    'creditType' => $promotion['credit']['creditType'] ?? null,
                    'amount' => isset($promotion['credit']['amount']) && $creditCurrency ? AmountHelper::parseAmount($promotion['credit']['amount'], $creditCurrency, $this->amountInMinorUnit()) : null,
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
                'amount' => AmountHelper::parseAmount($this->data['loyalty']['credit']['amount'], $currency, $this->amountInMinorUnit()),
                'currency' => $currency,
            ],
        ];
    }

    protected function amountInMinorUnit(): bool
    {
        return $this->data['payment']['amount']['inMinorUnit'] ?? false;
    }

    private function setShippingInformation(): void
    {
        $fulfillment = [
            'type' => $this->data['shipping']['type'] ?? '',
            'status' => $this->data['shipping']['status'] ?? null,
            'accessUrl' => $this->data['shipping']['accessUrl'] ?? null,
            'downloadDeviceIp' => $this->data['shipping']['downloadDeviceIp'] ?? null,
            'merchantFulfillmentId' => $this->data['payment']['reference'] ?? null,
            'recipientPerson' => $this->getPerson('payment.shipping'),
        ];

        $shipping = array_values(
            array_filter(
                ArrayHelper::get($this->data, 'payment.amount.details', []),
                function ($value) {
                    return isset($value['kind']) && $value['kind'] === 'shipping';
                }
            )
        )[0] ?? [];

        $fulfillment['shipping']['amount'] =
            isset($shipping['amount']) && $this->getCurrency() ?
                AmountHelper::parseAmount($shipping['amount'], $this->getCurrency(), $this->amountInMinorUnit())
                : null;

        $fulfillment['shipping']['provider'] = $this->data['shipping']['provider'] ?? null;
        $fulfillment['shipping']['trackingNumber'] = $this->data['shipping']['trackingNumber'] ?? null;
        $fulfillment['shipping']['method'] = $this->data['shipping']['method'] ?? null;

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

    private function setLinks(): void
    {
        if (!isset($this->data['links'])) {
            return;
        }

        $this->requestData['links'] = [
            'viewOrderUrl' => $this->data['links']['viewOrderUrl'] ?? null,
            'requestRefundUrl' => $this->data['links']['requestRefundUrl'] ?? null,
            'buyAgainUrl' => $this->data['links']['buyAgainUrl'] ?? null,
            'writeReviewUrl' => $this->data['links']['writeReviewUrl'] ?? null,
        ];
    }

    private function setAdvertising(): void
    {
        if (!isset($this->data['advertising'])) {
            return;
        }

        $this->requestData['advertising'] = [
            'channel' => $this->data['advertising']['channel'] ?? null,
            'affiliate' => $this->data['advertising']['affiliate'] ?? null,
            'subAffiliate' => $this->data['advertising']['subAffiliate'] ?? null,
            'writeReviewUrl' => $this->data['advertising']['writeReviewUrl'] ?? null,
            'events' => $this->data['advertising']['events'] ?? [],
            'campaign' => isset($this->data['advertising']['campaign']) ? [
                'id' => $this->data['advertising']['campaign']['id'] ?? null,
                'name' => $this->data['advertising']['campaign']['name'] ?? null,
            ] : null,
        ];
    }

    private function setMerchant(): void
    {
        if (!isset($this->data['merchant'])) {
            return;
        }

        $this->requestData['merchantCategoryCode'] = $this->data['merchant']['merchantCategoryCode'] ?? null;

        $this->requestData['merchant'] = [
            'name' => $this->data['merchant']['name'] ?? null,
            'storeName' => $this->data['merchant']['storeName'] ?? null,
            'websiteUrl' => $this->data['merchant']['websiteUrl'] ?? null,
            'id' => $this->data['merchant']['id'] ?? null,
            'contactEmail' => $this->data['merchant']['contactEmail'] ?? null,
            'contactPhoneNumber' => $this->data['merchant']['contactPhoneNumber'] ?? null,
        ];
    }
}
