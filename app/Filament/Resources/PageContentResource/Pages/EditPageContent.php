<?php

namespace App\Filament\Resources\PageContentResource\Pages;

use App\Filament\Resources\PageContentResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class EditPageContent extends EditRecord
{
    protected static string $resource = PageContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('preview')
                ->label('Aperçu')
                ->icon('heroicon-o-eye')
                ->url(fn () => match($this->record->page_slug) {
                    'about' => route('pages.about'),
                    'contact' => route('pages.contact'),
                    'cgu' => route('pages.cgu'),
                    'confidentialite' => route('pages.confidentialite'),
                    'mentions-legales' => route('pages.mentions-legales'),
                    'faq' => route('pages.faq'),
                    'guide-proprietaire' => route('pages.guide-proprietaire'),
                    'home' => route('home'),
                    default => route('home'),
                }, shouldOpenInNewTab: true),
        ];
    }

    public function form(Form $form): Form
    {
        $slug = $this->record?->page_slug ?? '';

        return $form->schema([
            Forms\Components\Section::make('Informations générales')
                ->schema([
                    Forms\Components\TextInput::make('page_title')
                        ->label('Titre de la page')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('page_slug')
                        ->label('Identifiant (slug)')
                        ->required()
                        ->disabled()
                        ->helperText('Ne peut pas être modifié'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Page active')
                        ->default(true),
                ])
                ->columns(3),

            Forms\Components\Section::make('SEO')
                ->schema([
                    Forms\Components\TextInput::make('meta_title')
                        ->label('Titre SEO')
                        ->maxLength(70)
                        ->helperText('60-70 caractères recommandés'),
                    Forms\Components\Textarea::make('meta_description')
                        ->label('Description SEO')
                        ->maxLength(160)
                        ->rows(2)
                        ->helperText('150-160 caractères recommandés'),
                ])
                ->columns(2),

            ...$this->getContentSections(),
        ]);
    }

    protected function getContentSections(): array
    {
        $slug = $this->record?->page_slug ?? '';

        return match($slug) {
            'about' => $this->getAboutSections(),
            'contact' => $this->getContactSections(),
            'cgu' => $this->getLegalSections('CGU'),
            'confidentialite' => $this->getLegalSections('Confidentialité'),
            'mentions-legales' => $this->getLegalSections('Mentions Légales'),
            'faq' => $this->getFaqSections(),
            'guide-proprietaire' => $this->getGuideSections(),
            'home' => $this->getHomeSections(),
            default => [],
        };
    }

    protected function getAboutSections(): array
    {
        return [
            // Hero Section
            Forms\Components\Section::make('Section Hero')
                ->schema([
                    Forms\Components\TextInput::make('data.hero.title')
                        ->label('Titre')
                        ->required(),
                    Forms\Components\TextInput::make('data.hero.highlight')
                        ->label('Texte en surbrillance'),
                    Forms\Components\Textarea::make('data.hero.description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('data.hero.cta_primary')
                        ->label('Bouton principal'),
                    Forms\Components\TextInput::make('data.hero.cta_secondary')
                        ->label('Bouton secondaire'),
                ])
                ->columns(2)
                ->collapsible(),

            // Mission Section
            Forms\Components\Section::make('Section Mission')
                ->schema([
                    Forms\Components\TextInput::make('data.mission.label')
                        ->label('Label'),
                    Forms\Components\TextInput::make('data.mission.title')
                        ->label('Titre')
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('data.mission.paragraphs')
                        ->label('Paragraphes')
                        ->simple(
                            Forms\Components\Textarea::make('text')
                                ->rows(2),
                        )
                        ->defaultItems(3)
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('data.mission.features')
                        ->label('Fonctionnalités')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Titre')
                                ->required(),
                            Forms\Components\TextInput::make('description')
                                ->label('Description'),
                            Forms\Components\Select::make('color')
                                ->label('Couleur')
                                ->options([
                                    'orange' => 'Orange',
                                    'emerald' => 'Vert',
                                    'yellow' => 'Jaune',
                                    'blue' => 'Bleu',
                                    'purple' => 'Violet',
                                    'red' => 'Rouge',
                                ]),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            // Steps Section
            Forms\Components\Section::make('Section Étapes')
                ->schema([
                    Forms\Components\TextInput::make('data.steps.title')
                        ->label('Titre'),
                    Forms\Components\TextInput::make('data.steps.subtitle')
                        ->label('Sous-titre'),
                    Forms\Components\Repeater::make('data.steps.items')
                        ->label('Étapes')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Titre')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rows(2),
                        ])
                        ->columns(2)
                        ->defaultItems(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            // Values Section
            Forms\Components\Section::make('Section Valeurs')
                ->schema([
                    Forms\Components\TextInput::make('data.values.title')
                        ->label('Titre')
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('data.values.items')
                        ->label('Valeurs')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Titre')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rows(2),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),

            // Why Section
            Forms\Components\Section::make('Section Pourquoi nous')
                ->schema([
                    Forms\Components\TextInput::make('data.why.title')
                        ->label('Titre')
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('data.why.items')
                        ->label('Avantages')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Titre')
                                ->required(),
                            Forms\Components\Textarea::make('description')
                                ->label('Description')
                                ->rows(2),
                        ])
                        ->columns(2)
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),

            // CTA Section
            Forms\Components\Section::make('Section CTA')
                ->schema([
                    Forms\Components\TextInput::make('data.cta.title')
                        ->label('Titre'),
                    Forms\Components\Textarea::make('data.cta.description')
                        ->label('Description')
                        ->rows(2),
                    Forms\Components\TextInput::make('data.cta.cta_primary')
                        ->label('Bouton principal'),
                    Forms\Components\TextInput::make('data.cta.cta_secondary')
                        ->label('Bouton secondaire'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ];
    }

    protected function getContactSections(): array
    {
        return [
            // Hero Section
            Forms\Components\Section::make('Section Hero')
                ->schema([
                    Forms\Components\TextInput::make('data.hero.title')
                        ->label('Titre')
                        ->required(),
                    Forms\Components\TextInput::make('data.hero.highlight')
                        ->label('Texte en surbrillance'),
                    Forms\Components\Textarea::make('data.hero.description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible(),

            // Cards Section
            Forms\Components\Section::make('Cartes de contact')
                ->schema([
                    Forms\Components\TextInput::make('data.cards.email')
                        ->label('Adresse email')
                        ->email()
                        ->placeholder('contact@rezi.ci')
                        ->helperText('Email affiché sur la carte'),
                    Forms\Components\TextInput::make('data.cards.email_subtitle')
                        ->label('Sous-titre email')
                        ->placeholder('Réponse sous 24h'),
                    Forms\Components\TextInput::make('data.cards.phone')
                        ->label('Numéro de téléphone')
                        ->placeholder('+225 07 00 00 00 00')
                        ->helperText('Format affiché'),
                    Forms\Components\TextInput::make('data.cards.phone_raw')
                        ->label('Téléphone (format brut)')
                        ->placeholder('+22507000000000')
                        ->helperText('Sans espaces, pour les liens tel:'),
                    Forms\Components\TextInput::make('data.cards.phone_subtitle')
                        ->label('Sous-titre téléphone')
                        ->placeholder('Lun – Sam · 8h – 18h'),
                    Forms\Components\TextInput::make('data.cards.whatsapp_number')
                        ->label('Numéro WhatsApp')
                        ->placeholder('22507000000000')
                        ->helperText('Sans + ni espaces'),
                    Forms\Components\TextInput::make('data.cards.whatsapp_label')
                        ->label('Label WhatsApp')
                        ->placeholder('Discuter maintenant'),
                    Forms\Components\TextInput::make('data.cards.whatsapp_subtitle')
                        ->label('Sous-titre WhatsApp')
                        ->placeholder('Réponse rapide'),
                    Forms\Components\TextInput::make('data.cards.address_title')
                        ->label('Titre adresse')
                        ->placeholder('Bureau'),
                    Forms\Components\TextInput::make('data.cards.address_line1')
                        ->label('Adresse ligne 1')
                        ->placeholder('Cocody, Riviera Palmeraie'),
                    Forms\Components\TextInput::make('data.cards.address_line2')
                        ->label('Adresse ligne 2')
                        ->placeholder('Abidjan, Cocody Côte d\'Ivoire'),
                ])
                ->columns(2)
                ->collapsible(),

            // FAQ Section
            Forms\Components\Section::make('FAQ')
                ->schema([
                    Forms\Components\TextInput::make('data.faq.title')
                        ->label('Titre'),
                    Forms\Components\Textarea::make('data.faq.subtitle')
                        ->label('Sous-titre')
                        ->rows(2),
                    Forms\Components\Repeater::make('data.faq.items')
                        ->label('Questions')
                        ->schema([
                            Forms\Components\TextInput::make('question')
                                ->label('Question')
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('answer')
                                ->label('Réponse')
                                ->rows(3)
                                ->columnSpanFull(),
                        ])
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            // Hours Section
            Forms\Components\Section::make('Horaires')
                ->schema([
                    Forms\Components\TextInput::make('data.hours.title')
                        ->label('Titre'),
                    Forms\Components\Textarea::make('data.hours.note')
                        ->label('Note')
                        ->rows(2),
                    Forms\Components\Repeater::make('data.hours.items')
                        ->label('Horaires')
                        ->schema([
                            Forms\Components\TextInput::make('day')
                                ->label('Jour'),
                            Forms\Components\TextInput::make('hours')
                                ->label('Heures'),
                            Forms\Components\Toggle::make('open')
                                ->label('Ouvert'),
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),

            // CTA Section
            Forms\Components\Section::make('Section CTA')
                ->schema([
                    Forms\Components\TextInput::make('data.cta.title')
                        ->label('Titre'),
                    Forms\Components\Textarea::make('data.cta.description')
                        ->label('Description')
                        ->rows(2),
                    Forms\Components\TextInput::make('data.cta.cta_primary')
                        ->label('Bouton principal'),
                    Forms\Components\TextInput::make('data.cta.cta_secondary')
                        ->label('Bouton secondaire'),
                ])
                ->columns(2)
                ->collapsible()
                ->collapsed(),
        ];
    }

    protected function getHomeSections(): array
    {
        return [
            Forms\Components\Section::make('Contenu de la page d\'accueil')
                ->description('Éditez le contenu de la page d\'accueil')
                ->schema([
                    Forms\Components\Placeholder::make('info')
                        ->content('La page d\'accueil utilise des composants dynamiques. Contactez le développeur pour des modifications importantes.'),
                ]),
        ];
    }

    protected function getLegalSections(string $title): array
    {
        return [
            Forms\Components\Section::make("Contenu de la page {$title}")
                ->schema([
                    Forms\Components\TextInput::make('data.title')
                        ->label('Titre de la page')
                        ->required(),
                    Forms\Components\Repeater::make('data.sections')
                        ->label('Sections')
                        ->schema([
                            Forms\Components\TextInput::make('title')
                                ->label('Titre de la section')
                                ->required(),
                            Forms\Components\Textarea::make('content')
                                ->label('Contenu')
                                ->rows(5)
                                ->required(),
                        ])
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ];
    }

    protected function getFaqSections(): array
    {
        return [
            Forms\Components\Section::make('Informations générales')
                ->schema([
                    Forms\Components\TextInput::make('data.title')
                        ->label('Titre')
                        ->required(),
                    Forms\Components\TextInput::make('data.subtitle')
                        ->label('Sous-titre'),
                ])
                ->columns(2)
                ->collapsible(),

            Forms\Components\Section::make('Catégories de questions')
                ->schema([
                    Forms\Components\Repeater::make('data.categories')
                        ->label('Catégories')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Nom de la catégorie')
                                ->required(),
                            Forms\Components\TextInput::make('icon')
                                ->label('Icône (emoji)')
                                ->maxLength(10),
                            Forms\Components\Select::make('color')
                                ->label('Couleur')
                                ->options([
                                    'orange' => 'Orange',
                                    'blue' => 'Bleu',
                                    'emerald' => 'Vert',
                                    'purple' => 'Violet',
                                    'red' => 'Rouge',
                                ]),
                            Forms\Components\Repeater::make('questions')
                                ->label('Questions')
                                ->schema([
                                    Forms\Components\TextInput::make('q')
                                        ->label('Question')
                                        ->required()
                                        ->columnSpanFull(),
                                    Forms\Components\Textarea::make('a')
                                        ->label('Réponse')
                                        ->rows(3)
                                        ->required()
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['q'] ?? null)
                                ->columnSpanFull(),
                        ])
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ];
    }

    protected function getGuideSections(): array
    {
        return [
            Forms\Components\Section::make('Informations générales')
                ->schema([
                    Forms\Components\TextInput::make('data.title')
                        ->label('Titre')
                        ->required(),
                    Forms\Components\TextInput::make('data.subtitle')
                        ->label('Sous-titre'),
                ])
                ->columns(2)
                ->collapsible(),

            Forms\Components\Section::make('Étapes du guide')
                ->schema([
                    Forms\Components\Repeater::make('data.steps')
                        ->label('Étapes')
                        ->schema([
                            Forms\Components\TextInput::make('number')
                                ->label('Numéro')
                                ->numeric()
                                ->required(),
                            Forms\Components\TextInput::make('title')
                                ->label('Titre')
                                ->required(),
                            Forms\Components\Textarea::make('content')
                                ->label('Contenu')
                                ->rows(3)
                                ->required(),
                            Forms\Components\TextInput::make('tip')
                                ->label('Astuce (optionnel)'),
                            Forms\Components\Repeater::make('substeps')
                                ->label('Sous-étapes')
                                ->simple(
                                    Forms\Components\TextInput::make('text'),
                                )
                                ->columnSpanFull(),
                            Forms\Components\Repeater::make('tips')
                                ->label('Conseils')
                                ->simple(
                                    Forms\Components\TextInput::make('text'),
                                )
                                ->columnSpanFull(),
                            Forms\Components\Repeater::make('tools')
                                ->label('Outils')
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nom'),
                                    Forms\Components\TextInput::make('icon')
                                        ->label('Icône'),
                                    Forms\Components\TextInput::make('description')
                                        ->label('Description'),
                                ])
                                ->columns(3)
                                ->columnSpanFull(),
                        ])
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => isset($state['number']) ? "Étape {$state['number']}: {$state['title']}" : null)
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Contenu de la page mis à jour';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
