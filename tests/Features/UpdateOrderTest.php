<?php

namespace Tests\Features;

use PlacetoPay\Kount\Constants\DecisionCodes;
use PlacetoPay\Kount\Helpers\MockClient;
use PlacetoPay\Kount\Messages\Responses\UpdateOrder;
use Tests\BaseTestCase;
use Tests\Traits\HasOrderStructure;

class UpdateOrderTest extends BaseTestCase
{
    use HasOrderStructure;

    /**
     * @test
     */
    public function itCanInquiryAnOrder(): void
    {
        $request = [
            'orderId' => 'ORDER12345',
            'payment' => [
                'reference' => 'ORDER12345',
            ],
            'kountSessionId' => 'SESSION12345',
            'riskInquiry' => [
                'decision' => DecisionCodes::APPROVE,
                'reasonCode' => DecisionCodes::APPROVE,
            ],
        ];

        $response = $this->service()->updateOrder(MockClient::VALID_API_TOKEN, $request);

        $this->assertInstanceOf(UpdateOrder::class, $response);

        $this->assertTrue($response->successful());
    }
}
