<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\CancellationPolicy;
use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingReceiptTest extends TestCase
{
    use RefreshDatabase;

    protected User $guest;
    protected Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $policy = CancellationPolicy::create([
            'name'         => 'flexible',
            'display_name' => 'Flexible',
            'description'  => 'Test policy',
            'refund_rules' => [['days_before' => 7, 'refund_percent' => 100]],
            'is_active'    => true,
        ]);

        $owner = User::factory()->create(['role' => 'owner']);

        $this->guest = User::factory()->create(['role' => 'user']);

        $residence = Residence::factory()->create([
            'owner_id'                => $owner->id,
            'cancellation_policy_id'  => $policy->id,
            'status'                  => 'approved',
        ]);

        $this->booking = Booking::factory()->create([
            'uuid'         => (string) Str::uuid(),
            'user_id'      => $this->guest->id,
            'residence_id' => $residence->id,
            'status'       => 'completed',
            'check_in'     => now()->subDays(5)->toDateString(),
            'check_out'    => now()->subDay()->toDateString(),
            'total_amount' => 75000,
        ]);
    }

    #[Test]
    public function authenticated_user_can_download_receipt_for_completed_booking(): void
    {
        $response = $this->actingAs($this->guest)
            ->get(route('bookings.receipt.download', $this->booking));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('receipt-booking-', $contentDisposition);
        $this->assertStringContainsString($this->booking->uuid, $contentDisposition);

        $content = $response->getContent();
        $this->assertStringContainsString($this->booking->uuid, $content);
        $this->assertStringContainsString('REZI', $content);
        $this->assertStringContainsString('75 000', $content);
        $this->assertStringContainsString('FCFA', $content);
    }

    #[Test]
    public function unauthorized_user_cannot_download_receipt(): void
    {
        $otherUser = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($otherUser)
            ->get(route('bookings.receipt.download', $this->booking));

        $response->assertStatus(403);
    }

    #[Test]
    public function receipt_download_returns_404_for_non_completed_booking(): void
    {
        $policy = CancellationPolicy::first();
        $owner  = User::factory()->create(['role' => 'owner']);
        $residence = Residence::factory()->create([
            'owner_id'               => $owner->id,
            'cancellation_policy_id' => $policy->id,
            'status'                 => 'approved',
        ]);

        $confirmedBooking = Booking::factory()->create([
            'uuid'         => (string) Str::uuid(),
            'user_id'      => $this->guest->id,
            'residence_id' => $residence->id,
            'status'       => 'confirmed',
        ]);

        $response = $this->actingAs($this->guest)
            ->get(route('bookings.receipt.download', $confirmedBooking));

        $response->assertStatus(404);
    }

    #[Test]
    public function unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('bookings.receipt.download', $this->booking));

        $response->assertRedirect(route('login'));
    }
}
