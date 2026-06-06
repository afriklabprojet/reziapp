<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait DisputeActions
{
    public function isOpen(): bool
    {
        return ! in_array($this->getAttribute('status'), ['resolved', 'closed']);
    }

    public function canBeEscalated(): bool
    {
        return ! in_array($this->getAttribute('status'), ['escalated', 'resolved', 'closed']);
    }

    public function assignTo(int $adminId): self
    {
        $this->update([
            'assigned_to' => $adminId,
            'status' => 'under_review',
        ]);

        return $this;
    }

    public function escalate(?string $reason = null): self
    {
        $this->update([
            'status' => 'escalated',
            'priority' => 'high',
            'resolution_details' => ($this->getAttribute('resolution_details') ? $this->getAttribute('resolution_details')."\n" : '').'[Escaladé] '.($reason ?? ''),
        ]);

        return $this;
    }

    public function requestResponse(int $hours = 48): self
    {
        $this->update([
            'status' => 'awaiting_response',
            'response_deadline' => now()->addHours($hours),
        ]);

        return $this;
    }

    public function resolve(string $resolutionType, string $details, ?float $amount = null): self
    {
        $this->update([
            'status' => 'resolved',
            'resolution_type' => $resolutionType,
            'resolution_details' => $details,
            'resolution_amount' => $amount,
            'resolved_at' => now(),
        ]);

        return $this;
    }

    public function close(?string $details = null): self
    {
        $this->update([
            'status' => 'closed',
            'resolution_details' => $details ?? $this->getAttribute('resolution_details'),
            'resolved_at' => $this->getAttribute('resolved_at') ?? now(),
        ]);

        return $this;
    }

    public function addEvidence(array $newEvidence): self
    {
        $evidence = $this->getAttribute('evidence_files') ?? [];
        $evidence[] = array_merge($newEvidence, [
            'added_at' => now()->toISOString(),
        ]);

        $this->update(['evidence_files' => $evidence]);

        return $this;
    }

    public static function getTypes(): array
    {
        return [
            'cancellation' => 'Litige d\'annulation',
            'property_issue' => 'Problème avec le logement',
            'payment' => 'Problème de paiement',
            'host_behavior' => 'Comportement de l\'hôte',
            'guest_behavior' => 'Comportement du voyageur',
            'refund' => 'Problème de remboursement',
            'other' => 'Autre',
        ];
    }

    public static function getResolutions(): array
    {
        return [
            'favor_guest' => 'En faveur du voyageur',
            'favor_host' => 'En faveur de l\'hôte',
            'partial_refund' => 'Remboursement partiel',
            'full_refund' => 'Remboursement total',
            'no_refund' => 'Pas de remboursement',
            'mutual_agreement' => 'Accord mutuel',
            'dismissed' => 'Rejeté',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            'low' => 'Faible',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'urgent' => 'Urgente',
        ];
    }
}
