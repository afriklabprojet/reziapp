<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $key = 'newsletter-subscribe:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => "Trop de tentatives. Réessayez dans " . ceil($seconds / 60) . " minute(s).",
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

            return response()->json([
                'success' => true,
                'message' => 'Bon retour parmi nous ! Vous êtes de nouveau inscrit. 🎉',
            ]);
        }

        // Nouvelle inscription
        NewsletterSubscriber::create([
            'email' => $email,
            'user_id' => Auth::id(),
            'source' => $request->input('source', 'footer'),
            'ip_address' => $request->ip(),
            'subscribed_at' => now(),
            'verified_at' => now(), // Auto-vérifié (pas de double opt-in pour l'instant)
        ]);

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
