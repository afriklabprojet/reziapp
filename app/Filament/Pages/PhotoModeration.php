<?php

namespace App\Filament\Pages;

use App\Jobs\OptimizeResidencePhoto;
use App\Models\Photo;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class PhotoModeration extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static string $view = 'filament.pages.photo-moderation';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?string $navigationLabel = 'Modération photos IA';

    protected static ?string $title = 'Modération des photos (Vision IA)';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Photo::where('moderation_status', 'review')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Photo::query()
                    ->with('residence.owner')
                    ->where('moderation_status', '!=', 'approved')
                    ->latest(),
            )
            ->columns([
                Tables\Columns\ImageColumn::make('path')
                    ->label('Photo')
                    ->disk('public')
                    ->width(120)
                    ->height(80),

                Tables\Columns\TextColumn::make('residence.name')
                    ->label('Annonce')
                    ->limit(30)
                    ->searchable()
                    ->url(fn ($record) => $record->residence ? route('residences.show', $record->residence) : null, shouldOpenInNewTab: true),

                Tables\Columns\TextColumn::make('residence.owner.name')
                    ->label('Propriétaire')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('moderation_status')
                    ->label('Statut')
                    ->colors([
                        'warning' => 'review',
                        'danger'  => 'rejected',
                        'success' => 'approved',
                        'gray'    => ['pending', 'skipped'],
                    ])
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'review'   => '🔍 À vérifier',
                        'rejected' => '❌ Rejeté',
                        'approved' => '✅ Approuvé',
                        'pending'  => '⏳ Non traité',
                        'skipped'  => '⏭ Ignoré (Vision OFF)',
                        default    => $state,
                    }),

                Tables\Columns\TextColumn::make('moderation_reason')
                    ->label('Raison')
                    ->wrap()
                    ->limit(60),

                Tables\Columns\TextColumn::make('room_type')
                    ->label('Pièce')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('quality_score')
                    ->label('Qualité')
                    ->suffix('/100')
                    ->color(fn (?int $state) => match (true) {
                        $state === null  => 'gray',
                        $state >= 70     => 'success',
                        $state >= 40     => 'warning',
                        default          => 'danger',
                    }),

                Tables\Columns\IconColumn::make('is_property_photo')
                    ->label('Immobilier ?')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploadé')
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('moderation_status')
                    ->label('Statut')
                    ->options([
                        'pending'  => '⏳ Non traité',
                        'review'   => '🔍 À vérifier',
                        'rejected' => '❌ Rejeté',
                        'skipped'  => '⏭ Ignoré (Vision OFF)',
                        'approved' => '✅ Approuvé',
                    ]),
                Tables\Filters\SelectFilter::make('room_type')
                    ->label('Type de pièce')
                    ->options(
                        Photo::whereNotNull('room_type')
                            ->distinct()
                            ->pluck('room_type', 'room_type')
                            ->toArray(),
                    ),
                Tables\Filters\TernaryFilter::make('is_property_photo')
                    ->label('Photo immobilière'),
            ])
            ->actions([
                Tables\Actions\Action::make('reanalyze')
                    ->label('Ré-analyser')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('gray')
                    ->action(function (Photo $record) {
                        $record->update([
                            'moderation_status' => 'pending',
                            'is_optimized'      => false,
                        ]);
                        OptimizeResidencePhoto::dispatch($record);

                        Notification::make()
                            ->title('Analyse IA relancée')
                            ->body('La photo sera retraitée par Cloud Vision.')
                            ->info()
                            ->send();
                    })
                    ->visible(fn (Photo $record) => in_array($record->moderation_status, ['pending', 'skipped', 'review'])),

                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Photo $record) {
                        $record->update([
                            'moderation_status' => 'approved',
                            'moderation_reason' => 'Approuvé manuellement par admin',
                        ]);

                        Notification::make()
                            ->title('Photo approuvée')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Photo $record) => $record->moderation_status !== 'approved'),

                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rejeter cette photo')
                    ->modalDescription('La photo sera masquée des annonces.')
                    ->action(function (Photo $record) {
                        $record->update([
                            'moderation_status' => 'rejected',
                            'moderation_reason' => ($record->moderation_reason ?? '').' | Rejeté manuellement par admin',
                        ]);

                        // Si c'était la photo primaire, promouvoir une autre
                        if ($record->is_primary) {
                            $record->update(['is_primary' => false]);

                            $next = Photo::where('residence_id', $record->residence_id)
                                ->where('id', '!=', $record->id)
                                ->whereIn('moderation_status', ['approved', 'pending', 'skipped'])
                                ->orderBy('order')
                                ->first();

                            if ($next) {
                                $next->update(['is_primary' => true]);
                            }
                        }

                        Notification::make()
                            ->title('Photo rejetée')
                            ->danger()
                            ->send();
                    })
                    ->visible(fn (Photo $record) => $record->moderation_status !== 'rejected'),

                Tables\Actions\Action::make('delete')
                    ->label('Supprimer')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer définitivement cette photo ?')
                    ->action(function (Photo $record) {
                        // Supprimer les fichiers
                        $disk = Storage::disk('public');
                        $disk->delete($record->path);
                        $disk->delete(preg_replace('/\.(jpe?g|png)$/i', '.webp', $record->path) ?? '');
                        $disk->delete(preg_replace('/(\.[a-z]+)$/i', '_thumb$1', $record->path) ?? '');

                        $record->delete();

                        Notification::make()
                            ->title('Photo supprimée définitivement')
                            ->warning()
                            ->send();
                    }),

                Tables\Actions\Action::make('details')
                    ->label('Détails IA')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Analyse Cloud Vision')
                    ->modalContent(fn (Photo $record) => view('filament.modals.photo-analysis-details', ['photo' => $record]))
                    ->modalSubmitAction(false),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_reanalyze')
                    ->label('Ré-analyser la sélection')
                    ->icon('heroicon-o-cpu-chip')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $records->each(function ($r) {
                            $r->update(['moderation_status' => 'pending', 'is_optimized' => false]);
                            OptimizeResidencePhoto::dispatch($r);
                        });

                        Notification::make()
                            ->title($records->count().' analyses IA relancées')
                            ->info()
                            ->send();
                    }),

                Tables\Actions\BulkAction::make('bulk_approve')
                    ->label('Approuver la sélection')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $records->each(fn ($r) => $r->update([
                            'moderation_status' => 'approved',
                            'moderation_reason' => 'Approuvé en lot par admin',
                        ]));

                        Notification::make()
                            ->title($records->count().' photos approuvées')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\BulkAction::make('bulk_reject')
                    ->label('Rejeter la sélection')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $records->each(fn ($r) => $r->update([
                            'moderation_status' => 'rejected',
                            'moderation_reason' => 'Rejeté en lot par admin',
                        ]));

                        Notification::make()
                            ->title($records->count().' photos rejetées')
                            ->danger()
                            ->send();
                    }),
            ]);
    }
}
