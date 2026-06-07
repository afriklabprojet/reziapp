<?php

namespace App\Providers\Filament;

use App\Filament\Pages\PlatformSettings;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Rezi Studio Meublé Faya Admin')
            ->brandLogo(fn () => view('filament.brand-logo'))
            ->favicon(asset('favicon.ico'))
            ->darkMode(true)
            ->maxContentWidth(MaxWidth::Full)
            ->colors([
                'primary' => Color::Rose,
                'danger' => Color::Red,
                'success' => Color::Green,
                'warning' => Color::Amber,
                'info' => Color::Blue,
                'purple' => Color::Purple,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Administration')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Configuration')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Gestion')
                    ->icon('heroicon-o-squares-2x2'),
                NavigationGroup::make()
                    ->label('Gestion des annonces')
                    ->icon('heroicon-o-home-modern'),
                NavigationGroup::make()
                    ->label('Annonces')
                    ->icon('heroicon-o-tag'),
                NavigationGroup::make()
                    ->label('Réservations')
                    ->icon('heroicon-o-calendar'),
                NavigationGroup::make()
                    ->label('Gestion des hôtes')
                    ->icon('heroicon-o-user-group'),
                NavigationGroup::make()
                    ->label('Gestion des locataires')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make()
                    ->label('Finances')
                    ->icon('heroicon-o-banknotes'),
                NavigationGroup::make()
                    ->label('Modération')
                    ->icon('heroicon-o-shield-check'),
                NavigationGroup::make()
                    ->label('Sécurité')
                    ->icon('heroicon-o-lock-closed'),
                NavigationGroup::make()
                    ->label('Vérifications')
                    ->icon('heroicon-o-identification'),
                NavigationGroup::make()
                    ->label('Support')
                    ->icon('heroicon-o-chat-bubble-left-right'),
                NavigationGroup::make()
                    ->label('Marketing')
                    ->icon('heroicon-o-megaphone'),
                NavigationGroup::make()
                    ->label('Contenu')
                    ->icon('heroicon-o-document-text')
                    ->collapsed(),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                PlatformSettings::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])
            ->databaseNotifications()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
