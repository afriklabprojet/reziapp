<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait DisputePresentation
{
    public function getTypeLabelAttribute(): string
    {
        $category = $this->getAttribute('category');

        return match ($category) {
            'cancellation' => 'Annulation',
            'property_issue' => 'Problème logement',
            'payment' => 'Paiement',
            'host_behavior' => 'Comportement hôte',
            'guest_behavior' => 'Comportement voyageur',
            'refund' => 'Remboursement',
            'other' => 'Autre',
            default => $category ?? '',
        };
    }

    public function getReasonAttribute(): ?string
    {
        return $this->getAttribute('title');
    }

    public function getDetailedDescriptionAttribute(): ?string
    {
        return $this->getAttribute('description');
    }

    public function getEvidenceAttribute(): array
    {
        return $this->getAttribute('evidence_files') ?? [];
    }

    public function getResolutionAttribute(): ?string
    {
        return $this->getAttribute('resolution_type');
    }

    public function getResolutionNotesAttribute(): ?string
    {
        return $this->getAttribute('resolution_details');
    }

    public function getStatusLabelAttribute(): string
    {
        $status = $this->getAttribute('status');

        return match ($status) {
            'open' => 'Ouvert',
            'under_review' => 'En examen',
            'awaiting_response' => 'En attente de réponse',
            'escalated' => 'Escaladé',
            'resolved' => 'Résolu',
            'closed' => 'Fermé',
            default => $status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->getAttribute('status')) {
            'open' => 'yellow',
            'under_review' => 'blue',
            'awaiting_response' => 'purple',
            'escalated' => 'red',
            'resolved' => 'green',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        $priority = $this->getAttribute('priority');

        return match ($priority) {
            'low' => 'Faible',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'urgent' => 'Urgente',
            default => $priority,
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->getAttribute('priority')) {
            'low' => 'gray',
            'medium' => 'yellow',
            'high' => 'orange',
            'urgent' => 'red',
            default => 'gray',
        };
    }

    public function getResolutionLabelAttribute(): ?string
    {
        $resolutionType = $this->getAttribute('resolution_type');

        if (! $resolutionType) {
            return null;
        }

        return match ($resolutionType) {
            'favor_guest' => 'En faveur du voyageur',
            'favor_host' => 'En faveur de l\'hôte',
            'partial_refund' => 'Remboursement partiel',
            'full_refund' => 'Remboursement total',
            'no_refund' => 'Pas de remboursement',
            'mutual_agreement' => 'Accord mutuel',
            'dismissed' => 'Rejeté',
            default => $resolutionType,
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        $responseDeadline = $this->getAttribute('response_deadline');

        return $responseDeadline
            && $responseDeadline->isPast()
            && ! in_array($this->getAttribute('status'), ['resolved', 'closed']);
    }
}
