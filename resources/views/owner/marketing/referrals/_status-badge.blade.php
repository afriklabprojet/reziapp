@php
    $badgeConfig = match($status) {
        'pending' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'ring' => 'ring-amber-600/20', 'dot' => 'bg-amber-500', 'label' => 'En attente'],
        'qualified' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'ring' => 'ring-blue-600/20', 'dot' => 'bg-blue-500', 'label' => 'Qualifié'],
        'rewarded' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'ring' => 'ring-green-600/20', 'dot' => 'bg-green-500', 'label' => 'Récompensé'],
        'cancelled' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'ring' => 'ring-red-600/20', 'dot' => 'bg-red-500', 'label' => 'Annulé'],
        default => ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'ring' => 'ring-gray-600/20', 'dot' => 'bg-gray-500', 'label' => $status],
    };
@endphp
<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $badgeConfig['bg'] }} {{ $badgeConfig['text'] }} ring-1 ring-inset {{ $badgeConfig['ring'] }}">
    <span class="w-1.5 h-1.5 rounded-full {{ $badgeConfig['dot'] }}"></span>
    {{ $badgeConfig['label'] }}
</span>
