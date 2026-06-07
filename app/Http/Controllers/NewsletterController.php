<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    // ── Configuration ──────────────────────────────────────────────────────────

    private const RATE_LIMIT_MAX      = 5;
    private const RATE_LIMIT_DECAY    = 3600;   // 1 heure
    private const BRAND_COLOR         = '#f97316';
    private const BRAND_COLOR_DARK    = '#ea6c0a';
    private const BRAND_COLOR_LIGHT   = '#fff7ed';

    // ── Actions publiques ───────────────────────────────────────────────────────

    /**
     * Inscription newsletter (AJAX footer / landing / modal)
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'  => 'required|email:rfc,dns|max:255',
            'name'   => 'nullable|string|max:100',
            'source' => 'nullable|string|max:50',
        ], [
            'email.required' => 'L\'adresse email est requise.',
            'email.email'    => 'Veuillez entrer une adresse email valide.',
        ]);

        $email  = strtolower(trim($validated['email']));
        $name   = isset($validated['name']) ? trim($validated['name']) : null;
        $source = $validated['source'] ?? $request->input('source', 'footer');

        // Rate limiting par IP
        $key = 'newsletter:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, self::RATE_LIMIT_MAX)) {
            $wait = ceil(RateLimiter::availableIn($key) / 60);

            Log::notice('Newsletter rate limit hit', [
                'ip'     => $request->ip(),
                'source' => $source,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Trop de tentatives. Réessayez dans {$wait} minute(s).",
                'code'    => 'rate_limited',
            ], 429);
        }
        RateLimiter::hit($key, self::RATE_LIMIT_DECAY);

        $existing = NewsletterSubscriber::where('email', $email)->first();

        // ── Déjà inscrit et actif ──
        if ($existing?->isActive()) {
            return response()->json([
                'success'            => true,
                'message'            => 'Vous êtes déjà parmi nos insiders ! 📬',
                'code'               => 'already_subscribed',
                'already_subscribed' => true,
            ]);
        }

        // ── Réabonnement ──
        if ($existing) {
            $existing->resubscribe();
            if ($name && !$existing->name) {
                $existing->update(['name' => $name]);
            }

            $this->dispatchWelcomeEmail(
                email:          $existing->email,
                name:           $existing->name ?? $name,
                unsubscribeUrl: $existing->unsubscribe_url,
                isReturn:       true,
            );

            $this->track('newsletter.resubscribed', $existing);

            return response()->json([
                'success' => true,
                'message' => 'Bon retour ! Vous recevrez à nouveau nos meilleures offres. 🎉',
                'code'    => 'resubscribed',
            ]);
        }

        // ── Nouvelle inscription ──
        $subscriber = NewsletterSubscriber::create([
            'email'         => $email,
            'name'          => $name,
            'user_id'       => Auth::id(),
            'source'        => $source,
            'ip_address'    => $request->ip(),
            'subscribed_at' => now(),
            'verified_at'   => now(),
        ]);

        $this->dispatchWelcomeEmail(
            email:          $subscriber->email,
            name:           $subscriber->name,
            unsubscribeUrl: $subscriber->unsubscribe_url,
        );

        $this->track('newsletter.subscribed', $subscriber);

        return response()->json([
            'success' => true,
            'message' => 'Bienvenue ! Vous serez parmi les premiers à découvrir nos nouvelles résidences. 🏠✨',
            'code'    => 'subscribed',
        ]);
    }

    /**
     * Désabonnement via lien tokenisé (depuis les emails)
     */
    public function unsubscribe(string $token): View
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if (!$subscriber) {
            return view('newsletter.unsubscribe', [
                'success' => false,
                'message' => 'Ce lien de désabonnement n\'est pas valide ou a expiré.',
            ]);
        }

        if (!$subscriber->isActive()) {
            return view('newsletter.unsubscribe', [
                'success' => true,
                'message' => 'Vous êtes déjà désabonné — aucune action requise.',
                'email'   => $subscriber->email,
            ]);
        }

        $subscriber->unsubscribe();
        $this->track('newsletter.unsubscribed', $subscriber);

        return view('newsletter.unsubscribe', [
            'success' => true,
            'message' => 'Vous avez bien été désabonné. Nous espérons vous revoir bientôt.',
            'email'   => $subscriber->email,
            'token'   => $token,
        ]);
    }

    /**
     * Réabonnement depuis la page de désabonnement
     */
    public function resubscribe(Request $request): RedirectResponse
    {
        $request->validate(['token' => 'required|string']);

        $subscriber = NewsletterSubscriber::where('token', $request->token)->first();

        if (!$subscriber) {
            return redirect()->route('home')->with('error', 'Lien invalide ou expiré.');
        }

        $subscriber->resubscribe();

        $this->dispatchWelcomeEmail(
            email:          $subscriber->email,
            name:           $subscriber->name,
            unsubscribeUrl: $subscriber->unsubscribe_url,
            isReturn:       true,
        );

        $this->track('newsletter.resubscribed', $subscriber);

        return redirect()->route('home')->with('success', 'Vous êtes de nouveau inscrit à la newsletter ! 🎉');
    }

    // ── Méthodes privées ────────────────────────────────────────────────────────

    /**
     * Envoie l'email de bienvenue de façon asynchrone (queue).
     * Fallback synchrone si la queue n'est pas disponible.
     */
    private function dispatchWelcomeEmail(
        string  $email,
        ?string $name           = null,
        ?string $unsubscribeUrl = null,
        bool    $isReturn       = false,
    ): void {
        try {
            $html = $this->buildWelcomeEmailHtml($name, $unsubscribeUrl, $isReturn);

            $subject = $isReturn
                ? 'Bon retour dans la newsletter Rezi App ! 🎉'
                : 'Bienvenue — vous êtes maintenant insider Rezi App 🏠';

            Mail::queue([], [], function ($message) use ($email, $subject, $html) {
                $message->to($email)
                    ->subject($subject)
                    ->html($html);
            });
        } catch (\Throwable $e) {
            Log::warning('Newsletter welcome email dispatch failed', [
                'email'     => $email,
                'is_return' => $isReturn,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Construit le HTML de l'email de bienvenue.
     * Design inspiré des meilleures newsletters SaaS (Linear, Notion, Loom).
     */
    private function buildWelcomeEmailHtml(
        ?string $name           = null,
        ?string $unsubscribeUrl = null,
        bool    $isReturn       = false,
    ): string {
        $appUrl      = config('app.url');
        $firstName   = $name ? explode(' ', trim($name))[0] : null;
        $greeting    = $firstName ? "Bonjour {$firstName}," : 'Bonjour,';
        $ctaUrl      = $appUrl.'?utm_source=newsletter&utm_medium=email&utm_campaign=welcome&utm_content=cta';
        $headerEmoji = $isReturn ? '🎉' : '👋';

        $intro = $isReturn
            ? 'Vous avez réactivé votre abonnement. Vous recevrez à nouveau nos sélections de résidences et offres exclusives à Abidjan.'
            : 'Merci de rejoindre la communauté Rezi App. Vous serez parmi les premiers à recevoir nos nouvelles annonces et promotions exclusives.';

        $unsubscribeBlock = $unsubscribeUrl
            ? '<a href="'.$unsubscribeUrl.'?utm_source=newsletter&utm_medium=email&utm_campaign=welcome" style="color:#9ca3af;text-decoration:underline;">Se désabonner</a>'
            : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="color-scheme" content="light">
  <title>Bienvenue sur Rezi App</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI','Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased;">

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f3f4f6;padding:48px 16px;">
  <tr><td align="center">

    <!-- Wrapper -->
    <table width="600" cellpadding="0" cellspacing="0" role="presentation"
      style="background:#ffffff;border-radius:16px;overflow:hidden;max-width:600px;box-shadow:0 1px 3px rgba(0,0,0,0.08);">

      <!-- Header -->
      <tr>
        <td style="background:linear-gradient(135deg,#f97316 0%,#fb923c 60%,#fdba74 100%);padding:40px 40px 36px;">
          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
              <td>
                <span style="display:inline-block;background:rgba(255,255,255,0.2);border-radius:10px;padding:6px 16px;">
                  <span style="color:#fff;font-size:20px;font-weight:800;letter-spacing:-0.5px;">Rezi App</span>
                </span>
              </td>
              <td align="right" style="font-size:28px;">{$headerEmoji}</td>
            </tr>
          </table>
          <h1 style="margin:24px 0 8px;color:#fff;font-size:26px;font-weight:700;line-height:1.3;letter-spacing:-0.3px;">
            Votre accès privilégié au marché immobilier d'Abidjan
          </h1>
          <p style="margin:0;color:rgba(255,255,255,0.85);font-size:15px;line-height:1.5;">
            Résidences meublées · Offres exclusives · Alertes en temps réel
          </p>
        </td>
      </tr>

      <!-- Body -->
      <tr>
        <td style="padding:36px 40px 32px;">

          <p style="margin:0 0 20px;color:#374151;font-size:16px;line-height:1.7;">{$greeting}</p>
          <p style="margin:0 0 28px;color:#374151;font-size:16px;line-height:1.7;">{$intro}</p>

          <!-- Value props -->
          <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
            style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;margin:0 0 28px;">
            <tr>
              <td style="padding:24px 24px 8px;">
                <p style="margin:0 0 4px;color:#9a3412;font-size:11px;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;">
                  Ce que vous recevrez
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding:4px 24px 24px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                  <tr>
                    <td style="padding:6px 0;color:#7c3d12;font-size:15px;line-height:1.6;">
                      <span style="margin-right:10px;">🏠</span>
                      <strong style="color:#431407;">Nouvelles résidences</strong> — alertes avant publication sur le site
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;color:#7c3d12;font-size:15px;line-height:1.6;">
                      <span style="margin-right:10px;">🎁</span>
                      <strong style="color:#431407;">Offres exclusives</strong> — réductions réservées aux insiders
                    </td>
                  </tr>
                  <tr>
                    <td style="padding:6px 0;color:#7c3d12;font-size:15px;line-height:1.6;">
                      <span style="margin-right:10px;">📊</span>
                      <strong style="color:#431407;">Tendances marché</strong> — prix, quartiers, opportunités à Abidjan
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>

          <!-- CTA -->
          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
              <td align="center" style="padding:4px 0 8px;">
                <a href="{$ctaUrl}"
                  style="display:inline-block;background:#f97316;color:#ffffff;padding:15px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:16px;letter-spacing:-0.2px;transition:background 0.2s;">
                  Voir les résidences disponibles →
                </a>
              </td>
            </tr>
            <tr>
              <td align="center" style="padding:12px 0 0;">
                <p style="margin:0;color:#9ca3af;font-size:13px;">
                  Ou accédez directement à <a href="{$appUrl}?utm_source=newsletter&utm_medium=email&utm_campaign=welcome&utm_content=link" style="color:#f97316;text-decoration:none;">reziapp.ci</a>
                </p>
              </td>
            </tr>
          </table>

        </td>
      </tr>

      <!-- Social proof -->
      <tr>
        <td style="background:#f9fafb;padding:20px 40px;border-top:1px solid #f3f4f6;">
          <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
            <tr>
              <td align="center" width="33%" style="padding:8px;text-align:center;">
                <div style="font-size:20px;font-weight:800;color:#f97316;">0%</div>
                <div style="font-size:11px;color:#6b7280;margin-top:2px;">Commission</div>
              </td>
              <td align="center" width="33%" style="padding:8px;text-align:center;border-left:1px solid #e5e7eb;border-right:1px solid #e5e7eb;">
                <div style="font-size:20px;font-weight:800;color:#f97316;">24h</div>
                <div style="font-size:11px;color:#6b7280;margin-top:2px;">Modération</div>
              </td>
              <td align="center" width="33%" style="padding:8px;text-align:center;">
                <div style="font-size:20px;font-weight:800;color:#f97316;">100%</div>
                <div style="font-size:11px;color:#6b7280;margin-top:2px;">Gratuit</div>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style="padding:20px 40px 28px;background:#f9fafb;border-top:1px solid #e5e7eb;">
          <p style="margin:0 0 6px;color:#9ca3af;font-size:12px;line-height:1.7;text-align:center;">
            Vous recevez cet email car vous vous êtes inscrit sur
            <a href="{$appUrl}" style="color:#f97316;text-decoration:none;">reziapp.ci</a>.
            Fréquence : 2–4 emails/mois maximum.
          </p>
          <p style="margin:0;color:#9ca3af;font-size:12px;text-align:center;">
            {$unsubscribeBlock}
          </p>
        </td>
      </tr>

    </table>
    <!-- /Wrapper -->

  </td></tr>
</table>

</body>
</html>
HTML;
    }

    /**
     * Log structuré des événements newsletter pour analytics / monitoring.
     */
    private function track(string $event, NewsletterSubscriber $subscriber): void
    {
        Log::info($event, [
            'subscriber_id' => $subscriber->id,
            'source'        => $subscriber->source,
            'user_id'       => $subscriber->user_id,
            'has_name'      => !empty($subscriber->name),
        ]);
    }
}
