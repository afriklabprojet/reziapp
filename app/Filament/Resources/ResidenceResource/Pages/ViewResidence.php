<?php

namespace App\Filament\Resources\ResidenceResource\Pages;

use App\Filament\Resources\ResidenceResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewResidence extends ViewRecord
{
    protected static string $resource = ResidenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Modifier'),

            Actions\Action::make('approve')
                ->label('Approuver')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['pending', 'needs_changes']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'active',
                        'is_verified' => true,
                        'verified_at' => now(),
                        'moderated_by' => auth()->id(),
                        'moderated_at' => now(),
                        'approval_score' => 80,
                    ]);

                    Notification::make()
                        ->title('Annonce approuvée')
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'is_verified', 'moderated_at', 'moderated_by']);
                }),

            Actions\Action::make('request_changes')
                ->label('Demander modifications')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'pending')
                ->form([
                    \Filament\Forms\Components\Textarea::make('changes_requested')
                        ->label('Modifications à apporter')
                        ->required()
                        ->rows(4),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'needs_changes',
                        'changes_requested' => $data['changes_requested'],
                        'moderated_by' => auth()->id(),
                        'moderated_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Modifications demandées')
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status', 'changes_requested', 'moderated_at', 'moderated_by']);
                }),

            Actions\Action::make('reject')
                ->label('Rejeter')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, ['pending', 'needs_changes']))
                ->requiresConfirmation()
                ->form([
                    \Filament\Forms\Components\Textarea::make('rejection_reason')
                        ->label('Motif du rejet')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => 'rejected',
                        'rejection_reason' => $data['rejection_reason'],
                        'moderated_by' => auth()->id(),
                        'moderated_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Annonce rejetée')
                        ->danger()
                        ->send();

                    $this->refreshFormData(['status', 'rejection_reason', 'moderated_at', 'moderated_by']);
                }),
        ];
    }
}
