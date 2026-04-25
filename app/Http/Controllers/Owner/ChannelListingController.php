<?php

declare(strict_types=1);

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\ChannelListing;
use App\Models\Residence;
use App\Services\ChannelManagerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChannelListingController extends Controller
{
    public function __construct(private ChannelManagerService $service) {}

    public function index(Residence $residence): View
    {
        abort_unless($residence->owner_id === Auth::id(), 403);

        $listings = $residence->channelListings()->get()->keyBy('channel');
        $available = ['airbnb' => 'Airbnb', 'booking' => 'Booking.com', 'expedia' => 'Expedia', 'vrbo' => 'Vrbo'];

        return view('owner.channels.index', compact('residence', 'listings', 'available'));
    }

    public function store(Request $request, Residence $residence): RedirectResponse
    {
        abort_unless($residence->owner_id === Auth::id(), 403);

        $validated = $request->validate([
            'channel' => ['required', 'in:airbnb,booking,expedia,vrbo'],
            'external_id' => ['nullable', 'string', 'max:120'],
        ]);

        $this->service->connect($residence, $validated['channel'], $validated['external_id'] ?? null);

        return back()->with('success', 'Canal '.$validated['channel'].' connecté. Synchronisation initiale en attente.');
    }

    public function sync(ChannelListing $listing): RedirectResponse
    {
        abort_unless($listing->residence->owner_id === Auth::id(), 403);

        $this->service->pushAvailability($listing);
        $this->service->pushPrice($listing);

        return back()->with('success', 'Synchronisation déclenchée pour '.$listing->channelLabel().'.');
    }

    public function destroy(ChannelListing $listing): RedirectResponse
    {
        abort_unless($listing->residence->owner_id === Auth::id(), 403);

        $this->service->disconnect($listing);
        $listing->delete();

        return back()->with('success', 'Canal déconnecté.');
    }
}
