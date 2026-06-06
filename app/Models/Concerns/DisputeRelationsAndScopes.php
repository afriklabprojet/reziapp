<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Booking;
use App\Models\Cancellation;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait DisputeRelationsAndScopes
{
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function cancellation()
    {
        return $this->hasOne(Cancellation::class, 'booking_id', 'booking_id');
    }

    public function opener()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function initiator()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function againstUser()
    {
        return $this->belongsTo(User::class, 'against_user_id');
    }

    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open');
    }

    public function scopeUnderReview(Builder $query): Builder
    {
        return $query->where('status', 'under_review');
    }

    public function scopeAwaitingResponse(Builder $query): Builder
    {
        return $query->where('status', 'awaiting_response');
    }

    public function scopeEscalated(Builder $query): Builder
    {
        return $query->where('status', 'escalated');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeAssignedTo(Builder $query, int $adminId): Builder
    {
        return $query->where('assigned_to', $adminId);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('response_deadline', '<', now())
            ->whereNotIn('status', ['resolved', 'closed']);
    }
}
