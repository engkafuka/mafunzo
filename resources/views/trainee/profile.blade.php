<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Left column: Personal info + Education list --}}
                <div class="space-y-6">
            {{-- Personal information --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-900 mb-4">{{ __('Personal information') }}</h3>
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('First name') }}</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $user->first_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Middle name') }}</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $user->middle_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Last name') }}</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $user->last_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Email') }}</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Phone') }}</dt>
                        <dd class="mt-0.5 text-gray-900">{{ $user->phone ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Education backgrounds --}}
            @if($educationBackgrounds->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-900 mb-4">{{ __('Education background') }}</h3>
                    <div class="space-y-4">
                        @foreach($educationBackgrounds as $eb)
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ __(\App\Models\EducationBackground::levelOptions()[$eb->level] ?? $eb->level) }}</p>
                                        <p class="text-sm text-gray-600">{{ $eb->program === 'others' ? ($eb->program_other ?? 'Others') : __(ucfirst($eb->program)) }} · {{ $eb->institution }}</p>
                                    </div>
                                    @if($eb->certificate_path)
                                        @php
                                            $certUrl = route('trainee.profile.certificate', $eb);
                                            $isPdf = in_array(strtolower(pathinfo($eb->certificate_path, PATHINFO_EXTENSION)), ['pdf']);
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            <button type="button" onclick="document.getElementById('preview-{{ $eb->id }}').classList.remove('hidden'); document.body.classList.add('overflow-hidden');"
                                                    class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                                {{ __('Preview') }} {{ __('document') }}
                                            </button>
                                            <a href="{{ $certUrl }}" target="_blank" rel="noopener noreferrer"
                                               class="inline-flex items-center px-3 py-1.5 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                                                {{ __('Open in new tab') }}
                                            </a>
                                        </div>
                                        {{-- Modal: PDF/document preview --}}
                                        <div id="preview-{{ $eb->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                            <div class="flex min-h-full items-center justify-center p-4">
                                                <div class="fixed inset-0 bg-black/60" onclick="document.getElementById('preview-{{ $eb->id }}').classList.add('hidden'); document.body.classList.remove('overflow-hidden');"></div>
                                                <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col">
                                                    <div class="flex items-center justify-between p-3 border-b">
                                                        <span class="font-medium text-gray-900">{{ __('Document preview') }} — {{ $eb->institution }}</span>
                                                        <button type="button" onclick="document.getElementById('preview-{{ $eb->id }}').classList.add('hidden'); document.body.classList.remove('overflow-hidden');"
                                                                class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                                                    </div>
                                                    <div class="flex-1 min-h-0 p-2">
                                                        @if($isPdf)
                                                            <iframe src="{{ $certUrl }}#toolbar=1" class="w-full h-[75vh] rounded border border-gray-200" title="{{ __('Certificate') }}"></iframe>
                                                        @else
                                                            <img src="{{ $certUrl }}" alt="{{ __('Certificate') }}" class="max-w-full max-h-[75vh] mx-auto block rounded border border-gray-200" />
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400">{{ __('No certificate') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <x-table-pagination :paginator="$educationBackgrounds" />
                </div>
            @endif

                </div>

                {{-- Right column: Add education form --}}
                <div class="space-y-6">
                    <p class="text-gray-600">{{ __('Add your education background. You must attach a certificate certified by an advocate for each entry.') }}</p>
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-900 mb-4">{{ __('Add education background') }}</h3>
                <form method="POST" action="{{ route('trainee.profile.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <x-input-label for="level" :value="__('Level')" />
                        <select id="level" name="level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">{{ __('Select level') }}</option>
                            @foreach(\App\Models\EducationBackground::levelOptions() as $value => $label)
                                <option value="{{ $value }}" {{ old('level') === $value ? 'selected' : '' }}>{{ __($label) }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('level')" class="mt-2" />
                    </div>

                    <div x-data="{ program: '{{ old('program', '') }}' }">
                        <div>
                            <x-input-label for="program" :value="__('Program')" />
                            <select id="program" name="program" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required x-model="program">
                                <option value="">{{ __('Select program') }}</option>
                                @foreach(\App\Models\EducationBackground::programOptions() as $value => $label)
                                    <option value="{{ $value }}" {{ old('program') === $value ? 'selected' : '' }}>{{ __($label) }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('program')" class="mt-2" />
                        </div>
                        <div x-show="program === 'others'" x-cloak class="mt-4">
                            <x-input-label for="program_other" :value="__('Specify (if Others)')" />
                            <x-text-input id="program_other" class="block mt-1 w-full" type="text" name="program_other" :value="old('program_other')" x-bind:required="program === 'others'" />
                            <x-input-error :messages="$errors->get('program_other')" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="institution" :value="__('Institution')" />
                        <x-text-input id="institution" class="block mt-1 w-full" type="text" name="institution" :value="old('institution')" required placeholder="{{ __('Institution name') }}" />
                        <x-input-error :messages="$errors->get('institution')" class="mt-2" />
                    </div>

                    <div>
                        <x-input-label for="certificate" :value="__('Certificate (certified by advocate)')" />
                        <input id="certificate" name="certificate" type="file" accept=".pdf,.jpg,.jpeg,.png" required
                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('Upload PDF or image (max 5MB). Certificate must be certified by an advocate.') }}</p>
                        <x-input-error :messages="$errors->get('certificate')" class="mt-2" />
                    </div>

                    <x-primary-button type="submit">{{ __('Add education background') }}</x-primary-button>
                </form>
            </div>

                    <p>
                        <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ __('Go to dashboard') }}</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
