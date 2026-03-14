{{--
    Comparison row partial
    @param string $label
    @param \Illuminate\Support\Collection $values  - formatted display values
    @param string $highlight  - 'max', 'min', or 'none'
    @param \Illuminate\Support\Collection $rawValues - numeric raw values for highlighting
--}}
@php
    $bestIdx = null;
    if ($highlight !== 'none' && $rawValues->filter()->isNotEmpty()) {
        if ($highlight === 'max') {
            $bestVal = $rawValues->max();
        } else {
            $bestVal = $rawValues->filter(fn($v) => $v > 0)->min();
        }
        // Find the index of the best value
        foreach ($rawValues as $i => $val) {
            if ($val == $bestVal && $bestVal > 0) {
                $bestIdx = $i;
                break;
            }
        }
    }
@endphp

<tr class="hover:bg-gray-50/50 transition-colors">
    <td class="sticky left-0 z-10 bg-white px-5 py-3 text-sm text-gray-600 font-medium whitespace-nowrap">
        {{ $label }}
    </td>
    @foreach ($values as $i => $value)
        <td
            class="px-5 py-3 text-center text-sm {{ $i === $bestIdx ? 'font-semibold text-gray-900' : 'text-gray-600' }}">
            <span class="inline-flex items-center gap-1">
                {{ $value }}
                @if ($i === $bestIdx)
                    <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                            clip-rule="evenodd" />
                    </svg>
                @endif
            </span>
        </td>
    @endforeach
</tr>
