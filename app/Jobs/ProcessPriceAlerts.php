<?php

namespace App\Jobs;

use App\Models\PriceAlert;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPriceAlerts implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        Log::info('Processing price alerts...');

        // Get all active alerts that need notification
        $alerts = PriceAlert::active()
            ->needsNotification()
            ->with(['user', 'residence'])
            ->get();

        $processed = 0;
        $notified = 0;

        foreach ($alerts as $alert) {
            try {
                $residence = $alert->residence;

                if (!$residence || !in_array($residence->status, ['active', 'approved'])) {
                    // Deactivate alerts for unpublished/deleted residences
                    $alert->update(['is_active' => false]);
                    continue;
                }

                $processed++;

                // Check if price changed and should notify
                $newPrice = $residence->price_per_night;

                if ($alert->shouldNotify($alert->current_price ?? $alert->original_price, $newPrice)) {
                    // Update alert
                    $alert->updatePrice($newPrice);

                    // Send notification
                    $this->sendPriceAlertNotification($notificationService, $alert, $newPrice);

                    $notified++;
                } else {
                    // Just update price tracking
                    $alert->update(['current_price' => $newPrice]);
                }
            } catch (\Exception $e) {
                Log::error("Error processing price alert {$alert->id}: {$e->getMessage()}");
            }
        }

        Log::info("Price alerts processed: {$processed}, notifications sent: {$notified}");
    }

    /**
     * Send notification to user
     */
    protected function sendPriceAlertNotification(NotificationService $notificationService, PriceAlert $alert, float $newPrice): void
    {
        $residence = $alert->residence;
        $priceChange = $newPrice - $alert->original_price;
        $changePercent = round(($priceChange / $alert->original_price) * 100, 1);

        $emoji = $priceChange < 0 ? '📉' : '📈';
        $changeText = $priceChange < 0
            ? number_format(abs($priceChange), 0, ',', ' ').' FCFA de moins'
            : number_format($priceChange, 0, ',', ' ').' FCFA de plus';

        $title = "{$emoji} Alerte de prix - {$residence->name}";
        $body = 'Le prix est passé à '.number_format($newPrice, 0, ',', ' ')." FCFA/nuit ({$changeText}, {$changePercent}%)";

        $notificationService->sendSystemNotification(
            recipient: $alert->user,
            title: $title,
            body: $body,
            data: [
                'residence_id' => $residence->id,
                'residence_name' => $residence->name,
                'old_price' => $alert->current_price,
                'new_price' => $newPrice,
                'change' => $priceChange,
                'change_percent' => $changePercent,
                'url' => route('residences.show', $residence),
            ],
        );

        // Update notification tracking
        $alert->increment('notification_count');
        $alert->update(['last_notified_at' => now()]);
    }
}
