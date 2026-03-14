<?php

namespace App\Services;

use App\Models\User;
use App\Models\OwnerBadge;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IdentityVerificationService
{
    // Types de documents acceptés par pays
    const DOCUMENT_TYPES = [
        'CI' => [
            'cni' => 'Carte Nationale d\'Identité',
            'passport' => 'Passeport',
            'residence_permit' => 'Carte de Séjour',
            'attestation' => 'Attestation d\'Identité',
        ],
        'SN' => [
            'cni' => 'Carte Nationale d\'Identité',
            'passport' => 'Passeport',
            'cedeao_card' => 'Carte CEDEAO',
        ],
        'GH' => [
            'ghana_card' => 'Ghana Card',
            'passport' => 'Passeport',
            'voter_id' => 'Voter ID',
            'drivers_license' => 'Driver\'s License',
        ],
        'NG' => [
            'nin' => 'National Identification Number',
            'passport' => 'Passeport',
            'voter_card' => 'Voter\'s Card',
            'drivers_license' => 'Driver\'s License',
        ],
        'default' => [
            'passport' => 'Passeport',
            'national_id' => 'Carte d\'Identité Nationale',
        ],
    ];

    // Statuts de vérification
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWING = 'reviewing';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    /**
     * Obtenir les types de documents pour un pays
     */
    public function getDocumentTypes(string $countryCode): array
    {
        return self::DOCUMENT_TYPES[$countryCode] ?? self::DOCUMENT_TYPES['default'];
    }

    /**
     * Soumettre des documents de vérification
     */
    public function submitVerification(
        User $user,
        string $documentType,
        UploadedFile $documentFront,
        ?UploadedFile $documentBack = null,
        ?UploadedFile $selfie = null,
        array $metadata = []
    ): array {
        // Valider le type de document
        $countryCode = $user->country_code ?? 'CI';
        $validTypes = $this->getDocumentTypes($countryCode);
        
        if (!isset($validTypes[$documentType])) {
            return [
                'success' => false,
                'error' => 'Type de document non valide pour votre pays',
            ];
        }

        // Stocker les documents de manière sécurisée
        $folder = "identity-docs/{$user->id}/" . Str::random(16);
        
        $frontPath = $documentFront->storeAs(
            $folder, 
            'front.' . $documentFront->getClientOriginalExtension(),
            'private'
        );

        $backPath = null;
        if ($documentBack) {
            $backPath = $documentBack->storeAs(
                $folder,
                'back.' . $documentBack->getClientOriginalExtension(),
                'private'
            );
        }

        $selfiePath = null;
        if ($selfie) {
            $selfiePath = $selfie->storeAs(
                $folder,
                'selfie.' . $selfie->getClientOriginalExtension(),
                'private'
            );
        }

        // Enregistrer la demande de vérification
        $verificationData = [
            'document_type' => $documentType,
            'document_type_label' => $validTypes[$documentType],
            'front_path' => $frontPath,
            'back_path' => $backPath,
            'selfie_path' => $selfiePath,
            'status' => self::STATUS_PENDING,
            'country_code' => $countryCode,
            'submitted_at' => now(),
            'metadata' => $metadata,
        ];

        $user->update([
            'identity_verification_data' => $verificationData,
            'identity_verification_status' => self::STATUS_PENDING,
        ]);

        return [
            'success' => true,
            'message' => 'Documents soumis avec succès. Votre vérification sera traitée sous 24-48h.',
            'status' => self::STATUS_PENDING,
        ];
    }

    /**
     * Approuver une vérification
     */
    public function approveVerification(User $user, ?string $adminNotes = null): array
    {
        $verificationData = $user->identity_verification_data ?? [];
        $verificationData['status'] = self::STATUS_APPROVED;
        $verificationData['approved_at'] = now();
        $verificationData['approved_by'] = auth()->id();
        $verificationData['admin_notes'] = $adminNotes;

        $user->update([
            'identity_verification_data' => $verificationData,
            'identity_verification_status' => self::STATUS_APPROVED,
            'identity_verified_at' => now(),
        ]);

        // Attribuer le badge "Identité vérifiée"
        OwnerBadge::award($user, OwnerBadge::TYPE_VERIFIED_IDENTITY);

        // Envoyer une notification
        $user->notify(new \App\Notifications\IdentityVerifiedNotification());

        return [
            'success' => true,
            'message' => 'Vérification approuvée',
        ];
    }

    /**
     * Rejeter une vérification
     */
    public function rejectVerification(User $user, string $reason): array
    {
        $verificationData = $user->identity_verification_data ?? [];
        $verificationData['status'] = self::STATUS_REJECTED;
        $verificationData['rejected_at'] = now();
        $verificationData['rejected_by'] = auth()->id();
        $verificationData['rejection_reason'] = $reason;

        $user->update([
            'identity_verification_data' => $verificationData,
            'identity_verification_status' => self::STATUS_REJECTED,
        ]);

        // Supprimer les documents (pour confidentialité)
        $this->deleteVerificationDocuments($user);

        // Envoyer une notification
        $user->notify(new \App\Notifications\IdentityRejectedNotification($reason));

        return [
            'success' => true,
            'message' => 'Vérification rejetée',
        ];
    }

    /**
     * Supprimer les documents de vérification
     */
    protected function deleteVerificationDocuments(User $user): void
    {
        $data = $user->identity_verification_data ?? [];
        
        if (isset($data['front_path'])) {
            Storage::disk('private')->delete($data['front_path']);
        }
        if (isset($data['back_path'])) {
            Storage::disk('private')->delete($data['back_path']);
        }
        if (isset($data['selfie_path'])) {
            Storage::disk('private')->delete($data['selfie_path']);
        }
    }

    /**
     * Vérifier si un utilisateur a une vérification en cours
     */
    public function hasPendingVerification(User $user): bool
    {
        return in_array($user->identity_verification_status, [
            self::STATUS_PENDING,
            self::STATUS_REVIEWING,
        ]);
    }

    /**
     * Vérifier si un utilisateur est vérifié
     */
    public function isVerified(User $user): bool
    {
        return $user->identity_verification_status === self::STATUS_APPROVED 
            && $user->identity_verified_at !== null;
    }

    /**
     * Obtenir le statut de vérification formaté
     */
    public function getVerificationStatus(User $user): array
    {
        $status = $user->identity_verification_status ?? 'not_submitted';
        $data = $user->identity_verification_data ?? [];

        $labels = [
            'not_submitted' => 'Non soumis',
            self::STATUS_PENDING => 'En attente de vérification',
            self::STATUS_REVIEWING => 'En cours d\'examen',
            self::STATUS_APPROVED => 'Vérifié',
            self::STATUS_REJECTED => 'Rejeté',
            self::STATUS_EXPIRED => 'Expiré',
        ];

        $colors = [
            'not_submitted' => 'gray',
            self::STATUS_PENDING => 'yellow',
            self::STATUS_REVIEWING => 'blue',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_EXPIRED => 'orange',
        ];

        return [
            'status' => $status,
            'label' => $labels[$status] ?? $status,
            'color' => $colors[$status] ?? 'gray',
            'verified_at' => $user->identity_verified_at,
            'document_type' => $data['document_type_label'] ?? null,
            'rejection_reason' => $data['rejection_reason'] ?? null,
            'can_resubmit' => in_array($status, ['not_submitted', self::STATUS_REJECTED]),
        ];
    }

    /**
     * Obtenir les statistiques de vérification (pour admin)
     */
    public function getVerificationStats(): array
    {
        return [
            'pending' => User::where('identity_verification_status', self::STATUS_PENDING)->count(),
            'reviewing' => User::where('identity_verification_status', self::STATUS_REVIEWING)->count(),
            'approved' => User::where('identity_verification_status', self::STATUS_APPROVED)->count(),
            'rejected' => User::where('identity_verification_status', self::STATUS_REJECTED)->count(),
            'total_verified' => User::whereNotNull('identity_verified_at')->count(),
        ];
    }
}
