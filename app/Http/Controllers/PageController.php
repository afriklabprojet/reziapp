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
        $metaTitle = $page?->meta_title ?? "Conditions Générales d'Utilisation – Rezi App";
        $metaDescription = $page?->meta_description ?? "Consultez les conditions générales d'utilisation de la plateforme Rezi App.";

        return view('pages.cgu', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Politique de Confidentialité
     */
    public function confidentialite(): View
    {
        $page = PageContent::getBySlug('confidentialite');
        $content = $page ? $page->data : PageContent::defaultConfidentialiteData();
        $metaTitle = $page?->meta_title ?? 'Politique de Confidentialité – Rezi App';
        $metaDescription = $page?->meta_description ?? 'Découvrez comment Rezi App collecte et protège vos données personnelles.';

        return view('pages.confidentialite', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Mentions Légales
     */
    public function mentionsLegales(): View
    {
        $page = PageContent::getBySlug('mentions-legales');
        $content = $page ? $page->data : PageContent::defaultMentionsLegalesData();
        $metaTitle = $page?->meta_title ?? 'Mentions Légales – Rezi App';
        $metaDescription = $page?->meta_description ?? 'Mentions légales de la plateforme Rezi App.';

        return view('pages.mentions-legales', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Foire Aux Questions
     */
    public function faq(): View
    {
        $page = PageContent::getBySlug('faq');
        $content = $page ? $page->data : PageContent::defaultFaqData();
        $metaTitle = $page?->meta_title ?? 'FAQ – Questions Fréquentes – Rezi App';
        $metaDescription = $page?->meta_description ?? 'Trouvez les réponses à vos questions sur Rezi App.';

        return view('pages.faq', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * À propos de Rezi App
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
        $metaTitle = $page?->meta_title ?? 'À propos de Rezi App – Location meublée en Afrique de l\'Ouest';
        $metaDescription = $page?->meta_description ?? "Découvrez Rezi App, la plateforme de référence pour la location de résidences meublées en Afrique de l'Ouest.";

        return view('pages.about', compact('stats', 'content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Guide propriétaire
     */
    public function guideProprietaire(): View
    {
        $page = PageContent::getBySlug('guide-proprietaire');
        $content = $page ? $page->data : PageContent::defaultGuideProprietaireData();
        $metaTitle = $page?->meta_title ?? 'Guide Propriétaire – Rezi App';
        $metaDescription = $page?->meta_description ?? 'Guide complet pour publier et gérer votre résidence sur Rezi App.';

        return view('pages.guide-proprietaire', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Nous contacter
     */
    public function contact(): View
    {
        $page = PageContent::getBySlug('contact');
        $content = $page ? $page->data : PageContent::defaultContactData();
        $metaTitle = $page?->meta_title ?? 'Nous Contacter – Rezi App';
        $metaDescription = $page?->meta_description ?? "Contactez l'équipe Rezi App pour toute question sur la location meublée.";

        return view('pages.contact', compact('content', 'metaTitle', 'metaDescription'));
    }

    /**
     * Tarifs & Modèle économique Rezi App
     */
    public function tarifs(): View
    {
        $content = \App\Models\PageContent::getBySlug('tarifs');

        $metaTitle = 'Tarifs Rezi App – 10% de commission propriétaire par réservation';
        $metaDescription = 'Rezi App ne fonctionne pas par abonnement. Les locataires ne paient aucun frais de plateforme et les propriétaires paient 10% sur le montant total de chaque réservation confirmée.';

        $boostPlans = $content?->data['boost_plans'] ?? \App\Models\PageContent::defaultTarifsData()['boost_plans'];
        $faqItems = \App\Models\PageContent::defaultTarifsData()['faq'];

        return view('pages.tarifs', compact('metaTitle', 'metaDescription', 'boostPlans', 'faqItems'));
    }
}
