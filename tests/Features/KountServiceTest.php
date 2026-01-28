<?php

namespace Tests\Features;

use PlacetoPay\Kount\Exceptions\KountServiceException;
use PlacetoPay\Kount\KountService;
use Tests\BaseTestCase;

class KountServiceTest extends BaseTestCase
{
    /** @test */
    public function itCanInstantiateTheService(): void
    {
        $this->assertInstanceOf(KountService::class, new KountService([
            'apiKey' => 'testingValues',
            'merchant' => 'testingValues',
            'website' => 'testingValues',
            'sandbox' => true,
        ]));
    }

    /** @test */
    public function itCannotInstantiateTheServiceMissingApiKey(): void
    {
        $this->expectException(KountServiceException::class);
        $this->expectExceptionMessage('Values for apiKey, website or merchant has to be provided');

        $this->assertInstanceOf(KountService::class, new KountService([
            'merchant' => 'testingValues',
            'website' => 'testingValues',
            'sandbox' => true,
        ]));
    }

    /** @test */
    public function itCannotInstantiateTheServiceMissingMerchant(): void
    {
        $this->expectException(KountServiceException::class);
        $this->expectExceptionMessage('Values for apiKey, website or merchant has to be provided');

        $this->assertInstanceOf(KountService::class, new KountService([
            'apiKey' => 'testingValues',
            'website' => 'testingValues',
            'sandbox' => true,
        ]));
    }

    /** @test */
    public function itCannotInstantiateTheServiceMissingWebsite(): void
    {
        $this->expectException(KountServiceException::class);
        $this->expectExceptionMessage('Values for apiKey, website or merchant has to be provided');

        $this->assertInstanceOf(KountService::class, new KountService([
            'apiKey' => 'testingValues',
            'merchant' => 'testingValues',
            'sandbox' => true,
        ]));
    }
}
