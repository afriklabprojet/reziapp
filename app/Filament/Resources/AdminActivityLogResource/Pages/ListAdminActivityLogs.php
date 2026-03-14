<?php

namespace App\Filament\Resources\AdminActivityLogResource\Pages;

use App\Filament\Resources\AdminActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminActivityLogs extends ListRecords
{
    protected static string $resource = AdminActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export')
                ->label('Exporter CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    $logs = $this->getFilteredTableQuery()->get();
                    
                    $csv = "Date,Admin,Action,Description,Modele,ID,IP\n";
                    foreach ($logs as $log) {
                        $csv .= sprintf(
                            "%s,%s,%s,%s,%s,%s,%s\n",
                            $log->created_at->format('d/m/Y H:i'),
                            str_replace(',', ' ', $log->admin?->name ?? ''),
                            $log->action,
                            str_replace(',', ' ', $log->description),
                            class_basename($log->model_type ?? ''),
                            $log->model_id ?? '',
                            $log->ip_address ?? ''
                        );
                    }
                    
                    return response()->streamDownload(function () use ($csv) {
                        echo $csv;
                    }, 'activity_log_' . now()->format('Y-m-d') . '.csv');
                }),
        ];
    }
}
