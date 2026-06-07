<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\OversizedIcalFileException;
use App\Models\Booking;
use App\Models\IcalBlockedDate;
use App\Models\IcalFeed;
use App\Models\Residence;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;

class IcalService
{
    private const MAX_ICAL_BYTES = 5_000_000;

    private const MAX_ICAL_EVENTS = 5_000;

    private const TRUSTED_HOST_SUFFIXES = [
        IcalFeed::PLATFORM_AIRBNB => ['airbnb.com'],
        IcalFeed::PLATFORM_BOOKING => ['booking.com'],
        IcalFeed::PLATFORM_EXPEDIA => ['expedia.com', 'expediapartnercentral.com'],
    ];

    public function __construct(
        private readonly ?PublicUrlGuard $publicUrlGuard = null,
    ) {
    }

    /**
     * Importer les événements depuis une URL iCal
     */
    public function importFeed(IcalFeed $feed): int
    {
        if (! $feed->import_url) {
            return 0;
        }

        if (! $this->guard()->isSafe($feed->import_url, $this->allowedHostSuffixesFor($feed))) {
            $this->markSyncError($feed, 'Unsafe or untrusted iCal URL.');

            return 0;
        }

        $feed->update(['sync_status' => 'syncing']);

        $imported = 0;

        try {
            $imported = $this->synchronizePublicFeed($feed);
        } catch (\Throwable $e) {
            $this->markSyncError($feed, $e->getMessage());
            Log::error("iCal import failed for feed {$feed->id}: {$e->getMessage()}");
        }

        return $imported;
    }

    /**
     * @return list<string>
     */
    protected function allowedHostSuffixesFor(IcalFeed $feed): array
    {
        return self::TRUSTED_HOST_SUFFIXES[$feed->platform] ?? [];
    }

    protected function guard(): PublicUrlGuard
    {
        return $this->publicUrlGuard ?? new PublicUrlGuard();
    }

    private function synchronizePublicFeed(IcalFeed $feed): int
    {
        $imported = 0;
        $response = $this->fetchIcalResponse((string) $feed->import_url);

        if (! $response->successful()) {
            $this->markSyncError($feed, "HTTP {$response->status()}: {$response->body()}");
        } else {
            $body = $response->body();

            if ($this->exceedsMaximumSize($body)) {
                $this->markSyncError($feed, 'iCal file too large.');
            } else {
                $events = $this->parseIcal($body);

                if (count($events) > self::MAX_ICAL_EVENTS) {
                    $this->markSyncError($feed, 'Too many events in iCal feed.');
                } else {
                    $imported = $this->replaceBlockedDates($feed, $events);
                }
            }
        }

        return $imported;
    }

    private function fetchIcalResponse(string $url): \Illuminate\Http\Client\Response
    {
        return Http::timeout(30)
            ->withOptions([
                'allow_redirects' => false,
                'on_headers' => function (ResponseInterface $response): void {
                    $contentLength = (int) $response->getHeaderLine('Content-Length');

                    if ($contentLength > self::MAX_ICAL_BYTES) {
                        throw new OversizedIcalFileException('iCal file too large.');
                    }
                },
            ])
            ->get($url);
    }

    private function exceedsMaximumSize(string $body): bool
    {
        return strlen($body) > self::MAX_ICAL_BYTES;
    }

    /**
     * @param  array<int, array{start: ?string, end: ?string, summary: ?string, uid: ?string}>  $events
     */
    private function replaceBlockedDates(IcalFeed $feed, array $events): int
    {
        $feed->blockedDates()->delete();

        $imported = 0;

        foreach ($events as $event) {
            if (! $event['start'] || ! $event['end']) {
                continue;
            }

            IcalBlockedDate::create([
                'ical_feed_id' => $feed->id,
                'residence_id' => $feed->residence_id,
                'start_date'   => $event['start'],
                'end_date'     => $event['end'],
                'summary'      => $event['summary'] ?? null,
                'uid'          => $event['uid'] ?? null,
            ]);
            $imported++;
        }

        $feed->update([
            'sync_status'           => 'synced',
            'last_synced_at'        => now(),
            'imported_events_count' => $imported,
            'last_error'            => null,
        ]);

        return $imported;
    }

    private function markSyncError(IcalFeed $feed, string $message): void
    {
        $feed->update([
            'sync_status' => IcalFeed::SYNC_STATUS_ERROR,
            'last_error'  => $message,
        ]);
    }

    /**
     * Générer le contenu iCal d'export pour une résidence
     */
    public function generateExport(Residence $residence): string
    {
        $bookings = Booking::where('residence_id', $residence->id)
            ->whereIn('status', ['confirmed', 'pending'])
            ->where('check_out', '>=', now()->subMonth())
            ->get();

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Rezi App//Calendar//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$residence->name,
        ];

        foreach ($bookings as $booking) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:booking-'.$booking->id.'@reziapp.ci';
            $lines[] = 'DTSTART;VALUE=DATE:'.Carbon::parse($booking->check_in)->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.Carbon::parse($booking->check_out)->format('Ymd');
            $lines[] = 'SUMMARY:Réservé - '.($booking->user->name ?? 'Voyageur');
            $lines[] = 'STATUS:CONFIRMED';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines);
    }

    /**
     * Synchroniser tous les flux qui en ont besoin
     */
    public function syncAll(): int
    {
        $feeds = IcalFeed::needsSync()->get();
        $synced = 0;

        foreach ($feeds as $feed) {
            $this->importFeed($feed);
            $synced++;
        }

        return $synced;
    }

    /**
     * Parser un fichier iCal
     */
    private function parseIcal(string $content): array
    {
        $events = [];
        $event  = null;

        $lines = preg_split('/\r?\n/', $content);

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === 'BEGIN:VEVENT') {
                $event = ['start' => null, 'end' => null, 'summary' => null, 'uid' => null];
            } elseif ($line === 'END:VEVENT' && $event) {
                $events[] = $event;
                $event = null;
            } elseif ($event) {
                if (str_starts_with($line, 'DTSTART')) {
                    $event['start'] = $this->parseIcalDate($line);
                } elseif (str_starts_with($line, 'DTEND')) {
                    $event['end'] = $this->parseIcalDate($line);
                } elseif (str_starts_with($line, 'SUMMARY:')) {
                    $event['summary'] = substr($line, 8);
                } elseif (str_starts_with($line, 'UID:')) {
                    $event['uid'] = substr($line, 4);
                }
            }
        }

        return $events;
    }

    /**
     * Parser une date iCal
     */
    private function parseIcalDate(string $line): ?string
    {
        // Extraire la valeur après le dernier ':'
        $parts = explode(':', $line);
        $dateStr = end($parts);
        $dateStr = trim($dateStr);
        $parsedDate = null;

        try {
            if (strlen($dateStr) === 8) {
                $parsedDate = Carbon::createFromFormat('Ymd', $dateStr)->format('Y-m-d');
            } elseif (strlen($dateStr) === 15) {
                $parsedDate = Carbon::createFromFormat('Ymd\THis', $dateStr)->format('Y-m-d');
            } elseif (strlen($dateStr) === 16 && str_ends_with($dateStr, 'Z')) {
                $parsedDate = Carbon::createFromFormat('Ymd\THis\Z', $dateStr)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // Ignorer les dates invalides
        }

        return $parsedDate;
    }
}
