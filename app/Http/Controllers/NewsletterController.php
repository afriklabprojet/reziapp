<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    /**
     * Inscription à la newsletter (AJAX depuis le footer)
     */
    public function subscribe(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email:rfc,dns|max:255',
        ], [
            'email.required' => 'L\'adresse email est requise.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
        ]);

        $email = strtolower(trim($request->email));

        // Rate limiting : max 5 tentatives par IP par heure
        $key = 'newsletter-subscribe:'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => 'Trop de tentatives. Réessayez dans '.ceil($seconds / 60).' minute(s).',
            ], 429);
        }
        RateLimiter::hit($key, 3600);

        // Vérifier si déjà inscrit
        $existing = NewsletterSubscriber::where('email', $email)->first();

        if ($existing) {
            if ($existing->isActive()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Vous êtes déjà inscrit à notre newsletter ! 📬',
                    'already_subscribed' => true,
                ]);
            }

            // Réabonnement
            $existing->resubscribe();
            $this->sendWelcomeEmail($email);

            return response()->json([
                'success' => true,
                'message' => 'Bon retour parmi nous ! Vous êtes de nouveau inscrit. 🎉',
            ]);
        }

        // Nouvelle inscription
        $subscriber = NewsletterSubscriber::create([
            'email' => $email,
            'user_id' => Auth::id(),
            'source' => $request->input('source', 'footer'),
            'ip_address' => $request->ip(),
            'subscribed_at' => now(),
            'verified_at' => now(),
        ]);

        $this->sendWelcomeEmail($email, $subscriber->unsubscribe_url);

        return response()->json([
            'success' => true,
            'message' => 'Bienvenue ! Vous recevrez nos meilleures offres. 🏠✨',
        ]);
    }

    /**
     * Désabonnement via token (lien dans les emails)
     */
    public function unsubscribe(string $token): View
    {
        $subscriber = NewsletterSubscriber::where('token', $token)->first();

        if (!$subscriber) {
            return view('newsletter.unsubscribe', [
                'success' => false,
                'message' => 'Ce lien de désabonnement n\'est pas valide.',
            ]);
        }

        if (!$subscriber->isActive()) {
            return view('newsletter.unsubscribe', [
                'success' => true,
                'message' => 'Vous êtes déjà désabonné de notre newsletter.',
                'email' => $subscriber->email,
            ]);
        }

        $subscriber->unsubscribe();

        return view('newsletter.unsubscribe', [
            'success' => true,
            'message' => 'Vous avez été désabonné avec succès.',
            'email' => $subscriber->email,
            'token' => $token,
        ]);
    }

    /**
     * Email de bienvenue newsletter
     */
    protected function sendWelcomeEmail(string $email, ?string $unsubscribeUrl = null): void
    {
        try {
            $html = '<!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Bienvenue sur REZI</title></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:\'Helvetica Neue\',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;">
      <tr><td style="background:linear-gradient(135deg,#f97316,#fb923c);padding:40px 32px;text-align:center;">
        <h1 style="margin:0;color:#fff;font-size:28px;font-weight:800;letter-spacing:-0.5px;">REZI</h1>
        <p style="margin:8px 0 0;color:rgba(255,255,255,0.9);font-size:14px;">Trouvez un logement à côté de vous</p>
      </td></tr>
      <tr><td style="padding:40px 32px;">
        <h2 style="margin:0 0 16px;color:#111827;font-size:22px;font-weight:700;">Bienvenue dans la newsletter REZI ! 🎉</h2>
        <p style="margin:0 0 16px;color:#4b5563;font-size:16px;line-height:1.6;">
          Merci de votre inscription. Vous recevrez en avant-première :
        </p>
        <ul style="margin:0 0 24px;padding-left:20px;color:#4b5563;font-size:15px;line-height:2;">
          <li>Les nouvelles résidences disponibles</li>
          <li>Les offres exclusives et promotions</li>
          <li>Les actualités du marché immobilier à Abidjan</li>
        </ul>
        <div style="text-align:center;margin:32px 0;">
          <a href="' . config('app.url') . '" style="display:inline-block;background:#f97316;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-weight:700;font-size:16px;">
            Découvrir les résidences
          </a>
        </div>
      </td></tr>
      <tr><td style="background:#f9fafb;padding:24px 32px;border-top:1px solid #e5e7eb;text-align:center;">
        <p style="margin:0;color:#9ca3af;font-size:13px;">
          Vous recevez cet email car vous vous êtes inscrit sur reziapp.ci.<br>
          ' . ($unsubscribeUrl ? '<a href="' . $unsubscribeUrl . '" style="color:#f97316;">Se désabonner</a>' : '') . '
        </p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>';

            Mail::send([], [], function ($message) use ($email, $html) {
                $message->to($email)
                    ->subject('Bienvenue dans la newsletter REZI ! 🏠')
                    ->html($html);
            });
        } catch (\Throwable $e) {
            \Log::warning('Newsletter welcome email failed', ['email' => $email, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Réabonnement depuis la page de désabonnement
     */
    public function resubscribe(Request $request): RedirectResponse
    {
        $request->validate(['token' => 'required|string']);

        $subscriber = NewsletterSubscriber::where('token', $request->token)->first();

        if ($subscriber) {
            $subscriber->resubscribe();

            return redirect()->route('home')->with('success', 'Vous êtes de nouveau inscrit à la newsletter ! 🎉');
        }

        return redirect()->route('home')->with('error', 'Lien invalide.');
    }
}
