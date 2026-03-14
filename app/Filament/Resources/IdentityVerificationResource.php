<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IdentityVerificationResource\Pages;
use App\Models\IdentityVerification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IdentityVerificationResource extends Resource
{
    protected static ?string $model = IdentityVerification::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Vérifications';

    protected static ?string $navigationLabel = 'Vérifications identité';

    protected static ?string $modelLabel = 'Vérification';

    protected static ?string $pluralModelLabel = 'Vérifications d\'identité';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Utilisateur')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                Forms\Components\Section::make('Document')
                    ->schema([
                        Forms\Components\Select::make('document_type')
                            ->label('Type de document')
                            ->options([
                                'cni' => 'Carte d\'identité',
                                'passport' => 'Passeport',
                                'driver_license' => 'Permis de conduire',
                                'residence_permit' => 'Titre de séjour',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('document_number')
                            ->label('Numéro du document'),
                        Forms\Components\TextInput::make('document_country')
                            ->label('Pays')
                            ->default('CI'),
                        Forms\Components\FileUpload::make('document_front')
                            ->label('Recto du document')
                            ->image()
                            ->directory('verifications'),
                        Forms\Components\FileUpload::make('document_back')
                            ->label('Verso du document')
                            ->image()
                            ->directory('verifications'),
                        Forms\Components\FileUpload::make('selfie_photo')
                            ->label('Selfie avec document')
                            ->image()
                            ->directory('verifications'),
                    ])->columns(2),

                Forms\Components\Section::make('Vérification')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options([
                                'pending' => 'En attente',
                                'submitted' => 'Soumis',
                                'processing' => 'En traitement',
                                'manual_review' => 'Revue manuelle',
                                'approved' => 'Approuvé',
                                'rejected' => 'Rejeté',
                                'expired' => 'Expiré',
                            ])
                            ->default('pending')
                            ->required(),
                        Forms\Components\Select::make('reviewed_by')
                            ->label('Vérifié par')
                            ->relationship('reviewer', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DateTimePicker::make('reviewed_at')
                            ->label('Date de vérification'),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif de rejet')
                            ->rows(2),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes admin')
                            ->rows(2),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->description(fn ($record) => $record->user?->email)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Document')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'cni' => 'CNI',
                        'passport' => 'Passeport',
                        'driver_license' => 'Permis',
                        'residence_permit' => 'Titre séjour',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('document_number')
                    ->label('N° Document')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\ImageColumn::make('document_front')
                    ->label('Recto')
                    ->disk('private')
                    ->circular()
                    ->size(40),
                Tables\Columns\ImageColumn::make('selfie_photo')
                    ->label('Selfie')
                    ->disk('private')
                    ->circular()
                    ->size(40),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'approved' => 'success',
                        'processing', 'manual_review' => 'info',
                        'pending', 'submitted' => 'warning',
                        'rejected' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'pending' => 'En attente',
                        'submitted' => 'Soumis',
                        'processing' => 'En traitement',
                        'manual_review' => 'Revue manuelle',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                        'expired' => 'Expiré',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('attempt_count')
                    ->label('Tentatives')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Vérifié par')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Date révision')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Soumis le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options([
                        'pending' => 'En attente',
                        'submitted' => 'Soumis',
                        'manual_review' => 'Revue manuelle',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'submitted', 'manual_review']))
                    ->requiresConfirmation()
                    ->modalHeading('Approuver cette vérification ?')
                    ->modalDescription('L\'utilisateur sera marqué comme vérifié et son niveau de confiance sera mis à jour automatiquement.')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes (optionnel)')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->approve(auth()->id(), $data['admin_notes'] ?? null);

                        \Filament\Notifications\Notification::make()
                            ->title('Vérification approuvée')
                            ->body("L'utilisateur {$record->user->name} est maintenant vérifié.")
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'submitted', 'manual_review']))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Motif de rejet')
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Notes internes (optionnel)')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->reject(auth()->id(), $data['rejection_reason'], $data['admin_notes'] ?? null);

                        \Filament\Notifications\Notification::make()
                            ->title('Vérification rejetée')
                            ->body("Motif : {$data['rejection_reason']}")
                            ->danger()
                            ->send();
                    }),
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIdentityVerifications::route('/'),
            'create' => Pages\CreateIdentityVerification::route('/create'),
            'edit' => Pages\EditIdentityVerification::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['pending', 'submitted', 'manual_review'])->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
