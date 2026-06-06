<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // ==========================================
    // SETUP PAGE
    // ==========================================

    /**
     * Page de configuration 2FA
     */
    public function setup()
    {
        $user = Auth::user();

        return view('auth.two-factor.setup', [
            'user' => $user,
            'enabled' => (bool) $user->two_factor_enabled,
        ]);
    }

    /**
     * Générer un nouveau secret et afficher le QR code
     */
    public function generate(Request $request)
    {
        $user = Auth::user();

        if ($user->two_factor_enabled) {
            return redirect()->route('two-factor.setup')
                ->with('error', 'La double authentification est déjà activée.');
        }

        $secret = $this->google2fa->generateSecretKey();
        session(['2fa_setup_secret' => $secret]);

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name', 'ReziApp'),
            $user->email,
            $secret,
        );

        return view('auth.two-factor.setup', [
            'user' => $user,
            'enabled' => false,
            'showSetup' => true,
            'secret' => $secret,
            'qrCodeUrl' => $qrCodeUrl,
        ]);
    }

    /**
     * Vérifier le code et activer la 2FA
     * Génère les codes de récupération et redirige vers la page d'affichage
     */
    public function enable(Request $request)
    {
        $request->validate([
            'code' => 'required|string|digits:6',
        ]);

        $user = Auth::user();
        $secret = session('2fa_setup_secret');

        if (! $secret) {
            return redirect()->route('two-factor.setup')
                ->with('error', 'Session expirée. Veuillez recommencer.');
        }

        $valid = $this->google2fa->verifyKey($secret, $request->code);

        if (! $valid) {
            return back()->with('error', 'Code invalide. Vérifiez votre application d\'authentification et réessayez.');
        }

        // Générer 8 codes de récupération
        $recoveryCodes = $this->generateRecoveryCodes();

        // Activer la 2FA et stocker le secret + codes chiffrés
        $user->update([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
            'last_security_check' => now(),
        ]);

        session()->forget('2fa_setup_secret');
        session(['2fa_verified' => true]);

        // Stocker les codes en session pour affichage unique
        session(['2fa_recovery_codes_display' => $recoveryCodes]);

        return redirect()->route('two-factor.recovery-codes');
    }

    /**
     * Afficher les codes de récupération (une seule fois après activation)
     */
    public function showRecoveryCodes()
    {
        $user = Auth::user();

        if (! $user->two_factor_enabled) {
            return redirect()->route('two-factor.setup');
        }

        $codes = session('2fa_recovery_codes_display');

        if (! $codes) {
            return redirect()->route('two-factor.setup')
                ->with('info', 'Les codes de récupération ne sont affichés qu\'une seule fois lors de l\'activation.');
        }

        return view('auth.two-factor.recovery-codes', [
            'user' => $user,
            'codes' => $codes,
        ]);
    }

    /**
     * Confirmer que les codes ont été sauvegardés (supprime de la session)
     */
    public function confirmRecoveryCodes()
    {
        session()->forget('2fa_recovery_codes_display');

        return redirect()->route('two-factor.setup')
            ->with('success', '🎉 Double authentification activée avec succès ! Votre compte est maintenant protégé.');
    }

    /**
     * Régénérer les codes de récupération (depuis la page setup)
     */
    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $user = Auth::user();

        if (! $user->two_factor_enabled) {
            return redirect()->route('two-factor.setup');
        }

        if (! Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Mot de passe incorrect.');
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_recovery_codes' => Crypt::encryptString(json_encode($recoveryCodes)),
            'last_security_check' => now(),
        ]);

        session(['2fa_recovery_codes_display' => $recoveryCodes]);

        return redirect()->route('two-factor.recovery-codes');
    }

    /**
     * Désactiver la 2FA
     */
    public function disable(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'code' => 'required|string|digits:6',
        ]);

        $user = Auth::user();

        if (! Hash::check($request->password, $user->password)) {
            return back()->with('error', 'Mot de passe incorrect.');
        }

        try {
            $secret = Crypt::decryptString($user->two_factor_secret);
            $valid = $this->google2fa->verifyKey($secret, $request->code);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur de vérification. Veuillez réessayer.');
        }

        if (! $valid) {
            return back()->with('error', 'Code invalide.');
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_recovery_codes' => null,
            'trusted_device_token' => null,
            'trusted_device_expires_at' => null,
            'last_security_check' => now(),
        ]);

        session()->forget('2fa_verified');

        $cookie = cookie()->forget('2fa_trusted_device');

        return redirect()->route('two-factor.setup')
            ->with('success', 'Double authentification désactivée.')
            ->withCookie($cookie);
    }

    // ==========================================
    // CHALLENGE (Login Flow)
    // ==========================================

    /**
     * Page de vérification 2FA après login
     */
    public function challenge()
    {
        $user = Auth::user();

        if (! $user->two_factor_enabled) {
            return redirect()->intended(
                $user->isOwner() ? route('owner.dashboard') : route('client.dashboard'),
            );
        }

        if (session('2fa_verified')) {
            return redirect()->intended(
                $user->isOwner() ? route('owner.dashboard') : route('client.dashboard'),
            );
        }

        // Vérifier si l'appareil est de confiance (auto-skip 2FA)
        if ($this->isTrustedDevice($user)) {
            session(['2fa_verified' => true]);

            return redirect()->intended(
                $user->isOwner() ? route('owner.dashboard') : route('client.dashboard'),
            );
        }

        return view('auth.two-factor.challenge', [
            'showRecoveryOption' => (bool) $user->two_factor_recovery_codes,
        ]);
    }

    /**
     * Vérifier le code 2FA lors du login
     */
    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|digits:6',
            'remember_device' => 'nullable',
        ]);

        $user = Auth::user();

        if (! $user->two_factor_enabled || ! $user->two_factor_secret) {
            return redirect()->intended(
                $user->isOwner() ? route('owner.dashboard') : route('client.dashboard'),
            );
        }

        try {
            $secret = Crypt::decryptString($user->two_factor_secret);
            $valid = $this->google2fa->verifyKey($secret, $request->code);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur de vérification. Veuillez réessayer.');
        }

        if (! $valid) {
            return back()->with('error', 'Code invalide. Vérifiez votre application d\'authentification.');
        }

        session(['2fa_verified' => true]);
        $user->update(['last_security_check' => now()]);

        // Se souvenir de cet appareil si demandé (30 jours)
        $cookie = null;
        if ($request->has('remember_device')) {
            $cookie = $this->trustDevice($user);
        }

        $redirect = redirect()->intended(
            $user->isOwner() ? route('owner.dashboard') : route('client.dashboard'),
        );

        return $cookie ? $redirect->withCookie($cookie) : $redirect;
    }

    /**
     * Vérifier avec un code de récupération
     */
    public function verifyRecovery(Request $request)
    {
        $request->validate([
            'recovery_code' => ['required', 'string', 'regex:/^[A-Za-z0-9]{5}-?[A-Za-z0-9]{5}$/'],
        ]);

        $user = Auth::user();

        if (! $user->two_factor_enabled || ! $user->two_factor_recovery_codes) {
            return redirect()->intended(
                $user->isOwner() ? route('owner.dashboard') : route('client.dashboard'),
            );
        }

        try {
            $codes = json_decode(Crypt::decryptString($user->two_factor_recovery_codes), true);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur de vérification. Veuillez réessayer.');
        }

        // Normaliser le code soumis
        $submittedCode = strtoupper(str_replace('-', '', $request->recovery_code));
        $index = null;

        foreach ($codes as $i => $code) {
            if (strtoupper(str_replace('-', '', $code)) === $submittedCode) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            return back()->with('error', 'Code de récupération invalide.');
        }

        // Supprimer le code utilisé (usage unique)
        unset($codes[$index]);
        $codes = array_values($codes);

        $user->update([
            'two_factor_recovery_codes' => count($codes) > 0
                ? Crypt::encryptString(json_encode($codes))
                : null,
            'last_security_check' => now(),
        ]);

        session(['2fa_verified' => true]);

        // Avertir si peu de codes restants
        $remaining = count($codes);
        if ($remaining <= 2) {
            session()->flash('warning', "⚠️ Il ne vous reste que {$remaining} code(s) de récupération. Pensez à en régénérer depuis les paramètres 2FA.");
        }

        return redirect()->intended(
            $user->isOwner() ? route('owner.dashboard') : route('client.dashboard'),
        );
    }

    // ==========================================
    // TRUSTED DEVICE HELPERS
    // ==========================================

    /**
     * Vérifier si l'appareil actuel est de confiance
     */
    private function isTrustedDevice($user): bool
    {
        $cookieToken = request()->cookie('2fa_trusted_device');

        if (! $cookieToken || ! $user->trusted_device_token) {
            return false;
        }

        if (! hash_equals($user->trusted_device_token, hash('sha256', $cookieToken))) {
            return false;
        }

        if ($user->trusted_device_expires_at && $user->trusted_device_expires_at->isPast()) {
            $user->update([
                'trusted_device_token' => null,
                'trusted_device_expires_at' => null,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Marquer cet appareil comme de confiance (30 jours)
     */
    private function trustDevice($user)
    {
        $token = Str::random(64);

        $user->update([
            'trusted_device_token' => hash('sha256', $token),
            'trusted_device_expires_at' => now()->addDays(30),
        ]);

        return cookie('2fa_trusted_device', $token, 60 * 24 * 30, '/', null, true, true, false, 'Lax');
    }

    // ==========================================
    // RECOVERY CODE HELPERS
    // ==========================================

    /**
     * Générer 8 codes de récupération au format XXXXX-XXXXX
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(Str::random(5).'-'.Str::random(5));
        }

        return $codes;
    }
}
