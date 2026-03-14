<?php

namespace App\Filament\Resources\EmergencyAlertResource\Pages;

use App\Filament\Resources\EmergencyAlertResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewEmergencyAlert extends ViewRecord
{
    protected static string $resource = EmergencyAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('acknowledge')
                ->label('Prendre en charge')
                ->icon('heroicon-o-hand-raised')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status, ['triggered', 'notified']))
                ->action(function () {
                    $this->record->acknowledge();
                    $this->refreshFormData(['status']);
                }),

            Actions\Action::make('resolve')
                ->label('Résoudre')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['triggered', 'notified', 'acknowledged']))
                ->form([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes de résolution')
                        ->required()
                        ->rows(3),
                    Forms\Components\Toggle::make('false_alarm')
                        ->label('Fausse alerte')
                        ->default(false),
                ])
                ->action(function (array $data) {
                    $this->record->resolve(
                        Auth::id(),
                        $data['notes'],
                        $data['false_alarm'] ?? false,
                    );
                    $this->refreshFormData(['status', 'resolved_by', 'resolved_at', 'resolution_notes']);
                }),
        ];
    }
}
