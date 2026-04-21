<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Guidebook;
use App\Models\User;

class GuidebookService
{
    public function create(User $user, array $data): Guidebook
    {
        $data['user_id'] = $user->id;

        $guidebook = Guidebook::create($data);

        // Créer des sections par défaut
        $this->createDefaultSections($guidebook);

        return $guidebook;
    }

    public function update(Guidebook $guidebook, array $data): Guidebook
    {
        $guidebook->update($data);

        return $guidebook->fresh();
    }

    private function createDefaultSections(Guidebook $guidebook): void
    {
        $defaults = [
            ['title' => 'Arrivée & Accès', 'icon' => 'key', 'content' => 'Instructions d\'accès au logement...', 'sort_order' => 1],
            ['title' => 'Équipements', 'icon' => 'tv', 'content' => 'Liste des équipements disponibles...', 'sort_order' => 2],
            ['title' => 'Règles de la maison', 'icon' => 'clipboard-list', 'content' => 'Merci de respecter les règles suivantes...', 'sort_order' => 3],
        ];

        foreach ($defaults as $section) {
            $guidebook->sections()->create($section);
        }
    }

    public function addRecommendation(Guidebook $guidebook, array $data): void
    {
        $guidebook->recommendations()->create($data);
    }

    public function publish(Guidebook $guidebook): void
    {
        $guidebook->update(['is_published' => true]);
    }

    public function unpublish(Guidebook $guidebook): void
    {
        $guidebook->update(['is_published' => false]);
    }
}
