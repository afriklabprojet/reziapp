<?php

namespace App\Livewire;

use App\Models\Residence;
use Illuminate\Support\Collection;
use Livewire\Component;

class ResidenceComparator extends Component
{
    public array $residenceIds = [];
    public Collection $residences;
    public int $maxCompare = 4;

    protected $listeners = [
        'addToCompare',
        'removeFromCompare',
        'clearComparison',
    ];

    public function mount()
    {
        $this->residenceIds = session('compare_residences', []);
        $this->loadResidences();
    }

    public function loadResidences(): void
    {
        if (empty($this->residenceIds)) {
            $this->residences = collect();
            return;
        }

        $this->residences = Residence::whereIn('id', $this->residenceIds)
            ->with(['photos', 'amenities', 'reviews', 'category'])
            ->get()
            ->sortBy(function ($residence) {
                return array_search($residence->id, $this->residenceIds);
            });
    }

    public function addToCompare(int $residenceId): void
    {
        if (count($this->residenceIds) >= $this->maxCompare) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "Maximum {$this->maxCompare} résidences à comparer",
            ]);
            return;
        }

        if (in_array($residenceId, $this->residenceIds)) {
            $this->dispatch('notify', [
                'type' => 'info',
                'message' => 'Déjà dans la comparaison',
            ]);
            return;
        }

        $this->residenceIds[] = $residenceId;
        session(['compare_residences' => $this->residenceIds]);
        $this->loadResidences();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Ajouté à la comparaison',
        ]);

        $this->dispatch('compare-updated', count: count($this->residenceIds));
    }

    public function removeFromCompare(int $residenceId): void
    {
        $this->residenceIds = array_values(array_diff($this->residenceIds, [$residenceId]));
        session(['compare_residences' => $this->residenceIds]);
        $this->loadResidences();

        $this->dispatch('compare-updated', count: count($this->residenceIds));
    }

    public function clearComparison(): void
    {
        $this->residenceIds = [];
        session()->forget('compare_residences');
        $this->residences = collect();

        $this->dispatch('compare-updated', count: 0);
    }

    public function getComparisonData(): array
    {
        if ($this->residences->isEmpty()) {
            return [];
        }

        $criteria = [
            'basic' => [
                'label' => 'Informations générales',
                'items' => [
                    'type' => ['label' => 'Type', 'format' => 'text'],
                    'location' => ['label' => 'Localisation', 'format' => 'text'],
                    'surface' => ['label' => 'Surface', 'format' => 'surface'],
                    'bedrooms' => ['label' => 'Chambres', 'format' => 'number'],
                    'bathrooms' => ['label' => 'Salles de bain', 'format' => 'number'],
                    'capacity' => ['label' => 'Capacité max', 'format' => 'persons'],
                ],
            ],
            'pricing' => [
                'label' => 'Tarifs',
                'items' => [
                    'price_per_night' => ['label' => 'Prix/nuit', 'format' => 'currency'],
                    'price_per_week' => ['label' => 'Prix/semaine', 'format' => 'currency'],
                    'price_per_month' => ['label' => 'Prix/mois', 'format' => 'currency'],
                    'cleaning_fee' => ['label' => 'Frais ménage', 'format' => 'currency'],
                    'caution' => ['label' => 'Caution', 'format' => 'currency'],
                ],
            ],
            'ratings' => [
                'label' => 'Évaluations',
                'items' => [
                    'avg_rating' => ['label' => 'Note moyenne', 'format' => 'rating'],
                    'reviews_count' => ['label' => 'Nombre d\'avis', 'format' => 'number'],
                ],
            ],
            'rules' => [
                'label' => 'Règlement',
                'items' => [
                    'min_nights' => ['label' => 'Nuits minimum', 'format' => 'nights'],
                    'pets_allowed' => ['label' => 'Animaux', 'format' => 'boolean'],
                    'smoking_allowed' => ['label' => 'Fumeurs', 'format' => 'boolean'],
                    'events_allowed' => ['label' => 'Événements', 'format' => 'boolean'],
                ],
            ],
        ];

        $data = [];

        foreach ($criteria as $section => $sectionData) {
            $data[$section] = [
                'label' => $sectionData['label'],
                'rows' => [],
            ];

            foreach ($sectionData['items'] as $field => $fieldData) {
                $row = [
                    'label' => $fieldData['label'],
                    'format' => $fieldData['format'],
                    'values' => [],
                ];

                foreach ($this->residences as $residence) {
                    $row['values'][] = $this->getFieldValue($residence, $field);
                }

                $data[$section]['rows'][] = $row;
            }
        }

        // Ajouter les équipements
        $data['amenities'] = $this->getAmenitiesComparison();

        return $data;
    }

    protected function getFieldValue(Residence $residence, string $field): mixed
    {
        return match ($field) {
            'type' => $residence->category?->name ?? ($residence->type ?? 'Non spécifié'),
            'location' => ($residence->commune ?? '') . ', ' . ($residence->city ?? ''),
            'surface' => $residence->surface_area,
            'bedrooms' => $residence->bedrooms,
            'bathrooms' => $residence->bathrooms,
            'capacity' => $residence->max_guests,
            'price_per_night' => $residence->price_per_night,
            'price_per_week' => $residence->price_per_week,
            'price_per_month' => $residence->price_per_month,
            'cleaning_fee' => 0,
            'caution' => 0,
            'avg_rating' => $residence->average_rating ?? 0,
            'reviews_count' => $residence->reviews->count(),
            'min_nights' => $residence->min_nights ?? 1,
            'pets_allowed' => $residence->pets_allowed ?? false,
            'smoking_allowed' => $residence->smoking_allowed ?? false,
            'events_allowed' => $residence->parties_allowed ?? false,
            default => null,
        };
    }

    protected function getAmenitiesComparison(): array
    {
        // Collecter tous les équipements uniques
        $allAmenities = collect();
        
        foreach ($this->residences as $residence) {
            foreach ($residence->amenities as $amenity) {
                $allAmenities->put($amenity->id, [
                    'id' => $amenity->id,
                    'name' => $amenity->name,
                    'icon' => $amenity->icon,
                ]);
            }
        }

        $amenities = $allAmenities->sortBy('name')->values();

        $rows = [];
        foreach ($amenities as $amenity) {
            $row = [
                'label' => $amenity['name'],
                'icon' => $amenity['icon'],
                'format' => 'boolean',
                'values' => [],
            ];

            foreach ($this->residences as $residence) {
                $row['values'][] = $residence->amenities->contains('id', $amenity['id']);
            }

            $rows[] = $row;
        }

        return [
            'label' => 'Équipements',
            'rows' => $rows,
        ];
    }

    public function render()
    {
        return view('livewire.residence-comparator', [
            'comparisonData' => $this->getComparisonData(),
        ]);
    }
}
