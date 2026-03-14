@props(['count' => 0])

@if($count > 0)
<span {{ $attributes->merge(['class' => 'inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1.5 text-xs font-bold text-white bg-red-500 rounded-full']) }}>
    {{ $count > 99 ? '99+' : $count }}
</span>
@endif
