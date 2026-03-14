<?php

namespace App\Filament\Pages;

use App\Models\Residence;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class ResidenceModeration extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static string $view = 'filament.pages.residence-moderation';

    protected static ?string $navigationGroup = 'Modération';

    protected static ?string $navigationLabel = 'Validation annonces';

    protected static ?string $title = 'Validation des annonces';

    protected static ?int $navigationSort = 1;

    public ?Residence $selectedResidence = null;

    public static function getNavigationBadge(): ?string
    {
        return Residence::where('status', 'pending')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Residence::query()->where('status', 'pending'))
            ->columns([
                Tables\Columns\ImageColumn::make('photos.path')
                    ->label('Photo')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->defaultImageUrl(fn () => asset('images/placeholder.jpg')),
                Tables\Columns\TextColumn::make('name')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->name),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Propriétaire')
                    ->description(fn ($record) => $record->owner?->email)
                    ->searchable(),
                Tables\Columns\TextColumn::make('commune')
                    ->label('Commune')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match($state) {
                        'apartment' => 'Appartement',
                        'studio' => 'Studio',
                        'villa' => 'Villa',
                        'house' => 'Maison',
                        'room' => 'Chambre',
                        default => $state ?? '—',
                    }),
                Tables\Columns\TextColumn::make('price_per_day')
                    ->label('Prix/jour')
                    ->money('XOF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bedrooms')
                    ->label('Chambres')
                    ->suffix(' ch.')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at->diffForHumans()),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'apartment' => 'Appartement',
                        'studio' => 'Studio',
                        'villa' => 'Villa',
                        'house' => 'Maison',
                        'room' => 'Chambre',
                    ]),
                Tables\Filters\SelectFilter::make('commune')
                    ->label('Commune')
                    ->options(fn () => Residence::where('status', 'pending')
                        ->distinct()
                        ->pluck('commune', 'commune')
                        ->filter()
                        ->toArray()),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Aperçu')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => 'Aperçu: '.$record->name)
                    ->modalContent(fn ($record) => view('filament.pages.residence-preview', ['residence' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer')
                    ->modalWidth('5xl'),
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approuver cette annonce')
                    ->modalDescription('L\'annonce sera publiée et visible par tous les utilisateurs.')
                    ->modalSubmitActionLabel('Approuver')
                    ->action(function (Residence $record) {
                        $record->update([
                            'status' => 'active',
                            'approved_at' => now(),
                            'is_available' => true,
                        ]);

                        // Notifier le propriétaire
                        // Mail::to($record->owner)->send(new ResidenceApproved($record));

                        Notification::make()
                            ->title('Annonce approuvée')
                            ->body("L'annonce \"{$record->name}\" a été publiée.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Refuser')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Select::make('rejection_reason')
                            ->label('Motif de refus')
                            ->options([
                                'incomplete' => 'Informations incomplètes',
                                'photos_quality' => 'Photos de mauvaise qualité',
                                'photos_missing' => 'Photos manquantes',
                                'description_poor' => 'Description insuffisante',
                                'pricing_issue' => 'Problème de tarification',
                                'location_invalid' => 'Adresse/localisation invalide',
                                'duplicate' => 'Annonce en double',
                                'inappropriate' => 'Contenu inapproprié',
                                'other' => 'Autre raison',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('rejection_details')
                            ->label('Détails / Message au propriétaire')
                            ->required()
                            ->rows(4)
                            ->placeholder('Expliquez les raisons du refus et ce que le propriétaire doit corriger...'),
                    ])
                    ->action(function (Residence $record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'rejection_reason' => $data['rejection_reason'],
                            'rejection_details' => $data['rejection_details'],
                        ]);

                        // Notifier le propriétaire
                        // Mail::to($record->owner)->send(new ResidenceRejected($record, $data));

                        Notification::make()
                            ->title('Annonce refusée')
                            ->body("L'annonce \"{$record->name}\" a été refusée.")
                            ->warning()
                            ->send();
                    }),
                Tables\Actions\Action::make('request_changes')
                    ->label('Demander modifications')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Forms\Components\CheckboxList::make('changes_requested')
                            ->label('Modifications requises')
                            ->options([
                                'more_photos' => 'Ajouter plus de photos',
                                'better_photos' => 'Améliorer la qualité des photos',
                                'description' => 'Compléter la description',
                                'amenities' => 'Préciser les équipements',
                                'pricing' => 'Revoir la tarification',
                                'location' => 'Vérifier l\'adresse/localisation',
                                'house_rules' => 'Ajouter les règles de la maison',
                                'availability' => 'Préciser les disponibilités',
                            ])
                            ->required()
                            ->columns(2),
                        Forms\Components\Textarea::make('change_message')
                            ->label('Message au propriétaire')
                            ->rows(4)
                            ->placeholder('Ajoutez des détails sur les modifications demandées...'),
                    ])
                    ->action(function (Residence $record, array $data) {
                        $record->update([
                            'status' => 'needs_changes',
                            'changes_requested' => $data['changes_requested'],
                            'change_message' => $data['change_message'] ?? null,
                        ]);

                        // Notifier le propriétaire
                        // Mail::to($record->owner)->send(new ResidenceChangesRequested($record, $data));

                        Notification::make()
                            ->title('Modifications demandées')
                            ->body('Une demande de modification a été envoyée au propriétaire.')
                            ->info()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_approve')
                    ->label('Approuver la sélection')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $records->each(function ($record) {
                            $record->update([
                                'status' => 'active',
                                'approved_at' => now(),
                                'is_available' => true,
                            ]);
                        });

                        Notification::make()
                            ->title($records->count().' annonces approuvées')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('Aucune annonce en attente')
            ->emptyStateDescription('Toutes les annonces ont été traitées.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->poll('30s');
    }

    public function getViewData(): array
    {
        return [
            'pendingCount' => Residence::where('status', 'pending')->count(),
            'todayApproved' => Residence::where('status', 'active')
                ->whereDate('approved_at', today())->count(),
            'todayRejected' => Residence::where('status', 'rejected')
                ->whereDate('updated_at', today())->count(),
            'changesRequested' => Residence::where('status', 'needs_changes')->count(),
        ];
    }
}
