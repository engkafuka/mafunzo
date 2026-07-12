@props(['checklist' => []])

<ul {{ $attributes->merge(['class' => 'space-y-2 text-sm']) }}>
    @foreach($checklist as $item)
        <li class="flex items-start gap-2">
            @if($item['met'])
                <span class="mt-0.5 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-green-100 text-green-700" aria-hidden="true">✓</span>
                <span class="text-green-800">{{ $item['label'] }}</span>
            @else
                <span class="mt-0.5 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700" aria-hidden="true">!</span>
                <span class="text-amber-900">{{ $item['label'] }}</span>
            @endif
        </li>
    @endforeach
</ul>
