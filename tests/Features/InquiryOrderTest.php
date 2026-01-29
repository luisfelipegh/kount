<?php

namespace Tests\Features;

use PlacetoPay\Kount\Helpers\MockClient;
use PlacetoPay\Kount\Messages\Responses\InquiryOrder;
use Tests\BaseTestCase;
use Tests\Traits\HasOrderStructure;

class InquiryOrderTest extends BaseTestCase
{
    use HasOrderStructure;

    /**
     * @test
     */
    public function itCanInquiryAnOrder(): void
    {
        $request = $this->getOrderRequestStructure();

        $response = $this->service()->inquiryOrder(MockClient::VALID_API_TOKEN, $request);

        $this->assertInstanceOf(InquiryOrder::class, $response);

        $this->assertTrue($response->successful());
    }
}
