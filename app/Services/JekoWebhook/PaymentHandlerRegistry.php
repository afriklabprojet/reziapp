<?php

declare(strict_types=1);

namespace App\Services\JekoWebhook;

final class PaymentHandlerRegistry
{
    /**
     * @var array<int, JekoPaymentHandler>
     */
    private array $handlers;

    public function __construct(
        SponsoredListingPaymentHandler $sponsoredListingPaymentHandler,
        BookingPaymentHandler $bookingPaymentHandler,
        SubscriptionPaymentHandler $subscriptionPaymentHandler,
        InsurancePaymentHandler $insurancePaymentHandler,
        GenericPaymentHandler $genericPaymentHandler,
    ) {
        $this->handlers = [
            $sponsoredListingPaymentHandler,
            $bookingPaymentHandler,
            $subscriptionPaymentHandler,
            $insurancePaymentHandler,
            $genericPaymentHandler,
        ];
    }

    public function forReference(string $reference): JekoPaymentHandler
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($reference)) {
                return $handler;
            }
        }

        return $this->handlers[array_key_last($this->handlers)];
    }
}
