<?php

namespace Tests\Features;

use PlacetoPay\Kount\Helpers\MockClient;
use PlacetoPay\Kount\Messages\Responses\GetOrder;
use Tests\BaseTestCase;
use Tests\Traits\HasOrderStructure;

class GetOrderTest extends BaseTestCase
{
    use HasOrderStructure;

    /**
     * @test
     */
    public function itCanInquiryAnOrder(): void
    {
        $response = $this->service()->getOrder(MockClient::VALID_API_TOKEN, 'ORDER12345');

        $this->assertInstanceOf(GetOrder::class, $response);

        $this->assertTrue($response->successful());
    }
}
