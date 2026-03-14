@extends('layouts.app')

@section('title', 'Comparaison partagée')

@section('content')
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Comparaison de résidences</h1>
            <p class="text-gray-600 mt-1">{{ $residences->count() }} résidence(s) comparées</p>
        </div>

        @if ($residences->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune résidence à comparer</h3>
                <p class="text-gray-600">Cette comparaison ne contient pas de résidences.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full bg-white rounded-xl shadow-sm border">
                    <thead>
                        <tr class="border-b">
                            <th class="p-4 text-left text-sm font-medium text-gray-500 w-40">Critère</th>
                            @foreach ($residences as $residence)
                                <th class="p-4 text-center min-w-55">
                                    <div class="space-y-2">
                                        @if ($residence->photos && $residence->photos->first())
                                            <img src="{{ Storage::url($residence->photos->first()->path) }}"
                                                alt="{{ $residence->title }}" class="w-full h-32 object-cover rounded-lg"
                                                loading="lazy">
                                        @else
                                            <div
                                                class="w-full h-32 bg-gray-100 rounded-lg flex items-center justify-center">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                        <a href="{{ route('residences.show', $residence) }}"
                                            class="text-sm font-semibold text-gray-900 hover:text-primary-600">{{ $residence->title }}</a>
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr>
                            <td class="p-4 text-sm font-medium text-gray-500">Prix / nuit</td>
                            @foreach ($residences as $residence)
                                <td class="p-4 text-center text-sm font-bold text-primary-600">
                                    {{ number_format($residence->price, 0, ',', ' ') }} FCFA/{{ $residence->price_label }}
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="p-4 text-sm font-medium text-gray-500">Commune</td>
                            @foreach ($residences as $residence)
                                <td class="p-4 text-center text-sm text-gray-700">{{ $residence->commune ?? '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="p-4 text-sm font-medium text-gray-500">Type</td>
                            @foreach ($residences as $residence)
                                <td class="p-4 text-center text-sm text-gray-700">{{ $residence->category->name ?? '—' }}
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="p-4 text-sm font-medium text-gray-500">Chambres</td>
                            @foreach ($residences as $residence)
                                <td class="p-4 text-center text-sm text-gray-700">{{ $residence->bedrooms ?? '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="p-4 text-sm font-medium text-gray-500">Voyageurs max</td>
                            @foreach ($residences as $residence)
                                <td class="p-4 text-center text-sm text-gray-700">{{ $residence->max_guests ?? '—' }}</td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="p-4 text-sm font-medium text-gray-500">Note moyenne</td>
                            @foreach ($residences as $residence)
                                <td class="p-4 text-center text-sm text-gray-700">
                                    @if ($residence->average_rating)
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                            </svg>
                                            {{ number_format($residence->average_rating, 1) }}/5
                                        </span>
                                    @else
                                        <span class="text-gray-400">Pas de note</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        <tr>
                            <td class="p-4 text-sm font-medium text-gray-500">Équipements</td>
                            @foreach ($residences as $residence)
                                <td class="p-4 text-center text-sm text-gray-700">
                                    @if ($residence->amenities && $residence->amenities->count())
                                        <div class="flex flex-wrap justify-center gap-1">
                                            @foreach ($residence->amenities->take(5) as $amenity)
                                                <span
                                                    class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded-full">{{ $amenity->name }}</span>
                                            @endforeach
                                            @if ($residence->amenities->count() > 5)
                                                <span
                                                    class="text-xs text-gray-400">+{{ $residence->amenities->count() - 5 }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
