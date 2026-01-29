<?php

namespace Tests\Features;

use PlacetoPay\Kount\Constants\FraudReportTypes;
use PlacetoPay\Kount\Helpers\MockClient;
use PlacetoPay\Kount\Messages\Responses\ChargebackOrder;
use Tests\BaseTestCase;
use Tests\Traits\HasOrderStructure;

class ChargebackOrderTest extends BaseTestCase
{
    use HasOrderStructure;

    /**
     * @test
     */
    public function itCanNotifyChargebackAnOrder(): void
    {
        $request = [
            'orderId' => 'ORDER12345',
            'fraudReportType' => FraudReportTypes::OTHER,
            'chargebacks' => [
                'transactionId' => uniqid(),
                'reasonCode' => '01',
                'cardType' => 'VISA',
            ],
        ];

        $response = $this->service()->notifyChargeback(MockClient::VALID_API_TOKEN, $request);

        $this->assertInstanceOf(ChargebackOrder::class, $response);

        $this->assertTrue($response->successful());
    }
}
