<?php

namespace Tests\Features;

use Tests\BaseTestCase;
use Tests\Traits\HasOrderStructure;

class CreateOrderTest extends BaseTestCase
{
    use HasOrderStructure;

    /**
     * @test
     */
    public function itCanCreateOrder(): void
    {
        $request = $this->getOrderRequestStructure();

        $this->service()->

        $this->assertTrue(true);
    }
}
