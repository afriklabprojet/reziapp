<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageSequenceStep extends Model
{
    const CHANNEL_EMAIL    = 'email';
    const CHANNEL_SMS      = 'sms';
    const CHANNEL_WHATSAPP = 'whatsapp';
    const CHANNEL_IN_APP   = 'in_app';

    const CHANNELS = [
        self::CHANNEL_EMAIL    => 'Email',
        self::CHANNEL_SMS      => 'SMS',
        self::CHANNEL_WHATSAPP => 'WhatsApp',
        self::CHANNEL_IN_APP   => 'Notification',
    ];

    const DELAY_AFTER_TRIGGER   = 'after_trigger';
    const DELAY_BEFORE_CHECKIN  = 'before_checkin';
    const DELAY_AFTER_CHECKOUT  = 'after_checkout';
    const DELAY_BEFORE_CHECKOUT = 'before_checkout';

    const DELAY_REFERENCES = [
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
            $message = str_replace('{' . $key . '}', (string) $value, $message);
        }
        return $message;
    }
}
