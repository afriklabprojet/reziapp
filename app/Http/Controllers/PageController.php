<?php

namespace App\Http\Controllers;

use App\Models\PageContent;
use App\Models\Residence;
use App\Models\Review;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Conditions Générales d'Utilisation
     */
    public function cgu(): View
    {
        $page = PageContent::getBySlug('cgu');
        $content = $page ? $page->data : PageContent::defaultCguData();
        $metaTitle = $page?->meta_title ?? "Conditions Générales d'Utilisation – REZI";
        $metaDescription = $page?->meta_description ?? "Consultez les conditions générales d'utilisation de la plateforme REZI.";

        return view('pages.cgu', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Politique de Confidentialité
     */
    public function confidentialite(): View
    {
        $page = PageContent::getBySlug('confidentialite');
        $content = $page ? $page->data : PageContent::defaultConfidentialiteData();
        $metaTitle = $page?->meta_title ?? 'Politique de Confidentialité – REZI';
        $metaDescription = $page?->meta_description ?? 'Découvrez comment REZI collecte et protège vos données personnelles.';

        return view('pages.confidentialite', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Mentions Légales
     */
    public function mentionsLegales(): View
    {
        $page = PageContent::getBySlug('mentions-legales');
        $content = $page ? $page->data : PageContent::defaultMentionsLegalesData();
        $metaTitle = $page?->meta_title ?? 'Mentions Légales – REZI';
        $metaDescription = $page?->meta_description ?? 'Mentions légales de la plateforme REZI.';

        return view('pages.mentions-legales', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Foire Aux Questions
     */
    public function faq(): View
    {
        $page = PageContent::getBySlug('faq');
        $content = $page ? $page->data : PageContent::defaultFaqData();
        $metaTitle = $page?->meta_title ?? 'FAQ – Questions Fréquentes – REZI';
        $metaDescription = $page?->meta_description ?? 'Trouvez les réponses à vos questions sur REZI.';

        return view('pages.faq', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * À propos de REZI
     */
    public function about(): View
    {
        $cacheTtl = config('rezi.cache_ttl', 3600);

        $stats = Cache::remember('about_stats', $cacheTtl, function () {
            return [
                'residences' => Residence::listable()->count(),
                'owners' => Residence::listable()->distinct('owner_id')->count('owner_id'),
                'communes' => Residence::listable()->distinct('commune')->count('commune'),
                'reviews' => Review::where('status', 'approved')->count(),
            ];
        });

        $page = PageContent::getBySlug('about');
        $content = $page ? $page->data : PageContent::defaultAboutData();
        $metaTitle = $page?->meta_title ?? 'À propos de REZI – Location meublée en Afrique de l\'Ouest';
        $metaDescription = $page?->meta_description ?? "Découvrez REZI, la plateforme de référence pour la location de résidences meublées en Afrique de l'Ouest.";

        return view('pages.about', compact('stats', 'content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Guide propriétaire
     */
    public function guideProprietaire(): View
    {
        $page = PageContent::getBySlug('guide-proprietaire');
        $content = $page ? $page->data : PageContent::defaultGuideProprietaireData();
        $metaTitle = $page?->meta_title ?? 'Guide Propriétaire – REZI';
        $metaDescription = $page?->meta_description ?? 'Guide complet pour publier et gérer votre résidence sur REZI.';

        return view('pages.guide-proprietaire', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Nous contacter
     */
    public function contact(): View
    {
        $page = PageContent::getBySlug('contact');
        $content = $page ? $page->data : PageContent::defaultContactData();
        $metaTitle = $page?->meta_title ?? 'Nous Contacter – REZI';
        $metaDescription = $page?->meta_description ?? "Contactez l'équipe REZI pour toute question sur la location meublée.";

        return view('pages.contact', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Tarifs & Modèle économique REZI
     */
    public function tarifs(): View
    {
        $content = \App\Models\PageContent::getBySlug('tarifs');

        $metaTitle       = $content?->meta_title       ?? 'Tarifs REZI – Publication gratuite, 0 commission';
        $metaDescription = $content?->meta_description ?? 'Publiez votre résidence meublée gratuitement sur REZI. Aucune commission sur la mise en location, options de visibilité disponibles.';

        $boostPlans = $content?->data['boost_plans'] ?? \App\Models\PageContent::defaultTarifsData()['boost_plans'];
        $faqItems   = $content?->data['faq']         ?? \App\Models\PageContent::defaultTarifsData()['faq'];

        return view('pages.tarifs', compact('metaTitle', 'metaDescription', 'boostPlans', 'faqItems'));
    }
}
