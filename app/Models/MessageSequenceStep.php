<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageSequenceStep extends Model
{
    public const CHANNEL_EMAIL    = 'email';
    public const CHANNEL_SMS      = 'sms';
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_IN_APP   = 'in_app';

    public const CHANNELS = [
        self::CHANNEL_EMAIL    => 'Email',
        self::CHANNEL_SMS      => 'SMS',
        self::CHANNEL_WHATSAPP => 'WhatsApp',
        self::CHANNEL_IN_APP   => 'Notification',
    ];

    public const DELAY_AFTER_TRIGGER   = 'after_trigger';
    public const DELAY_BEFORE_CHECKIN  = 'before_checkin';
    public const DELAY_AFTER_CHECKOUT  = 'after_checkout';
    public const DELAY_BEFORE_CHECKOUT = 'before_checkout';

    public const DELAY_REFERENCES = [
        self::DELAY_AFTER_TRIGGER   => 'Après le déclencheur',
        self::DELAY_BEFORE_CHECKIN  => 'Avant l\'arrivée',
        self::DELAY_AFTER_CHECKOUT  => 'Après le départ',
        self::DELAY_BEFORE_CHECKOUT => 'Avant le départ',
    ];

    protected $fillable = [
        'message_sequence_id', 'step_order', 'delay_hours', 'delay_reference',
        'channel', 'subject', 'message', 'variables', 'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    public function sequence(): BelongsTo
    {
        return $this->belongsTo(MessageSequence::class, 'message_sequence_id');
    }

    public function getChannelLabelAttribute(): string
    {
        return self::CHANNELS[$this->channel] ?? $this->channel;
    }

    public function getDelayReferenceLabelAttribute(): string
    {
        return self::DELAY_REFERENCES[$this->delay_reference] ?? $this->delay_reference;
    }

    /**
     * Remplacer les variables dans le message
     */
    public function renderMessage(array $data): string
    {
        $message = $this->message;
        foreach ($data as $key => $value) {
            $message = str_replace('{'.$key.'}', (string) $value, $message);
        }

        return $message;
    }
}
