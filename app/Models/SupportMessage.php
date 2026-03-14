<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'message',
        'attachments',
        'is_internal_note',
        'read_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_internal_note' => 'boolean',
        'read_at' => 'datetime',
    ];

    // ===== RELATIONSHIPS =====

    /**
     * Support ticket this message belongs to
     */
    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * User who sent the message
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ===== SCOPES =====

    /**
     * Customer messages only
     */
    public function scopeFromCustomer($query)
    {
        return $query->whereHas('ticket', function ($q) {
            $q->whereColumn('support_messages.user_id', 'support_tickets.user_id');
        });
    }

    /**
     * Admin/staff messages only
     */
    public function scopeFromStaff($query)
    {
        return $query->whereHas('ticket', function ($q) {
            $q->whereColumn('support_messages.user_id', '!=', 'support_tickets.user_id');
        })->where('is_internal_note', false);
    }

    /**
     * Internal notes only
     */
    public function scopeInternalNotes($query)
    {
        return $query->where('is_internal_note', true);
    }

    /**
     * Public messages (not internal notes)
     */
    public function scopePublic($query)
    {
        return $query->where('is_internal_note', false);
    }

    /**
     * Unread messages
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    // ===== ACCESSORS =====

    /**
     * Check if message is from customer
     */
    public function getIsFromCustomerAttribute(): bool
    {
        return $this->user_id === $this->ticket->user_id;
    }

    /**
     * Check if message is from staff
     */
    public function getIsFromStaffAttribute(): bool
    {
        return $this->user_id !== $this->ticket->user_id && !$this->is_internal_note;
    }

    /**
     * Get sender role
     */
    public function getSenderRoleAttribute(): string
    {
        if ($this->is_internal_note) {
            return 'internal';
        }

        return $this->is_from_customer ? 'customer' : 'staff';
    }

    /**
     * Get sender label
     */
    public function getSenderLabelAttribute(): string
    {
        if ($this->is_internal_note) {
            return 'Note interne';
        }

        return $this->is_from_customer ? 'Client' : 'Support';
    }

    /**
     * Check if has attachments
     */
    public function getHasAttachmentsAttribute(): bool
    {
        return !empty($this->attachments);
    }

    /**
     * Get attachments count
     */
    public function getAttachmentsCountAttribute(): int
    {
        return count($this->attachments ?? []);
    }

    /**
     * Get formatted message (with line breaks)
     */
    public function getFormattedMessageAttribute(): string
    {
        return nl2br(e($this->message));
    }

    // ===== METHODS =====

    /**
     * Mark as read
     */
    public function markAsRead(): self
    {
        if (!$this->read_at) {
            $this->update(['read_at' => now()]);
        }

        return $this;
    }

    /**
     * Check if read
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * Check if unread
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * Add attachment
     */
    public function addAttachment(string $path, string $name, ?string $type = null): self
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = [
            'path' => $path,
            'name' => $name,
            'type' => $type,
            'added_at' => now()->toISOString(),
        ];

        $this->update(['attachments' => $attachments]);

        return $this;
    }
}
