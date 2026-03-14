<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class AdminResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Administrateurs';

    protected static ?string $modelLabel = 'Administrateur';

    protected static ?string $pluralModelLabel = 'Administrateurs';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'administrateurs';

    protected static ?string $recordTitleAttribute = 'name';

    // Filtrer uniquement les administrateurs
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'admin');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations de l\'administrateur')
                    ->description('Informations de base de l\'administrateur')
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

                Forms\Components\Section::make('Accès administrateur')
                    ->schema([
                        Forms\Components\Hidden::make('role')
                            ->default('admin')
                            ->dehydrated(fn (string $context): bool => $context === 'create'),
                        Forms\Components\TextInput::make('password')
                            ->label('Mot de passe')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => $state ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText('Laissez vide pour conserver le mot de passe actuel'),
                        Forms\Components\Toggle::make('two_factor_enabled')
                            ->label('Authentification à deux facteurs')
                            ->helperText('Recommandé pour les comptes administrateurs'),
                    ])->columns(2),

                Forms\Components\Section::make('Statut du compte')
                    ->schema([
                        Forms\Components\Toggle::make('is_suspended')
                            ->label('Compte suspendu')
                            ->live()
                            ->helperText('Suspendre temporairement l\'accès administrateur'),
                        Forms\Components\DateTimePicker::make('suspended_until')
                            ->label('Suspendu jusqu\'au')
                            ->visible(fn ($get) => $get('is_suspended')),
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Raison de la suspension')
                            ->visible(fn ($get) => $get('is_suspended'))
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Informations de connexion')
                    ->schema([
                        Forms\Components\Placeholder::make('last_login_at')
                            ->label('Dernière connexion')
                            ->content(fn ($record) => $record?->last_login_at ? $record->last_login_at->format('d/m/Y à H:i') : 'Jamais'),
                        Forms\Components\Placeholder::make('last_login_ip')
                            ->label('Dernière IP')
                            ->content(fn ($record) => $record?->last_login_ip ?? 'Non disponible'),
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Compte créé le')
                            ->content(fn ($record) => $record?->created_at?->format('d/m/Y à H:i') ?? '-'),
                    ])->columns(3)
                    ->visibleOn('edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Photo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name).'&background=dc2626&color=fff'),
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
                Tables\Columns\IconColumn::make('two_factor_enabled')
                    ->label('2FA')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('success')
                    ->falseColor('warning'),
                Tables\Columns\IconColumn::make('is_suspended')
                    ->label('Statut')
                    ->boolean()
                    ->trueIcon('heroicon-o-x-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->tooltip(fn ($record) => $record->is_suspended ? 'Compte suspendu' : 'Compte actif'),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('Dernière connexion')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Jamais connecté'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_suspended')
                    ->label('Statut')
                    ->placeholder('Tous')
                    ->trueLabel('Suspendus')
                    ->falseLabel('Actifs'),
                Tables\Filters\TernaryFilter::make('two_factor_enabled')
                    ->label('2FA activé'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Voir'),
                Tables\Actions\EditAction::make()->label('Modifier'),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspendre')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->visible(fn ($record) => !$record->is_suspended && $record->id !== auth()->id())
                    ->requiresConfirmation()
                    ->modalHeading('Suspendre cet administrateur ?')
                    ->modalDescription('L\'administrateur ne pourra plus accéder au panneau d\'administration.')
                    ->form([
                        Forms\Components\Textarea::make('suspension_reason')
                            ->label('Raison de la suspension')
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'is_suspended' => true,
                            'suspension_reason' => $data['suspension_reason'],
                        ]);
                    }),
                Tables\Actions\Action::make('unsuspend')
                    ->label('Réactiver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->is_suspended)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'is_suspended' => false,
                        'suspended_until' => null,
                        'suspension_reason' => null,
                    ])),
            ])
            ->bulkActions([
                // Pas de suppression en masse pour les admins
            ])
            ->emptyStateHeading('Aucun administrateur')
            ->emptyStateDescription('Ajoutez votre premier administrateur pour commencer.')
            ->emptyStateIcon('heroicon-o-shield-check');
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
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
