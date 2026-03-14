<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\IcalBlockedDate;
use App\Models\IcalFeed;
use App\Models\Residence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IcalService
{
    /**
     * Importer les événements depuis une URL iCal
     */
    public function importFeed(IcalFeed $feed): int
    {
        if (!$feed->import_url) {
            return 0;
        }

        $feed->update(['sync_status' => 'syncing']);

        try {
            $response = Http::timeout(30)->get($feed->import_url);

            if (!$response->successful()) {
                throw new \Exception("HTTP {$response->status()}: {$response->body()}");
            }

            $events = $this->parseIcal($response->body());

            // Supprimer les anciennes dates importées pour ce feed
            $feed->blockedDates()->delete();

            $imported = 0;
            foreach ($events as $event) {
                if (!$event['start'] || !$event['end']) {
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
        } catch (\Throwable $e) {
            $feed->update([
                'sync_status' => 'error',
                'last_error'  => $e->getMessage(),
            ]);
            Log::error("iCal import failed for feed {$feed->id}: {$e->getMessage()}");
            return 0;
        }
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
            'PRODID:-//REZI//Calendar//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:' . $residence->name,
        ];

        foreach ($bookings as $booking) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:booking-' . $booking->id . '@reziapp.ci';
            $lines[] = 'DTSTART;VALUE=DATE:' . Carbon::parse($booking->check_in)->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:' . Carbon::parse($booking->check_out)->format('Ymd');
            $lines[] = 'SUMMARY:Réservé - ' . ($booking->user->name ?? 'Voyageur');
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

        try {
            if (strlen($dateStr) === 8) {
                return Carbon::createFromFormat('Ymd', $dateStr)->format('Y-m-d');
            }
            if (strlen($dateStr) === 15) {
                return Carbon::createFromFormat('Ymd\THis', $dateStr)->format('Y-m-d');
            }
            if (strlen($dateStr) === 16 && str_ends_with($dateStr, 'Z')) {
                return Carbon::createFromFormat('Ymd\THis\Z', $dateStr)->format('Y-m-d');
            }
        } catch (\Exception $e) {
            // Ignorer les dates invalides
        }

        return null;
    }
}
