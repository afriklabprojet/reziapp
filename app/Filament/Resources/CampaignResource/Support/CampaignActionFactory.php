<?php

namespace App\Filament\Resources\CampaignResource\Support;

use App\Exceptions\CampaignUserNotAuthenticatedException;
use App\Models\Campaign;
use App\Models\User;
use App\Services\CampaignService;
use Filament\Actions\Action as PageAction;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Throwable;

class CampaignActionFactory
{
    public const SENDABLE_STATUSES = ['draft', 'scheduled'];

    public static function makeTableSendAction(): TableAction
    {
        return TableAction::make('send')
            ->label('Envoyer')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->visible(fn (Campaign $record): bool => self::isSendable($record))
            ->requiresConfirmation()
            ->modalHeading('Envoyer la campagne')
            ->modalDescription(fn (Campaign $record): string => self::sendConfirmationDescription($record))
            ->modalSubmitActionLabel('Oui, envoyer')
            ->action(fn (Campaign $record) => self::sendCampaign($record));
    }

    public static function makeTableTestAction(): TableAction
    {
        return TableAction::make('test')
            ->label('Tester')
            ->icon('heroicon-o-beaker')
            ->color('info')
            ->visible(fn (Campaign $record): bool => self::isSendable($record))
            ->requiresConfirmation()
            ->modalHeading('Envoyer un test')
            ->modalDescription('Un message de test sera envoyé à votre adresse email.')
            ->modalSubmitActionLabel('Envoyer le test')
            ->action(fn (Campaign $record) => self::sendTestCampaign($record));
    }

    public static function makePageSendAction(Campaign $campaign): PageAction
    {
        return PageAction::make('send')
            ->label('Envoyer maintenant')
            ->icon('heroicon-o-paper-airplane')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Envoyer la campagne')
            ->modalDescription(fn (): string => self::sendConfirmationDescription($campaign))
            ->modalSubmitActionLabel('Oui, envoyer')
            ->visible(fn (): bool => self::isSendable($campaign))
            ->action(fn () => self::sendCampaign($campaign));
    }

    public static function makePageTestAction(Campaign $campaign): PageAction
    {
        return PageAction::make('test')
            ->label('Tester')
            ->icon('heroicon-o-beaker')
            ->color('info')
            ->visible(fn (): bool => self::isSendable($campaign))
            ->requiresConfirmation()
            ->modalHeading('Envoyer un test')
            ->modalDescription('Un message de test sera envoyé à votre adresse email.')
            ->modalSubmitActionLabel('Envoyer le test')
            ->action(fn () => self::sendTestCampaign($campaign));
    }

    public static function sendCampaign(Campaign $campaign): void
    {
        try {
            $result = app(CampaignService::class)->sendCampaign($campaign);

            if ($result['success']) {
                Notification::make()
                    ->title('Campagne envoyée !')
                    ->body("{$result['sent']} messages envoyés, {$result['failed']} échecs.")
                    ->success()
                    ->send();

                return;
            }

            Notification::make()
                ->title('Erreur d\'envoi')
                ->body($result['error'] ?? 'Une erreur est survenue.')
                ->danger()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Erreur d\'envoi')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function sendTestCampaign(Campaign $campaign): void
    {
        try {
            $user = self::authenticatedUser();
            $content = self::personalizeContent($campaign->content, $user);

            if ($campaign->type === 'email') {
                Mail::raw($content, function ($message) use ($campaign, $user) {
                    $message->to($user->email)
                        ->subject('[TEST] '.($campaign->subject ?? $campaign->name));
                });
            }

            Notification::make()
                ->title('Test envoyé !')
                ->body('Message de test envoyé à '.$user->email)
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Erreur')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    private static function isSendable(Campaign $campaign): bool
    {
        return in_array($campaign->status, self::SENDABLE_STATUSES, true);
    }

    private static function sendConfirmationDescription(Campaign $campaign): string
    {
        $recipients = app(CampaignService::class)->getCampaignRecipients($campaign)->count();

        return 'Êtes-vous sûr de vouloir envoyer cette campagne à '
            .number_format($recipients, 0, ',', ' ')
            .' destinataires ? Cette action est irréversible.';
    }

    private static function authenticatedUser(): User
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            throw new CampaignUserNotAuthenticatedException('Utilisateur non authentifié.');
        }

        return $user;
    }

    private static function personalizeContent(string $content, User $user): string
    {
        $replacements = [
            '{{name}}' => $user->name,
            '{{first_name}}' => explode(' ', $user->name)[0],
            '{{email}}' => $user->email,
            '{{phone}}' => $user->phone ?? '',
            '{{referral_code}}' => $user->referral_code ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
}
