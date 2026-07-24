@props([
    'alpine' => true,
    'step' => 3,
    'includeWrrb' => false,
])

@php
    $levelOptions = \App\Models\EducationBackground::levelOptions(includeWrrb: (bool) $includeWrrb);
    $programOptions = \App\Models\EducationBackground::programOptions();
    $wrrbLevel = \App\Models\EducationBackground::LEVEL_WRRB_CERTIFICATE;
@endphp

<div class="space-y-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">{{ __('Education background') }}</h2>
            @if($includeWrrb)
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('Add your WRRB Certificate (mandatory). You may also add other qualifications. Each entry requires a certificate upload.') }}
                </p>
            @else
                <p class="mt-1 text-sm text-gray-500">
                    {{ __('Add all relevant qualifications. Each entry requires a certificate certified by an advocate.') }}
                </p>
            @endif
        </div>
        @if($alpine)
            <button type="button" @click="addEducation()"
                    class="shrink-0 inline-flex items-center rounded-lg border border-[#0a71ab] px-3 py-2 text-sm font-medium text-[#0a71ab] hover:bg-[#0a71ab]/10 transition">
                {{ __('Add education') }}
            </button>
        @endif
    </div>

    <x-input-error :messages="$errors->get('education')" class="mt-1" />

    @if($alpine)
        <template x-for="(entry, index) in educationEntries" :key="entry.id">
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4 space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-sm font-semibold text-gray-900" x-text="'{{ __('Education') }} ' + (index + 1)"></h3>
                    <button type="button" x-show="educationEntries.length > 1" @click="removeEducation(index)"
                            class="text-sm font-medium text-red-600 hover:text-red-800">
                        {{ __('Remove') }}
                    </button>
                </div>

                <input type="hidden" :name="'education[' + index + '][id]'" x-model="entry.record_id">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label :value="__('Education level')" />
                        <select :name="'education[' + index + '][level]'" data-step="{{ $step }}"
                                x-model="entry.level"
                                @change="if (entry.level === '{{ $wrrbLevel }}') { entry.institution = entry.institution || 'WRRB'; entry.program = entry.program || 'others'; entry.program_other = entry.program_other || 'WRRB Certificate'; }"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]">
                            <option value="">{{ __('Select level') }}</option>
                            @foreach($levelOptions as $value => $label)
                                <option value="{{ $value }}">{{ __($label) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label :value="__('Institution')" />
                        <input type="text" :name="'education[' + index + '][institution]'" data-step="{{ $step }}"
                               x-model="entry.institution"
                               required
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]">
                    </div>
                </div>

                <div>
                    <x-input-label :value="__('Program')" />
                    <select :name="'education[' + index + '][program]'" data-step="{{ $step }}"
                            x-model="entry.program"
                            required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]">
                        <option value="">{{ __('Select program') }}</option>
                        @foreach($programOptions as $value => $label)
                            <option value="{{ $value }}">{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="entry.program === 'others'" x-cloak>
                    <x-input-label :value="__('Program specification')" />
                    <input type="text" :name="'education[' + index + '][program_other]'" data-step="{{ $step }}"
                           x-model="entry.program_other"
                           x-bind:required="entry.program === 'others'"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-[#0a71ab] focus:ring-[#0a71ab]">
                </div>

                <div>
                    <x-input-label>
                        @if($includeWrrb)
                            <span x-text="entry.level === '{{ $wrrbLevel }}' ? '{{ __('WRRB certificate') }}' : '{{ __('Education certificate (certified by advocate)') }}'"></span>
                        @else
                            {{ __('Education certificate (certified by advocate)') }}
                        @endif
                    </x-input-label>
                    <template x-if="entry.existing_certificate">
                        <p class="mt-1 text-xs text-gray-600">{{ __('Current certificate on file. Upload a new file only if you want to replace it.') }}</p>
                    </template>
                    <label class="mt-1.5 flex cursor-pointer items-center gap-3 rounded-lg border border-dashed border-gray-300 bg-white px-3 py-2 transition hover:border-[#0a71ab]/50 hover:bg-[#0a71ab]/5">
                        <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <div class="min-w-0 flex-1 text-left">
                            <span class="text-xs text-gray-600">{{ __('Click to upload') }} · {{ __('PDF or image, max 5MB') }}</span>
                            <span class="mt-0.5 block truncate text-xs font-medium text-[#0a71ab]" x-text="entry.filename || ''"></span>
                        </div>
                        <span class="shrink-0 rounded border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600">{{ __('Browse') }}</span>
                        <input type="file" accept=".pdf,.jpg,.jpeg,.png" data-step="{{ $step }}"
                               :name="'education[' + index + '][certificate]'"
                               x-bind:required="!entry.record_id"
                               class="sr-only"
                               @change="entry.filename = $event.target.files[0]?.name || (entry.existing_certificate ? '{{ __('Existing file kept') }}' : '')">
                    </label>
                </div>
            </div>
        </template>
    @endif
</div>
