@props([
    'options' => [],
    'placeholder' => 'Sélectionner...',
    'selected' => null,
    'searchable' => false,
    'multiple' => false,
    'disabled' => false,
])

@php
    $id = $attributes->get('id') ?? $attributes->get('name') ?? 'select-' . uniqid();
@endphp

<div 
    x-data="{
        open: false,
        search: '',
        selected: {{ json_encode($multiple ? (array)$selected : $selected) }},
        multiple: {{ $multiple ? 'true' : 'false' }},
        
        get filteredOptions() {
            if (!this.search) return {{ json_encode($options) }};
            return {{ json_encode($options) }}.filter(opt => 
                opt.label.toLowerCase().includes(this.search.toLowerCase())
            );
        },
        
        isSelected(value) {
            if (this.multiple) {
                return this.selected.includes(value);
            }
            return this.selected === value;
        },
        
        toggle(value, label) {
            if (this.multiple) {
                const index = this.selected.indexOf(value);
                if (index > -1) {
                    this.selected.splice(index, 1);
                } else {
                    this.selected.push(value);
                }
            } else {
                this.selected = value;
                this.open = false;
            }
        },
        
        getLabel() {
            if (this.multiple && this.selected.length > 0) {
                return this.selected.length + ' sélectionné(s)';
            }
            const opt = {{ json_encode($options) }}.find(o => o.value === this.selected);
            return opt ? opt.label : '{{ $placeholder }}';
        },
        
        remove(value) {
            const index = this.selected.indexOf(value);
            if (index > -1) {
                this.selected.splice(index, 1);
            }
        }
    }"
    class="relative"
    @click.away="open = false"
>
    {{-- Hidden input for form submission --}}
    @if($multiple)
        <template x-for="val in selected" :key="val">
            <input type="hidden" name="{{ $attributes->get('name') }}[]" :value="val">
        </template>
    @else
        <input type="hidden" name="{{ $attributes->get('name') }}" x-model="selected">
    @endif
    
    {{-- Trigger button --}}
    <button
        type="button"
        @click="open = !open"
        {{ $disabled ? 'disabled' : '' }}
        class="relative w-full bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-left cursor-pointer focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-colors {{ $disabled ? 'bg-gray-50 cursor-not-allowed opacity-60' : 'hover:border-gray-300' }}"
    >
        <span class="block truncate text-sm" :class="selected && (Array.isArray(selected) ? selected.length : true) ? 'text-gray-900' : 'text-gray-500'" x-text="getLabel()"></span>
        <span class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </span>
    </button>
    
    {{-- Dropdown --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-1 w-full bg-white shadow-lg rounded-xl border border-gray-100 max-h-60 overflow-hidden"
        x-cloak
    >
        @if($searchable)
            {{-- Search input --}}
            <div class="p-2 border-b border-gray-100">
                <input
                    type="text"
                    x-model="search"
                    placeholder="Rechercher..."
                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent"
                    @click.stop
                >
            </div>
        @endif
        
        {{-- Options list --}}
        <ul class="py-1 overflow-auto max-h-48" role="listbox">
            <template x-for="option in filteredOptions" :key="option.value">
                <li
                    @click="toggle(option.value, option.label)"
                    :class="isSelected(option.value) ? 'bg-orange-50 text-orange-600' : 'text-gray-700 hover:bg-gray-50'"
                    class="px-4 py-2 text-sm cursor-pointer flex items-center justify-between"
                    role="option"
                >
                    <span x-text="option.label"></span>
                    <svg x-show="isSelected(option.value)" class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </li>
            </template>
            
            <li x-show="filteredOptions.length === 0" class="px-4 py-2 text-sm text-gray-500 text-center">
                Aucun résultat
            </li>
        </ul>
    </div>
    
    {{-- Selected tags (for multiple) --}}
    @if($multiple)
        <div x-show="selected.length > 0" class="flex flex-wrap gap-1 mt-2">
            <template x-for="val in selected" :key="val">
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-700 rounded-lg text-xs">
                    <span x-text="{{ json_encode($options) }}.find(o => o.value === val)?.label"></span>
                    <button type="button" @click.stop="remove(val)" class="hover:text-orange-900">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </span>
            </template>
        </div>
    @endif
</div>
