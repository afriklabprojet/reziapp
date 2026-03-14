<?php

namespace App\Livewire\Owner;

use App\Models\AvailabilityCalendar;
use App\Models\Residence;
use App\Models\SeasonalPricing;
use Illuminate\Support\Carbon;
use Livewire\Component;

class AvailabilityManager extends Component
{
    public Residence $residence;
    public string $currentMonth;
    public array $calendar = [];
    public array $blockedDates = [];
    public array $seasonalPricing = [];

    // Pour le formulaire de blocage
    public ?string $blockStartDate = null;
    public ?string $blockEndDate = null;
    public ?string $blockNote = null;

    // Pour le formulaire de prix personnalisé
    public ?string $priceStartDate = null;
    public ?string $priceEndDate = null;
    public ?float $customPrice = null;

    // Pour le formulaire de tarif saisonnier
    public ?string $seasonName = null;
    public ?string $seasonStartDate = null;
    public ?string $seasonEndDate = null;
    public ?float $seasonPricePerDay = null;
    public float $seasonMultiplier = 1.0;
    public int $seasonMinNights = 1;

    protected $rules = [
        'blockStartDate' => 'required|date',
        'blockEndDate' => 'required|date|after_or_equal:blockStartDate',
        'blockNote' => 'nullable|string|max:255',
        'priceStartDate' => 'required|date',
        'priceEndDate' => 'required|date|after_or_equal:priceStartDate',
        'customPrice' => 'required|numeric|min:0',
        'seasonName' => 'required|string|max:100',
        'seasonStartDate' => 'required|date',
        'seasonEndDate' => 'required|date|after:seasonStartDate',
        'seasonPricePerDay' => 'nullable|numeric|min:0',
        'seasonMultiplier' => 'required|numeric|min:0.1|max:5',
        'seasonMinNights' => 'required|integer|min:1',
    ];

    public function mount(Residence $residence)
    {
        abort_unless(
            auth()->check() && auth()->id() === $residence->owner_id,
            403,
            'Vous n\'êtes pas autorisé à gérer cette résidence.'
        );

        $this->residence = $residence;
        $this->currentMonth = now()->format('Y-m');
        $this->loadCalendar();
        $this->loadSeasonalPricing();
    }

    public function loadCalendar()
    {
        $startDate = Carbon::parse($this->currentMonth)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $this->calendar = AvailabilityCalendar::getCalendar(
            $this->residence->id,
            $startDate,
            $endDate
        )->toArray();

        $this->blockedDates = AvailabilityCalendar::getBlockedDates(
            $this->residence->id,
            $startDate,
            $endDate
        )->toArray();
    }

    public function loadSeasonalPricing()
    {
        $this->seasonalPricing = SeasonalPricing::where('residence_id', $this->residence->id)
            ->where('is_active', true)
            ->orderBy('start_date')
            ->get()
            ->toArray();
    }

    public function previousMonth()
    {
        $this->currentMonth = Carbon::parse($this->currentMonth)->subMonth()->format('Y-m');
        $this->loadCalendar();
    }

    public function nextMonth()
    {
        $this->currentMonth = Carbon::parse($this->currentMonth)->addMonth()->format('Y-m');
        $this->loadCalendar();
    }

    public function blockDates()
    {
        $this->validate([
            'blockStartDate' => 'required|date',
            'blockEndDate' => 'required|date|after_or_equal:blockStartDate',
            'blockNote' => 'nullable|string|max:255',
        ]);

        AvailabilityCalendar::blockDates(
            $this->residence->id,
            Carbon::parse($this->blockStartDate),
            Carbon::parse($this->blockEndDate),
            $this->blockNote
        );

        $this->reset(['blockStartDate', 'blockEndDate', 'blockNote']);
        $this->loadCalendar();
        $this->dispatch('notify', type: 'success', message: 'Dates bloquées avec succès');
    }

    public function unblockDate(string $date)
    {
        $dateCarbon = Carbon::parse($date);
        AvailabilityCalendar::unblockDates($this->residence->id, $dateCarbon, $dateCarbon);
        $this->loadCalendar();
        $this->dispatch('notify', type: 'success', message: 'Date débloquée');
    }

    public function setCustomPrice()
    {
        $this->validate([
            'priceStartDate' => 'required|date',
            'priceEndDate' => 'required|date|after_or_equal:priceStartDate',
            'customPrice' => 'required|numeric|min:0',
        ]);

        AvailabilityCalendar::setCustomPrice(
            $this->residence->id,
            Carbon::parse($this->priceStartDate),
            Carbon::parse($this->priceEndDate),
            $this->customPrice
        );

        $this->reset(['priceStartDate', 'priceEndDate', 'customPrice']);
        $this->loadCalendar();
        $this->dispatch('notify', type: 'success', message: 'Prix personnalisé défini');
    }

    public function addSeasonalPricing()
    {
        $this->validate([
            'seasonName' => 'required|string|max:100',
            'seasonStartDate' => 'required|date',
            'seasonEndDate' => 'required|date|after:seasonStartDate',
            'seasonPricePerDay' => 'nullable|numeric|min:0',
            'seasonMultiplier' => 'required|numeric|min:0.1|max:5',
            'seasonMinNights' => 'required|integer|min:1',
        ]);

        SeasonalPricing::create([
            'residence_id' => $this->residence->id,
            'name' => $this->seasonName,
            'start_date' => $this->seasonStartDate,
            'end_date' => $this->seasonEndDate,
            'price_per_day' => $this->seasonPricePerDay,
            'price_multiplier' => $this->seasonMultiplier,
            'min_nights' => $this->seasonMinNights,
        ]);

        $this->reset(['seasonName', 'seasonStartDate', 'seasonEndDate', 'seasonPricePerDay']);
        $this->seasonMultiplier = 1.0;
        $this->seasonMinNights = 1;
        $this->loadSeasonalPricing();
        $this->dispatch('notify', type: 'success', message: 'Tarif saisonnier ajouté');
    }

    public function deleteSeasonalPricing(int $id)
    {
        SeasonalPricing::where('id', $id)
            ->where('residence_id', $this->residence->id)
            ->delete();
        
        $this->loadSeasonalPricing();
        $this->dispatch('notify', type: 'success', message: 'Tarif saisonnier supprimé');
    }

    public function importTemplate(string $templateKey)
    {
        $year = (int) Carbon::parse($this->currentMonth)->format('Y');
        $seasonal = SeasonalPricing::createFromTemplate($this->residence->id, $templateKey, $year);
        
        if ($seasonal) {
            $this->loadSeasonalPricing();
            $this->dispatch('notify', type: 'success', message: 'Template importé: ' . $seasonal->name);
        }
    }

    public function getMonthNameProperty(): string
    {
        return Carbon::parse($this->currentMonth)->translatedFormat('F Y');
    }

    public function getTemplatesProperty(): array
    {
        return SeasonalPricing::getSeasonTemplates();
    }

    public function render()
    {
        return view('livewire.owner.availability-manager');
    }
}
