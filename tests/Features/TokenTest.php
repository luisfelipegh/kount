<?php

namespace Tests\Features;

use PlacetoPay\Kount\KountService;
use PlacetoPay\Kount\Messages\Responses\Token;
use Tests\BaseTestCase;
use Tests\Traits\HasOrderStructure;

class TokenTest extends BaseTestCase
{
    use HasOrderStructure;

    /**
     * @test
     */
    public function itCanCreateOrder(): void
    {
        $token = $this->service()->token();

        $this->assertInstanceOf(Token::class, $token);
    }
}
