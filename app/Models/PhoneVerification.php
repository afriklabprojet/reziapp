<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone',
        'country_code',
        'otp_code',
        'otp_expires_at',
        'status',
        'attempts',
        'resend_count',
        'last_sent_at',
        'verified_at',
        'verification_method',
        'provider',
    ];

    protected $casts = [
        'otp_expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected $hidden = [
        'otp_code',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'sent']);
    }

    // ==========================================
    // MÉTHODES
    // ==========================================

    /**
     * Générer un nouveau code OTP
     */
    public function generateOtp(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'otp_code' => $code,
            'otp_expires_at' => now()->addMinutes(10),
            'status' => 'sent',
            'resend_count' => $this->resend_count + 1,
            'last_sent_at' => now(),
        ]);

        return $code;
    }

    /**
     * Vérifier le code OTP
     */
    public function verifyOtp(string $code): bool
    {
        // Incrémenter les tentatives
        $this->increment('attempts');

        // Vérifier le nombre de tentatives
        if ($this->attempts > 5) {
            $this->update(['status' => 'failed']);

            return false;
        }

        // Vérifier l'expiration
        if ($this->otp_expires_at && $this->otp_expires_at->isPast()) {
            return false;
        }

        // Vérifier le code
        if ($this->otp_code !== $code) {
            return false;
        }

        // Marquer comme vérifié
        $this->update([
            'status' => 'verified',
            'verified_at' => now(),
            'otp_code' => null,
        ]);

        // Mettre à jour l'utilisateur
        $this->user->update([
            'phone_verified' => true,
            'phone' => $this->getFullPhone(),
        ]);

        return true;
    }

    /**
     * Peut renvoyer le code
     */
    public function canResend(): bool
    {
        // Max 5 renvois
        if ($this->resend_count >= 5) {
            return false;
        }

        // Attendre 60 secondes entre les renvois
        if ($this->last_sent_at && $this->last_sent_at->addSeconds(60)->isFuture()) {
            return false;
        }

        return true;
    }

    /**
     * Temps restant avant prochain renvoi
     */
    public function getResendCooldown(): int
    {
        if (!$this->last_sent_at) {
            return 0;
        }

        $nextAllowed = $this->last_sent_at->addSeconds(60);

        if ($nextAllowed->isPast()) {
            return 0;
        }

        return now()->diffInSeconds($nextAllowed);
    }

    /**
     * Obtenir le numéro complet
     */
    public function getFullPhone(): string
    {
        return $this->country_code.$this->phone;
    }

    /**
     * Obtenir le numéro masqué
     */
    public function getMaskedPhone(): string
    {
        $phone = $this->phone;
        $visible = 3;

        return str_repeat('*', strlen($phone) - $visible).substr($phone, -$visible);
    }
}
