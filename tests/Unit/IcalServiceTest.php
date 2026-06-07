<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\IcalFeed;
use App\Models\Residence;
use App\Models\User;
use App\Services\IcalService;
use App\Services\PublicUrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IcalServiceTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_CALENDAR_URL = 'https://calendar.google.com/calendar/ical/test/public/basic.ics';

    public function test_blocks_private_ical_url_without_outbound_request(): void
    {
        $feed = $this->makeFeed(importUrl: $this->metadataUrl(), platform: IcalFeed::PLATFORM_OTHER);
        $service = new IcalService(new class () extends PublicUrlGuard {
            public function isSafe(string $url, array $allowedHostSuffixes = []): bool
            {
                return false;
            }
        });

        Http::fake();

        $imported = $service->importFeed($feed);

        $this->assertSame(0, $imported);
        $this->assertSame(IcalFeed::SYNC_STATUS_ERROR, $feed->fresh()->sync_status);
        $this->assertSame('Unsafe or untrusted iCal URL.', $feed->fresh()->last_error);
        Http::assertNothingSent();
    }

    public function test_blocks_untrusted_host_for_known_airbnb_platform(): void
    {
        $feed = $this->makeFeed(importUrl: self::PUBLIC_CALENDAR_URL, platform: IcalFeed::PLATFORM_AIRBNB);
        $service = new IcalService(new class () extends PublicUrlGuard {
            public function resolveHostIps(string $host): array
            {
                return [implode('.', [8, 8, 8, 8])];
            }
        });

        Http::fake();

        $imported = $service->importFeed($feed);

        $this->assertSame(0, $imported);
        Http::assertNothingSent();
    }

    public function test_imports_public_ical_feed(): void
    {
        $feed = $this->makeFeed(importUrl: self::PUBLIC_CALENDAR_URL, platform: IcalFeed::PLATFORM_OTHER);
        $service = new IcalService(new class () extends PublicUrlGuard {
            public function resolveHostIps(string $host): array
            {
                return [implode('.', [8, 8, 8, 8])];
            }
        });

        Http::fake([
            'calendar.google.com/*' => Http::response(implode("\r\n", [
                'BEGIN:VCALENDAR',
                'BEGIN:VEVENT',
                'UID:test-1@example.com',
                'DTSTART;VALUE=DATE:20260610',
                'DTEND;VALUE=DATE:20260612',
                'SUMMARY:Reservation externe',
                'END:VEVENT',
                'END:VCALENDAR',
            ]), 200),
        ]);

        $imported = $service->importFeed($feed);

        $this->assertSame(1, $imported);
        $this->assertSame(IcalFeed::SYNC_STATUS_SYNCED, $feed->fresh()->sync_status);
        $this->assertDatabaseHas('ical_blocked_dates', [
            'ical_feed_id' => $feed->id,
            'summary' => 'Reservation externe',
            'start_date' => '2026-06-10 00:00:00',
            'end_date' => '2026-06-12 00:00:00',
        ]);
    }

    public function test_rejects_oversized_ical_feed(): void
    {
        $feed = $this->makeFeed(importUrl: self::PUBLIC_CALENDAR_URL, platform: IcalFeed::PLATFORM_OTHER);
        $service = new IcalService(new class () extends PublicUrlGuard {
            public function resolveHostIps(string $host): array
            {
                return [implode('.', [8, 8, 8, 8])];
            }
        });

        Http::fake([
            'calendar.google.com/*' => Http::response(str_repeat('A', 5_000_001), 200),
        ]);

        $imported = $service->importFeed($feed);

        $this->assertSame(0, $imported);
        $this->assertSame(IcalFeed::SYNC_STATUS_ERROR, $feed->fresh()->sync_status);
        $this->assertSame('iCal file too large.', $feed->fresh()->last_error);
    }

    public function test_rejects_ical_feed_with_too_many_events(): void
    {
        $feed = $this->makeFeed(importUrl: self::PUBLIC_CALENDAR_URL, platform: IcalFeed::PLATFORM_OTHER);
        $service = new IcalService(new class () extends PublicUrlGuard {
            public function resolveHostIps(string $host): array
            {
                return [implode('.', [8, 8, 8, 8])];
            }
        });

        $events = [];

        for ($index = 0; $index < 5001; $index++) {
            $events[] = implode("\r\n", [
                'BEGIN:VEVENT',
                'UID:event-'.$index,
                'DTSTART;VALUE=DATE:20260610',
                'DTEND;VALUE=DATE:20260612',
                'SUMMARY:Reservation '.$index,
                'END:VEVENT',
            ]);
        }

        Http::fake([
            'calendar.google.com/*' => Http::response(implode("\r\n", array_merge(['BEGIN:VCALENDAR'], $events, ['END:VCALENDAR'])), 200),
        ]);

        $imported = $service->importFeed($feed);

        $this->assertSame(0, $imported);
        $this->assertSame(IcalFeed::SYNC_STATUS_ERROR, $feed->fresh()->sync_status);
        $this->assertSame('Too many events in iCal feed.', $feed->fresh()->last_error);
        $this->assertDatabaseMissing('ical_blocked_dates', [
            'ical_feed_id' => $feed->id,
        ]);
    }

    private function makeFeed(string $importUrl, string $platform): IcalFeed
    {
        /** @var User $owner */
        $owner = User::factory()->createOne(['role' => 'owner']);
        /** @var Residence $residence */
        $residence = Residence::factory()->createOne(['owner_id' => $owner->id]);

        return IcalFeed::create([
            'residence_id' => $residence->id,
            'user_id' => $owner->id,
            'name' => 'External Feed',
            'platform' => $platform,
            'import_url' => $importUrl,
            'sync_status' => IcalFeed::SYNC_STATUS_PENDING,
            'auto_sync' => true,
            'sync_interval_minutes' => 60,
        ]);
    }

    private function metadataUrl(): string
    {
        return sprintf('http://%d.%d.%d.%d/latest/meta-data/', 169, 254, 169, 254);
    }
}
