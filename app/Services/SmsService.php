<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service d'envoi de SMS
 *
 * Supporte plusieurs providers :
 * - twilio : Twilio (international, fiable)
 * - orange : Orange SMS API (local Côte d'Ivoire)
 * - log    : Mode dev — écrit dans les logs sans envoyer
 *
 * Configuration : config('services.sms.provider') + config('services.twilio.*')
 */
class SmsService
{
    /**
     * Envoyer un SMS
     */
    public static function send(string $phone, string $message): bool
    {
        $provider = config('services.sms.provider', 'log');

        return match ($provider) {
            'twilio' => self::sendViaTwilio($phone, $message),
            'orange' => self::sendViaOrange($phone, $message),
            default => self::logOnly($phone, $message),
        };
    }

    /**
     * Envoyer via Twilio
     */
    protected static function sendViaTwilio(string $phone, string $message): bool
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (! $sid || ! $token || ! $from) {
            Log::error('SMS Twilio: Configuration incomplète (SID, TOKEN ou FROM manquant)');

            return false;
        }

        // Formater le numéro pour la Côte d'Ivoire si pas de préfixe international
        $phone = self::formatPhone($phone);

        try {
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post(
                    "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json",
                    [
                        'To' => $phone,
                        'From' => $from,
                        'Body' => $message,
                    ]
                );

            if ($response->successful()) {
                Log::info('SMS envoyé', [
                    'to' => $phone,
                    'sid' => $response->json('sid'),
                ]);

                return true;
            }

            Log::error('SMS Twilio échoué', [
                'to' => $phone,
                'status' => $response->status(),
                'error' => $response->json('message') ?? $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('SMS Twilio exception', [
                'to' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envoyer via Orange SMS API (Côte d'Ivoire)
     *
     * Documentation : https://developer.orange.com/apis/sms-ci
     */
    protected static function sendViaOrange(string $phone, string $message): bool
    {
        $clientId = config('services.orange_sms.client_id');
        $clientSecret = config('services.orange_sms.client_secret');
        $senderAddress = config('services.orange_sms.sender_address', 'tel:+2250000');
        $senderName = config('services.orange_sms.sender_name', 'REZI');

        if (! $clientId || ! $clientSecret) {
            Log::error('SMS Orange: Configuration incomplète (CLIENT_ID ou CLIENT_SECRET manquant)');

            return false;
        }

        $phone = self::formatPhone($phone);

        try {
            // Étape 1 : Obtenir un token OAuth2
            $tokenResponse = Http::asForm()->post('https://api.orange.com/oauth/v3/token', [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

            if (! $tokenResponse->successful()) {
                Log::error('SMS Orange: Échec authentification', [
                    'status' => $tokenResponse->status(),
                ]);

                return false;
            }

            $accessToken = $tokenResponse->json('access_token');

            // Étape 2 : Envoyer le SMS
            $encodedSender = urlencode($senderAddress);
            $smsResponse = Http::withToken($accessToken)
                ->post("https://api.orange.com/smsmessaging/v1/outbound/{$encodedSender}/requests", [
                    'outboundSMSMessageRequest' => [
                        'address' => "tel:{$phone}",
                        'senderAddress' => $senderAddress,
                        'senderName' => $senderName,
                        'outboundSMSTextMessage' => [
                            'message' => $message,
                        ],
                    ],
                ]);

            if ($smsResponse->successful()) {
                Log::info('SMS Orange envoyé', ['to' => $phone]);

                return true;
            }

            Log::error('SMS Orange échoué', [
                'to' => $phone,
                'status' => $smsResponse->status(),
                'error' => $smsResponse->json(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('SMS Orange exception', [
                'to' => $phone,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Mode log uniquement (développement)
     */
    protected static function logOnly(string $phone, string $message): bool
    {
        Log::info('[SMS DEV] Message non envoyé (provider=log)', [
            'to' => $phone,
            'message' => $message,
        ]);

        return true; // Retourne true en dev pour ne pas bloquer le flux
    }

    /**
     * Formater un numéro de téléphone pour la Côte d'Ivoire
     *
     * Accepte : 0701020304, 0101020304, +2250701020304, 2250701020304
     * Retourne : +2250701020304
     */
    public static function formatPhone(string $phone): string
    {
        // Nettoyer le numéro
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Si déjà au format international
        if (str_starts_with($phone, '+225')) {
            return $phone;
        }

        if (str_starts_with($phone, '225')) {
            return '+' . $phone;
        }

        // Numéro local ivoirien (commence par 0)
        if (str_starts_with($phone, '0') && strlen($phone) === 10) {
            return '+225' . substr($phone, 1);
        }

        // Numéro sans préfixe (9 chiffres)
        if (strlen($phone) === 9) {
            return '+225' . $phone;
        }

        // Retourner tel quel si format inconnu
        return $phone;
    }
}
