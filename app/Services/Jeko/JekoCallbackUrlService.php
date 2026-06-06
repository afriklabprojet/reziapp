<?php

namespace App\Services\Jeko;

use App\Models\Booking;
use App\Models\SponsoredListing;
use Illuminate\Support\Facades\URL;

class JekoCallbackUrlService
{
    protected string $callbackBaseUrl;

    public function __construct(?string $callbackBaseUrl = null)
    {
        $this->callbackBaseUrl = $callbackBaseUrl ?? config('services.jeko.callback_base_url') ?? config('app.url') ?? '';
    }

    public function sponsoredSuccessUrl(SponsoredListing $sponsored, string $reference): string
    {
        return $this->formatSignedCallbackUrl(
            URL::temporarySignedRoute(
                'payment.jeko.success',
                now()->addDay(),
                [
                    'sponsored_id' => $sponsored->getKey(),
                    'reference' => $reference,
                ],
                absolute: false,
            ),
        );
    }

    public function sponsoredErrorUrl(SponsoredListing $sponsored, string $reference): string
    {
        return $this->formatSignedCallbackUrl(
            URL::temporarySignedRoute(
                'payment.jeko.error',
                now()->addDay(),
                [
                    'sponsored_id' => $sponsored->getKey(),
                    'reference' => $reference,
                ],
                absolute: false,
            ),
        );
    }

    public function sponsoredCheckUrl(SponsoredListing $sponsored): string
    {
        return $this->formatSignedCallbackUrl(
            URL::temporarySignedRoute(
                'payment.jeko.check',
                now()->addDay(),
                [
                    'sponsored' => $sponsored->getKey(),
                    'reference' => $sponsored->jeko_reference,
                ],
                absolute: false,
            ),
        );
    }

    public function bookingSuccessUrl(Booking $booking): string
    {
        return $this->formatPath('/bookings/payment/success?booking='.$booking->uuid);
    }

    public function bookingErrorUrl(Booking $booking): string
    {
        return $this->formatPath('/bookings/payment/error?booking='.$booking->uuid);
    }

    public function subscriptionSuccessUrl(string $reference): string
    {
        return $this->formatPath('/owner/subscriptions/payment/success?reference='.$reference);
    }

    public function subscriptionErrorUrl(string $reference): string
    {
        return $this->formatPath('/owner/subscriptions/payment/error?reference='.$reference);
    }

    public function insuranceSuccessUrl(string $reference): string
    {
        return $this->formatPath('/insurance/payment/success?reference='.$reference);
    }

    public function insuranceErrorUrl(string $reference): string
    {
        return $this->formatPath('/insurance/payment/error?reference='.$reference);
    }

    protected function formatSignedCallbackUrl(string $relativeSignedUrl): string
    {
        if ($this->callbackBaseUrl) {
            return rtrim($this->callbackBaseUrl, '/').$relativeSignedUrl;
        }

        return URL::to($relativeSignedUrl);
    }

    protected function formatPath(string $path): string
    {
        if ($this->callbackBaseUrl) {
            return rtrim($this->callbackBaseUrl, '/').'/'.ltrim($path, '/');
        }

        return URL::to($path);
    }
}
