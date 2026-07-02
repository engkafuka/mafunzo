@props([
    'id' => null,
    'name',
    'required' => false,
    'step' => null,
    'showExistingNote' => false,
    'initialFilename' => '',
])

@if($showExistingNote)
    <p class="mt-1 text-xs text-gray-600">{{ __('Current certificate on file. Upload a new file only if you want to replace it.') }}</p>
@endif

<label class="mt-1.5 flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 px-3 py-2 transition hover:border-[#0a71ab]/50 hover:bg-[#0a71ab]/5">
    <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
    </svg>
    <div class="min-w-0 flex-1 text-left">
        <span class="text-xs text-gray-600">{{ __('Click to upload') }} · {{ __('PDF or image, max 5MB') }}</span>
        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            <span data-filename class="certificate-upload-filename mt-0.5 block truncate text-xs font-medium text-[#0a71ab]">{{ $initialFilename }}</span>
        @endif
    </div>
    <span class="shrink-0 rounded border border-gray-200 bg-white px-2 py-0.5 text-xs font-medium text-gray-600">{{ __('Browse') }}</span>
    <input
        type="file"
        accept=".pdf,.jpg,.jpeg,.png"
        class="sr-only"
        @if($id) id="{{ $id }}" @endif
        name="{{ $name }}"
        @if($step) data-step="{{ $step }}" @endif
        @if($required) required @endif
        onchange="const el = this.closest('label')?.querySelector('[data-filename]'); if (el) { el.textContent = this.files[0]?.name || ''; }"
        {{ $attributes }}
    >
</label>
