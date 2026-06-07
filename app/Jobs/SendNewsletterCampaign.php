<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\NewsletterSubscriber;
use App\Models\Residence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie une campagne newsletter à tous les abonnés actifs.
 *
 * Types supportés :
 *   'new_residence' — notifie l'approbation d'une nouvelle résidence
 *   'weekly'        — digest hebdomadaire des meilleures résidences
 *
 * Usage :
 *   SendNewsletterCampaign::dispatch([$residenceId], 'new_residence');
 *   SendNewsletterCampaign::dispatch($ids, 'weekly');
 */
class SendNewsletterCampaign implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** Nombre de tentatives max */
    public int $tries = 2;

    /** Timeout par exécution (secondes) */
    public int $timeout = 300;

    public function __construct(
        public readonly array $residenceIds,
        public readonly string $type,
    ) {
    }

    public function handle(): void
    {
        $residences = Residence::whereIn('id', $this->residenceIds)
            ->with('primaryPhoto')
            ->get();

        if ($residences->isEmpty()) {
            Log::warning('SendNewsletterCampaign: aucune résidence trouvée', ['ids' => $this->residenceIds]);

            return;
        }

        $subject = $this->type === 'new_residence'
            ? 'Nouvelle résidence disponible sur Rezi Studio Meublé Faya 🏠'
            : 'Les meilleures résidences de la semaine 🏠';

        $sent = 0;
        $failed = 0;

        NewsletterSubscriber::active()
            ->orderBy('id')
            ->chunk(50, function ($subscribers) use ($residences, $subject, &$sent, &$failed) {
                foreach ($subscribers as $subscriber) {
                    try {
                        $html = $this->buildHtml($residences, $subscriber, $subject);

                        Mail::send([], [], function ($message) use ($subscriber, $html, $subject) {
                            $message
                                ->to($subscriber->email)
                                ->subject($subject)
                                ->html($html);
                        });

                        $sent++;
                    } catch (\Throwable $e) {
                        $failed++;
                        Log::warning('SendNewsletterCampaign: échec envoi', [
                            'email'  => $subscriber->email,
                            'error'  => $e->getMessage(),
                        ]);
                    }
                }
            });

        Log::info('SendNewsletterCampaign terminée', [
            'type'   => $this->type,
            'sent'   => $sent,
            'failed' => $failed,
        ]);
    }

    // ── HTML builder ──────────────────────────────────────────────────────────

    private function buildHtml($residences, NewsletterSubscriber $subscriber, string $subject): string
    {
        $appUrl = config('app.url');
        $unsubscribeUrl = $subscriber->unsubscribe_url;
        $isWeekly = $this->type === 'weekly';

        $intro = $isWeekly
            ? 'Voici une sélection des meilleures résidences disponibles cette semaine à Abidjan.'
            : 'Une nouvelle résidence vient d\'être validée sur Rezi Studio Meublé Faya. Découvrez-la avant tout le monde !';

        $cards = '';
        foreach ($residences as $residence) {
            $photoUrl = $residence->primaryPhoto?->url ?? $appUrl.'/images/placeholder.jpg';
            $price = $residence->price_per_day > 0
                ? number_format($residence->price_per_day, 0, ',', ' ').' FCFA/j'
                : ($residence->price_per_month > 0
                    ? number_format($residence->price_per_month, 0, ',', ' ').' FCFA/mois'
                    : 'Prix sur demande');
            $url = $appUrl.'/residences/'.$residence->id;
            $commune = $residence->commune ?? 'Abidjan';
            $title = htmlspecialchars($residence->title ?? $residence->name ?? 'Résidence');

            $cards .= '
            <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:24px;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">
              <tr>
                <td>
                  <a href="'.$url.'" style="display:block;">
                    <img src="'.$photoUrl.'" alt="'.$title.'" width="100%" style="display:block;max-height:200px;object-fit:cover;">
                  </a>
                </td>
              </tr>
              <tr>
                <td style="padding:16px 20px;">
                  <p style="margin:0 0 4px;font-size:17px;font-weight:700;color:#111827;">'.$title.'</p>
                  <p style="margin:0 0 8px;font-size:13px;color:#6b7280;">📍 '.$commune.'</p>
                  <p style="margin:0 0 14px;font-size:15px;font-weight:700;color:#f97316;">'.$price.'</p>
                  <a href="'.$url.'" style="display:inline-block;background:#f97316;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px;">
                    Voir la résidence
                  </a>
                </td>
              </tr>
            </table>';
        }

        $headerTitle = $isWeekly ? 'Sélection de la semaine' : 'Nouvelle résidence disponible';

        return '<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>'.$subject.'</title></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:\'Helvetica Neue\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;">

      {{-- Header --}}
      <tr><td style="background:linear-gradient(135deg,#f97316,#fb923c);padding:32px;text-align:center;">
        <h1 style="margin:0;color:#fff;font-size:26px;font-weight:800;letter-spacing:-0.5px;">Rezi Studio Meublé Faya</h1>
        <p style="margin:8px 0 0;color:rgba(255,255,255,0.9);font-size:13px;">Trouvez un logement à côté de vous</p>
      </td></tr>

      {{-- Body --}}
      <tr><td style="padding:32px;">
        <h2 style="margin:0 0 8px;color:#111827;font-size:20px;font-weight:700;">'.$headerTitle.'</h2>
        <p style="margin:0 0 28px;color:#6b7280;font-size:15px;line-height:1.6;">'.$intro.'</p>

        '.$cards.'

        <div style="text-align:center;margin-top:24px;">
          <a href="'.$appUrl.'/residences" style="display:inline-block;background:#111827;color:#fff;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px;">
            Voir toutes les résidences
          </a>
        </div>
      </td></tr>

      {{-- Footer --}}
      <tr><td style="background:#f9fafb;padding:20px 32px;border-top:1px solid #e5e7eb;text-align:center;">
        <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.8;">
          Vous recevez cet email car vous êtes inscrit à la newsletter Rezi Studio Meublé Faya.<br>
          <a href="'.$unsubscribeUrl.'" style="color:#f97316;text-decoration:underline;">Se désabonner</a>
        </p>
      </td></tr>

    </table>
  </td></tr>
</table>
</body></html>';
    }
}
