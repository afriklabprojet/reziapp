<?php

namespace App\Services;

use App\Models\WhatsappMessage;
use App\Support\SensitiveData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $apiToken;
    protected string $phoneNumberId;
    protected bool $enabled;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url', 'https://graph.facebook.com/v18.0');
        $this->apiToken = config('services.whatsapp.token') ?? '';
        $this->phoneNumberId = config('services.whatsapp.phone_number_id') ?? '';
        $this->enabled = (bool) config('services.whatsapp.enabled', false);
    }

    /**
     * Envoyer un message WhatsApp template
     */
    public function sendTemplate(string $to, string $templateName, array $parameters = [], ?int $userId = null): ?WhatsappMessage
    {
        if (!$this->enabled) {
            Log::info('WhatsApp disabled: template message skipped', [
                'template' => $templateName,
                'to' => SensitiveData::maskPhone($to),
            ]);

            return null;
        }

        $phone = $this->formatPhoneNumber($to);

        $message = WhatsappMessage::create([
            'user_id' => $userId,
            'phone_number' => $phone,
            'message_type' => 'template',
            'template_name' => $templateName,
            'status' => 'pending',
            'metadata' => ['parameters' => $parameters],
        ]);

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withToken($this->apiToken)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'template',
                    'template' => [
                        'name' => $templateName,
                        'language' => ['code' => 'fr'],
                        'components' => $this->buildTemplateComponents($parameters),
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $message->update([
                    'status' => 'sent',
                    'whatsapp_message_id' => $data['messages'][0]['id'] ?? null,
                    'sent_at' => now(),
                ]);
            } else {
                $message->update([
                    'status' => 'failed',
                    'metadata' => array_merge($message->metadata ?? [], ['error' => $response->json()]),
                ]);
                Log::error('WhatsApp API error', [
                    'status' => $response->status(),
                    'to' => SensitiveData::maskPhone($phone),
                    'error' => $response->json('error.message') ?? $response->json('message') ?? 'Unknown API error',
                ]);
            }
        } catch (\Exception $e) {
            $message->update([
                'status' => 'failed',
                'metadata' => array_merge($message->metadata ?? [], ['error' => $e->getMessage()]),
            ]);
            Log::error('WhatsApp exception', [
                'to' => SensitiveData::maskPhone($phone),
                'error' => $e->getMessage(),
            ]);
        }

        return $message;
    }

    /**
     * Envoyer un message texte simple
     */
    public function sendText(string $to, string $text, ?int $userId = null): ?WhatsappMessage
    {
        if (!$this->enabled) {
            Log::info('WhatsApp disabled: text message skipped', [
                'to' => SensitiveData::maskPhone($to),
                'message_length' => mb_strlen($text),
            ]);

            return null;
        }

        $phone = $this->formatPhoneNumber($to);

        $message = WhatsappMessage::create([
            'user_id' => $userId,
            'phone_number' => $phone,
            'message_type' => 'text',
            'message_content' => $text,
            'status' => 'pending',
        ]);

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = Http::withToken($this->apiToken)
                ->post("{$this->apiUrl}/{$this->phoneNumberId}/messages", [
                    'messaging_product' => 'whatsapp',
                    'to' => $phone,
                    'type' => 'text',
                    'text' => ['body' => $text],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $message->update([
                    'status' => 'sent',
                    'whatsapp_message_id' => $data['messages'][0]['id'] ?? null,
                    'sent_at' => now(),
                ]);
            } else {
                $message->update(['status' => 'failed']);
            }
        } catch (\Exception $e) {
            $message->update(['status' => 'failed']);
            Log::error('WhatsApp exception', [
                'to' => SensitiveData::maskPhone($phone),
                'error' => $e->getMessage(),
            ]);
        }

        return $message;
    }

    /**
     * Envoyer notification de nouvelle réservation
     */
    public function sendBookingNotification(string $to, array $data, ?int $userId = null): ?WhatsappMessage
    {
        return $this->sendTemplate($to, 'booking_confirmation', [
            'guest_name' => $data['guest_name'] ?? '',
            'residence_name' => $data['residence_name'] ?? '',
            'check_in' => $data['check_in'] ?? '',
            'check_out' => $data['check_out'] ?? '',
            'total_amount' => $data['total_amount'] ?? '',
        ], $userId);
    }

    /**
     * Envoyer notification de nouveau message
     */
    public function sendMessageNotification(string $to, array $data, ?int $userId = null): ?WhatsappMessage
    {
        return $this->sendTemplate($to, 'new_message', [
            'sender_name' => $data['sender_name'] ?? '',
            'residence_name' => $data['residence_name'] ?? '',
            'message_preview' => mb_substr($data['message'] ?? '', 0, 100),
        ], $userId);
    }

    /**
     * Envoyer rappel de check-in
     */
    public function sendCheckInReminder(string $to, array $data, ?int $userId = null): ?WhatsappMessage
    {
        return $this->sendTemplate($to, 'checkin_reminder', [
            'guest_name' => $data['guest_name'] ?? '',
            'residence_name' => $data['residence_name'] ?? '',
            'check_in_date' => $data['check_in_date'] ?? '',
            'address' => $data['address'] ?? '',
        ], $userId);
    }

    /**
     * Formater le numéro de téléphone pour WhatsApp
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Supprimer tout sauf les chiffres
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Si commence par 0, remplacer par indicatif pays (225 pour CI)
        if (str_starts_with($phone, '0')) {
            $phone = '225'.substr($phone, 1);
        }

        // Si pas d'indicatif, ajouter 225
        if (strlen($phone) <= 10) {
            $phone = '225'.$phone;
        }

        return $phone;
    }

    /**
     * Construire les composants du template
     */
    protected function buildTemplateComponents(array $parameters): array
    {
        if (empty($parameters)) {
            return [];
        }

        $bodyParams = [];
        foreach ($parameters as $key => $value) {
            $bodyParams[] = [
                'type' => 'text',
                'text' => (string) $value,
            ];
        }

        return [
            [
                'type' => 'body',
                'parameters' => $bodyParams,
            ],
        ];
    }

    /**
     * Générer un lien WhatsApp direct
     */
    public static function generateLink(string $phone, string $message = ''): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '225'.substr($phone, 1);
        }

        $url = "https://wa.me/{$phone}";

        if ($message) {
            $url .= '?text='.urlencode($message);
        }

        return $url;
    }
}
