<?php

namespace Tests\Features;

use PlacetoPay\Kount\Helpers\MockClient;
use PlacetoPay\Kount\Messages\Responses\Token;
use Tests\BaseTestCase;
use Tests\Traits\HasOrderStructure;

class TokenTest extends BaseTestCase
{
    use HasOrderStructure;

    /**
     * @test
     */
    public function itCanAuthenticateInKount(): void
    {
        $token = $this->service([
            'apiKey' => MockClient::VALID_API_KEY,
        ])->token();

        $this->assertInstanceOf(Token::class, $token);

        $this->assertEquals($token->accessToken(), MockClient::VALID_API_TOKEN);
        $this->assertTrue($token->successful());
        $this->assertEquals(200, $token->status());
    }

    /**
     * @test
     */
    public function itCannotAuthenticate(): void
    {
        $token = $this->service([
            'apiKey' => MockClient::INVALID_API_KEY,
        ])->token();

        $this->assertInstanceOf(Token::class, $token);

        $this->assertEquals($token->accessToken(), MockClient::INVALID_API_TOKEN);
        $this->assertTrue($token->successful());
        $this->assertEquals(200, $token->status());
    }

    /**
     * @test
     */
    public function itCannotAuthenticateInvalidApiKey(): void
    {
        $response = $this->service()->token();

        $this->assertFalse($response->successful());
        $this->assertEquals(401, $response->status());
    }
}
