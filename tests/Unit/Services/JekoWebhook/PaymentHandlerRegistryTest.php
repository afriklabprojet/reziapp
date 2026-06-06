<?php

namespace Tests\Unit\Services\JekoWebhook;

use App\Services\JekoWebhook\BookingPaymentHandler;
use App\Services\JekoWebhook\GenericPaymentHandler;
use App\Services\JekoWebhook\InsurancePaymentHandler;
use App\Services\JekoWebhook\PaymentHandlerRegistry;
use App\Services\JekoWebhook\SponsoredListingPaymentHandler;
use App\Services\JekoWebhook\SubscriptionPaymentHandler;
use App\Services\PaymentService;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentHandlerRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(PaymentService::class, Mockery::mock(PaymentService::class));
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function it_dispatches_sponsored_listing_references(): void
    {
        $handler = app(PaymentHandlerRegistry::class)->forReference('REZI-SP-42-ABCDEFGH');

        $this->assertInstanceOf(SponsoredListingPaymentHandler::class, $handler);
    }

    #[Test]
    public function it_dispatches_booking_references(): void
    {
        $handler = app(PaymentHandlerRegistry::class)->forReference('REZI-BK-42-ABCDEFGH');

        $this->assertInstanceOf(BookingPaymentHandler::class, $handler);
    }

    #[Test]
    public function it_dispatches_subscription_references(): void
    {
        $handler = app(PaymentHandlerRegistry::class)->forReference('REZI-SUB-42-ABCDEFGH');

        $this->assertInstanceOf(SubscriptionPaymentHandler::class, $handler);
    }

    #[Test]
    public function it_dispatches_legacy_subscription_references(): void
    {
        $handler = app(PaymentHandlerRegistry::class)->forReference('SUB-42-ABCDEFGH');

        $this->assertInstanceOf(SubscriptionPaymentHandler::class, $handler);
    }

    #[Test]
    public function it_dispatches_insurance_references(): void
    {
        $handler = app(PaymentHandlerRegistry::class)->forReference('REZI-INS-42-ABCDEFGH');

        $this->assertInstanceOf(InsurancePaymentHandler::class, $handler);
    }

    #[Test]
    public function it_falls_back_to_generic_handler_for_unknown_references(): void
    {
        $handler = app(PaymentHandlerRegistry::class)->forReference('REZI-OTHER-42-ABCDEFGH');

        $this->assertInstanceOf(GenericPaymentHandler::class, $handler);
    }
}
