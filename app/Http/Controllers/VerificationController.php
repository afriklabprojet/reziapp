<?php

namespace App\Http\Controllers;

use App\Models\EmergencyContact;
use App\Models\FraudReport;
use App\Models\IdentityVerification;
use App\Models\PhoneVerification;
use App\Services\DocumentValidationService;
use App\Services\VerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class VerificationController extends Controller
{
    protected VerificationService $verificationService;

    protected DocumentValidationService $documentValidator;

    public function __construct(VerificationService $verificationService, DocumentValidationService $documentValidator)
    {
        $this->verificationService = $verificationService;
        $this->documentValidator = $documentValidator;
    }

    // ==========================================
    // DASHBOARD
    // ==========================================

    /**
     * Page principale de vérification
     */
    public function dashboard()
    {
        $user = Auth::user();

        $identityVerification = IdentityVerification::where('user_id', $user->id)
            ->latest()
            ->first();

        $emergencyContacts = EmergencyContact::where('user_id', $user->id)
            ->orderByDesc('is_primary')
            ->get();

        $trustScore = $this->verificationService->calculateTrustScore($user);

        // Recalculer le niveau de vérification à chaque visite du dashboard
        $this->verificationService->updateVerificationLevel($user);

        return view('verification.dashboard', compact(
            'user',
            'identityVerification',
            'emergencyContacts',
            'trustScore',
        ));
    }

    // ==========================================
    // VÉRIFICATION D'IDENTITÉ
    // ==========================================

    /**
     * Formulaire de vérification d'identité
     */
    public function identityStart()
    {
        $user = Auth::user();

        $verification = IdentityVerification::where('user_id', $user->id)
            ->latest()
            ->first();

        if ($verification && in_array($verification->status, ['submitted', 'processing', 'manual_review', 'approved'])) {
            return redirect()->route('verification.dashboard')
                ->with('info', 'Vous avez déjà une vérification en cours ou approuvée.');
        }

        return view('verification.identity.start', compact('verification'));
    }

    /**
     * Soumettre les documents d'identité
     */
    public function identityUpload(Request $request)
    {
        $rules = [
            'document_type' => 'required|in:cni,passport',
            'document_front' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
            'document_number' => 'required|string|max:50',
            'expiry_date' => 'required|date|after:today',
        ];

        if ($request->document_type !== 'passport') {
            $rules['document_back'] = 'required|image|mimes:jpg,jpeg,png,webp|max:10240';
        }

        $request->validate($rules);

        $user = Auth::user();

        // Validation avancée du document (format numéro, qualité image, doublons)
        $validationResult = $this->documentValidator->validate(
            documentType: $request->document_type,
            documentNumber: $request->document_number,
            expiryDate: $request->expiry_date,
            frontImage: $request->file('document_front'),
            backImage: $request->file('document_back'),
            userId: $user->id,
        );

        if ($validationResult->failed()) {
            return back()
                ->withInput()
                ->withErrors($validationResult->errorMessages())
                ->with('document_validation_errors', $validationResult->errors);
        }

        if ($validationResult->hasWarnings()) {
            session()->flash('document_warnings', $validationResult->warnings);
        }

        // Chiffrer le numéro de document (PII)
        $verification = IdentityVerification::updateOrCreate(
            ['user_id' => $user->id, 'status' => 'pending'],
            [
                'document_type' => $request->document_type,
                'document_number' => encrypt($request->document_number),
                'document_expiry' => $request->expiry_date,
            ],
        );

        $frontPath = $request->file('document_front')->store('identity-documents/'.$user->id, 'private');
        $verification->document_front = $frontPath;

        if ($request->hasFile('document_back')) {
            $backPath = $request->file('document_back')->store('identity-documents/'.$user->id, 'private');
            $verification->document_back = $backPath;
        }

        $verification->save();

        return redirect()->route('verification.identity.selfie.form');
    }

    /**
     * Formulaire selfie
     */
    public function identitySelfieForm()
    {
        $verification = IdentityVerification::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->latest()
            ->firstOrFail();

        return view('verification.identity.selfie', compact('verification'));
    }

    /**
     * Soumettre le selfie
     */
    public function identitySelfie(Request $request)
    {
        $request->validate([
            'selfie' => 'required|image|mimes:jpg,jpeg,png,webp|max:10240',
        ]);

        $user = Auth::user();

        $verification = IdentityVerification::where('user_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->firstOrFail();

        $selfiePath = $request->file('selfie')->store('identity-documents/'.$user->id, 'private');
        $verification->selfie_photo = $selfiePath;
        $verification->save();

        // submit() gère status + attempt_count + last_attempt_at
        $verification->submit();

        // Mettre en revue manuelle par admin
        $this->verificationService->processAutomaticVerification($verification);

        return redirect()->route('verification.dashboard')
            ->with('success', 'Vérification soumise avec succès ! Nous examinerons votre dossier sous 24-48h.');
    }

    // ==========================================
    // VÉRIFICATION TÉLÉPHONE
    // ==========================================

    /**
     * Envoyer le code de vérification téléphone
     */
    public function phoneSend(Request $request)
    {
        $user = Auth::user();

        if (!$user->phone) {
            return redirect()->route('profile.edit')
                ->with('error', 'Veuillez d\'abord ajouter un numéro de téléphone.');
        }

        // Rate limiting: max 3 envois par heure par utilisateur
        $rateLimitKey = 'phone-send:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->with('error', "Trop de demandes. Réessayez dans {$seconds} secondes.");
        }

        // Cooldown de 2 minutes sur le dernier envoi
        $lastVerification = PhoneVerification::where('user_id', $user->id)
            ->where('created_at', '>', now()->subMinutes(2))
            ->first();

        if ($lastVerification) {
            return back()->with('error', 'Veuillez patienter 2 minutes avant de demander un nouveau code.');
        }

        // Limite globale: max 10 codes par jour
        $dailyCount = PhoneVerification::where('user_id', $user->id)
            ->where('created_at', '>', now()->startOfDay())
            ->count();

        if ($dailyCount >= 10) {
            return back()->with('error', 'Limite quotidienne atteinte. Réessayez demain.');
        }

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Stocker le code hashé (sécurité)
        PhoneVerification::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'otp_code' => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(10),
            'status' => 'sent',
            'last_sent_at' => now(),
        ]);

        RateLimiter::hit($rateLimitKey, 3600);

        // Envoyer le SMS (log en dev, provider en prod)
        $this->sendOtpSms($user->phone, $code);

        // Flash le code uniquement en local/testing
        if (app()->environment('local', 'testing')) {
            session()->flash('dev_otp_code', $code);
        }

        Log::info('OTP sent for phone verification', [
            'user_id' => $user->id,
            'phone' => substr($user->phone, 0, -4).'****',
        ]);

        return redirect()->route('verification.phone.verify.form')
            ->with('success', 'Code envoyé ! Vérifiez vos SMS.');
    }

    /**
     * Formulaire de vérification du code
     */
    public function phoneVerifyForm()
    {
        $user = Auth::user();

        // Vérifier qu'il y a un code en attente
        $hasPending = PhoneVerification::where('user_id', $user->id)
            ->where('otp_expires_at', '>', now())
            ->where('status', 'sent')
            ->exists();

        if (!$hasPending) {
            return redirect()->route('verification.dashboard')
                ->with('error', 'Aucun code en attente. Demandez un nouveau code.');
        }

        return view('verification.phone.verify', ['resendTimer' => 120]);
    }

    /**
     * Vérifier le code OTP
     */
    public function phoneVerify(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = Auth::user();

        // Rate limiting: max 10 tentatives par heure
        $rateLimitKey = 'phone-verify:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 10)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return back()->with('error', "Trop de tentatives. Réessayez dans {$seconds} secondes.");
        }

        $verification = PhoneVerification::where('user_id', $user->id)
            ->where('phone', $user->phone)
            ->where('otp_expires_at', '>', now())
            ->where('status', 'sent')
            ->where('attempts', '<', 5)
            ->latest()
            ->first();

        if (!$verification) {
            return back()->with('error', 'Code expiré ou trop de tentatives. Demandez un nouveau code.');
        }

        RateLimiter::hit($rateLimitKey, 3600);

        // Vérifier le code avec Hash::check (code hashé en DB)
        if (!Hash::check($request->code, $verification->otp_code)) {
            $verification->increment('attempts');
            $remaining = 5 - $verification->attempts;

            if ($remaining <= 0) {
                $verification->update(['status' => 'failed']);

                return back()->with('error', 'Trop de tentatives échouées. Demandez un nouveau code.');
            }

            return back()->with('error', "Code incorrect. {$remaining} tentative(s) restante(s).");
        }

        // Succès
        $verification->update([
            'status' => 'verified',
            'verified_at' => now(),
            'otp_code' => null,
        ]);

        $user->update(['phone_verified' => true]);

        // Recalculer le niveau de vérification (+20 points)
        $this->verificationService->updateVerificationLevel($user);

        Log::info('Phone verified successfully', ['user_id' => $user->id]);

        return redirect()->route('verification.dashboard')
            ->with('success', 'Téléphone vérifié avec succès ! +20 points de confiance.');
    }

    // ==========================================
    // CONTACTS D'URGENCE
    // ==========================================

    /**
     * Liste des contacts d'urgence
     */
    public function emergencyContacts()
    {
        $contacts = EmergencyContact::where('user_id', Auth::id())
            ->orderByDesc('is_primary')
            ->get();

        return view('verification.emergency.contacts', compact('contacts'));
    }

    /**
     * Ajouter un contact d'urgence
     */
    public function emergencyStore(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'phone' => 'required|string|max:20',
            'relationship' => 'required|string|max:50',
            'email' => 'nullable|email|max:100',
            'is_primary' => 'boolean',
        ]);

        $user = Auth::user();
        $count = EmergencyContact::where('user_id', $user->id)->count();

        if ($count >= 3) {
            return back()->with('error', 'Maximum 3 contacts d\'urgence.');
        }

        if ($request->boolean('is_primary') || $count === 0) {
            EmergencyContact::where('user_id', $user->id)->update(['is_primary' => false]);
        }

        EmergencyContact::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'phone' => preg_replace('/[^0-9+]/', '', $request->phone),
            'email' => $request->email,
            'relationship' => $request->relationship,
            'is_primary' => $request->boolean('is_primary') || $count === 0,
            'notify_on_emergency' => true,
        ]);

        return back()->with('success', 'Contact ajouté avec succès.');
    }

    /**
     * Supprimer un contact d'urgence
     */
    public function emergencyDestroy(EmergencyContact $contact)
    {
        if ($contact->user_id !== Auth::id()) {
            abort(403);
        }

        $wasPrimary = $contact->is_primary;
        $contact->delete();

        if ($wasPrimary) {
            $newPrimary = EmergencyContact::where('user_id', Auth::id())->first();
            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }

        return back()->with('success', 'Contact supprimé.');
    }

    /**
     * Définir comme contact principal
     */
    public function emergencySetPrimary(EmergencyContact $contact)
    {
        if ($contact->user_id !== Auth::id()) {
            abort(403);
        }

        EmergencyContact::where('user_id', Auth::id())->update(['is_primary' => false]);
        $contact->update(['is_primary' => true]);

        return back()->with('success', 'Contact défini comme principal.');
    }

    /**
     * Déclencher une alerte d'urgence
     */
    public function emergencyTrigger(Request $request)
    {
        $request->validate([
            'type' => 'nullable|string|in:panic,sos,suspicious,medical,other',
            'message' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $user = Auth::user();

        // Rate limiting: max 3 alertes par heure
        $rateLimitKey = 'emergency-trigger:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $message = 'Trop d\'alertes envoyées. Appelez le 170 en cas d\'urgence réelle.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 429);
            }

            return back()->with('error', $message);
        }

        $contacts = EmergencyContact::where('user_id', $user->id)->get();

        if ($contacts->isEmpty()) {
            $message = 'Ajoutez d\'abord des contacts d\'urgence.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        RateLimiter::hit($rateLimitKey, 3600);

        // Utiliser le service (crée l'alerte, notifie les contacts, alerte les admins)
        $alert = $this->verificationService->triggerEmergency(
            user: $user,
            type: $request->input('type', 'panic'),
            message: $request->input('message'),
            lat: $request->filled('latitude') ? (float) $request->input('latitude') : null,
            lng: $request->filled('longitude') ? (float) $request->input('longitude') : null,
            context: [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'triggered_at' => now()->toIso8601String(),
            ],
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Alerte envoyée ! Vos contacts ont été notifiés.',
                'alert_id' => $alert->id,
            ]);
        }

        return redirect()->route('verification.dashboard')
            ->with('success', 'Alerte déclenchée ! Vos contacts ont été notifiés par SMS.');
    }

    // ==========================================
    // SIGNALEMENT FRAUDE
    // ==========================================

    /**
     * Signaler une fraude
     */
    public function reportFraud(Request $request)
    {
        $request->validate([
            'target_type' => 'required|in:user,residence,review,message',
            'target_id' => 'required|integer',
            'fraud_type' => 'required|string|max:50',
            'description' => 'required|string|min:20|max:2000',
        ]);

        $user = Auth::user();

        // Vérifier les signalements en double (7 jours)
        $existingReport = FraudReport::where('reporter_id', $user->id)
            ->where('target_type', $request->target_type)
            ->where('target_id', $request->target_id)
            ->where('created_at', '>', now()->subDays(7))
            ->exists();

        if ($existingReport) {
            return back()->with('info', 'Vous avez déjà signalé cet élément récemment. Notre équipe l\'examine.');
        }

        // Rate limiting: max 5 signalements par heure
        $rateLimitKey = 'fraud-report:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            return back()->with('error', 'Trop de signalements. Réessayez plus tard.');
        }

        RateLimiter::hit($rateLimitKey, 3600);

        // Service complet (risk score, alerte admin si critique)
        $this->verificationService->reportFraud(
            targetType: $request->target_type,
            targetId: $request->target_id,
            fraudType: $request->fraud_type,
            description: $request->description,
            reporter: $user,
        );

        return back()->with('success', 'Signalement envoyé. Notre équipe va examiner votre rapport.');
    }

    // ==========================================
    // HELPERS
    // ==========================================

    /**
     * Envoyer le SMS OTP via le SmsService
     */
    protected function sendOtpSms(string $phone, string $code): void
    {
        $message = "Votre code Rezi Studio Meublé Faya : {$code}. Valable 10 minutes. Ne partagez ce code avec personne.";

        try {
            app(\App\Services\SmsService::class)->send($phone, $message);
        } catch (\Throwable $e) {
            Log::warning('[KYC SMS] Failed to send OTP', [
                'phone' => substr($phone, 0, -4).'****',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
