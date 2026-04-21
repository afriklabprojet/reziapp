<?php

namespace App\Filament\Resources\CampaignResource\Pages;

use App\Filament\Resources\CampaignResource;
use App\Services\MarketingService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Mail;

class EditCampaign extends EditRecord
{
    protected static string $resource = CampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('send')
                ->label('Envoyer maintenant')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Envoyer la campagne')
                ->modalDescription(fn () => 'Êtes-vous sûr de vouloir envoyer cette campagne à '
                    .number_format(app(MarketingService::class)->getCampaignRecipients($this->record)->count(), 0, ',', ' ')
                    .' destinataires ? Cette action est irréversible.')
                ->modalSubmitActionLabel('Oui, envoyer')
                ->visible(fn () => in_array($this->record->status, ['draft', 'scheduled']))
                ->action(function () {
                    try {
                        $result = app(MarketingService::class)->sendCampaign($this->record);

                        if ($result['success']) {
                            Notification::make()
                                ->title('Campagne envoyée !')
                                ->body("{$result['sent']} messages envoyés, {$result['failed']} échecs.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Erreur d\'envoi')
                                ->body($result['error'] ?? 'Une erreur est survenue.')
                                ->danger()
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur d\'envoi')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('test')
                ->label('Tester')
                ->icon('heroicon-o-beaker')
                ->color('info')
                ->visible(fn () => in_array($this->record->status, ['draft', 'scheduled']))
                ->requiresConfirmation()
                ->modalHeading('Envoyer un test')
                ->modalDescription('Un message de test sera envoyé à votre adresse email.')
                ->modalSubmitActionLabel('Envoyer le test')
                ->action(function () {
                    try {
                        $user = auth()->user();
                        $content = CampaignResource::personalizeContent($this->record->content, $user);

                        if ($this->record->type === 'email') {
                            Mail::raw($content, function ($message) use ($user) {
                                $message->to($user->email)
                                    ->subject('[TEST] '.($this->record->subject ?? $this->record->name));
                            });
                        }

                        Notification::make()
                            ->title('Test envoyé !')
                            ->body('Message de test envoyé à '.$user->email)
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Erreur')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('preview')
                ->label('Aperçu')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading('Aperçu de la campagne')
                ->modalContent(fn () => view('filament.pages.campaign-preview', ['campaign' => $this->record]))
                ->modalSubmitAction(false),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
