@extends('layouts.owner')

@section('title', $request->reference . ' — Maintenance — Rezi App')

@section('owner-content')
<div class="max-w-3xl mx-auto space-y-6">
    <div>
        <a href="{{ route('owner.maintenance.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            Retour
        </a>
    </div>

    {{-- Header --}}
    <div class="bg-white rounded-2xl border border-gray-100 p-6">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-mono text-gray-400">{{ $request->reference }}</span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold uppercase bg-{{ $request->status_color }}-100 text-{{ $request->status_color }}-700">{{ $request->status_label }}</span>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold uppercase bg-{{ $request->priority_color }}-100 text-{{ $request->priority_color }}-700">{{ $request->priority_label }}</span>
                </div>
                <h1 class="text-xl font-bold text-gray-900 mt-2">{{ $request->title }}</h1>
                <p class="text-sm text-gray-500 mt-1">{{ $request->residence?->name ?? '—' }} · {{ $request->category_label }} · {{ $request->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <div class="mt-4 p-4 bg-gray-50 rounded-xl">
            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $request->description }}</p>
        </div>

        @if($request->photos && count($request->photos))
        <div class="mt-4">
            <p class="text-sm font-semibold text-gray-700 mb-2">Photos</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2">
                @foreach($request->photos as $photo)
                <a href="{{ Storage::url($photo) }}" target="_blank" class="aspect-square rounded-xl bg-gray-100 overflow-hidden hover:opacity-80 transition-opacity">
                    <img src="{{ Storage::url($photo) }}" alt="Photo" class="w-full h-full object-cover">
                </a>
                @endforeach
            </div>
        </div>
        @endif

        @if($request->assignee)
        <div class="mt-4 flex items-center gap-2">
            <span class="text-sm text-gray-500">Assigné à :</span>
            <span class="text-sm font-semibold text-gray-700">{{ $request->assignee->name }}</span>
        </div>
        @endif

        @if($request->resolved_at)
        <div class="mt-3 flex items-center gap-2">
            <span class="text-sm text-gray-500">Résolu le :</span>
            <span class="text-sm font-semibold text-green-700">{{ $request->resolved_at->format('d/m/Y H:i') }}</span>
        </div>
        @endif

        @if($request->cost)
        <div class="mt-3 flex items-center gap-2">
            <span class="text-sm text-gray-500">Coût :</span>
            <span class="text-sm font-bold text-gray-900">{{ number_format($request->cost, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
    </div>

    {{-- Actions --}}
    @if($request->status !== 'resolved' && $request->status !== 'cancelled')
    <div class="bg-white rounded-2xl border border-gray-100 p-6 space-y-4">
        <h2 class="font-semibold text-gray-900">Actions</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Update Status --}}
            <form method="POST" action="{{ route('owner.maintenance.status', $request) }}" class="space-y-3">
                @csrf @method('PATCH')
                <label class="block text-sm font-semibold text-gray-700">Changer le statut</label>
                <select name="status" class="w-full rounded-xl border-gray-200 focus:ring-[#F16A00] text-sm">
                    @foreach(\App\Models\MaintenanceRequest::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ $request->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="flex gap-2">
                    <input type="number" name="cost" placeholder="Coût (FCFA)" value="{{ old('cost', $request->cost) }}" class="flex-1 rounded-xl border-gray-200 focus:ring-[#F16A00] text-sm">
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-semibold rounded-xl hover:bg-gray-800 transition-colors">Mettre à jour</button>
                </div>
            </form>

            {{-- Assign --}}
            <form method="POST" action="{{ route('owner.maintenance.assign', $request) }}" class="space-y-3">
                @csrf @method('PATCH')
                <label class="block text-sm font-semibold text-gray-700">Assigner à</label>
                <input type="text" name="assigned_to_name" placeholder="Nom du technicien" value="{{ old('assigned_to_name') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#F16A00] text-sm">
                <input type="text" name="assigned_to_phone" placeholder="Téléphone" value="{{ old('assigned_to_phone') }}" class="w-full rounded-xl border-gray-200 focus:ring-[#F16A00] text-sm">
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition-colors">Assigner</button>
            </form>
        </div>
    </div>
    @endif
</div>
@endsection
