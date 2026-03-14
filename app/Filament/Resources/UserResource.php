<?php

namespace App\Filament\Resources;

use App\Filament\Exports\UserExporter;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Gestion des locataires';

    protected static ?string $navigationLabel = 'Locataires';

    protected static ?string $modelLabel = 'Locataire';

    protected static ?string $pluralModelLabel = 'Locataires';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $slug = 'locataires';

    // Afficher uniquement les locataires (role = user)
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'user');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations personnelles')
                    ->description('Informations de base de l\'utilisateur')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom complet')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Adresse email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Photo de profil')
                            ->image()
                            ->directory('avatars')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Compte')
                    ->schema([
                        Forms\Components\Hidden::make('role')
                            ->default('user')
                            ->dehydrated(fn (string $context): bool => $context === 'create'),
                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText('Laissez vide pour conserver le mot de passe actuel'),
                    ])->columns(2),

                Forms\Components\Section::make('Vérifications')
                    ->schema([
                        Forms\Components\Toggle::make('email_verified')
                            ->label('Email vérifié'),
                        Forms\Components\Toggle::make('phone_verified')
                            ->label('Téléphone vérifié'),
                        Forms\Components\Toggle::make('identity_verified')
                            ->label('Identité vérifiée'),
                        Forms\Components\Select::make('verification_level')
                            ->label('Niveau de vérification')
                            ->options([
                                0 => 'Non vérifié',
                                1 => 'Email vérifié',
                                2 => 'Téléphone vérifié',
                                3 => 'Identité vérifiée',
                            ])
                            ->default(0),
                    ])->columns(4),

                Forms\Components\Section::make('Suspension')
                    ->schema([
                        Forms\Components\Toggle::make('is_suspended')
                            ->label('Compte suspendu')
                            ->live(),
                        Forms\Components\DateTimePicker::make('suspended_until')
                            ->label('Suspendu jusqu\'au')
                            ->visible(fn ($get) => $get('is_suspended')),
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Raison de la suspension')
                            ->visible(fn ($get) => $get('is_suspended'))
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=3b82f6&color=fff'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->icon('heroicon-m-envelope'),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable()
                    ->icon('heroicon-m-phone')
                    ->placeholder('Non renseigné'),
                Tables\Columns\TextColumn::make('bookings_count')
                    ->label('Réservations')
                    ->counts('bookings')
                    ->badge()
                    ->color('info'),
                Tables\Columns\IconColumn::make('email_verified')
                    ->label('Email')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_suspended')
                    ->label('Statut')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Inscrit le')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_suspended')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Suspendus')
                    ->falseLabel('Actifs'),
                Tables\Filters\TernaryFilter::make('email_verified')
                    ->label('Email vérifié'),
                Tables\Filters\TernaryFilter::make('identity_verified')
                    ->label('Identité vérifiée'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspendre')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => !$record->is_suspended)
                    ->requiresConfirmation()
                    ->modalHeading('Suspendre cet utilisateur ?')
                    ->modalDescription('L\'utilisateur ne pourra plus accéder à son compte.')
                    ->action(fn ($record) => $record->update(['is_suspended' => true])),
                Tables\Actions\Action::make('unsuspend')
                    ->label('Réactiver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->is_suspended)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update(['is_suspended' => false, 'suspended_until' => null, 'suspension_reason' => null])),
            ])
            ->headerActions([
                ExportAction::make()
                    ->exporter(UserExporter::class)
                    ->label('Exporter CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Supprimer'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }
}
