<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Registration review') }} — {{ $user->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-4">
                <a href="{{ route('app-management.registrations.index') }}" class="text-indigo-600 hover:text-indigo-800">{{ __('&larr; Back to registrations') }}</a>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 bg-gray-50 border-b">
                        <h3 class="font-medium text-gray-900">{{ __('Applicant information') }}</h3>
                    </div>
                    <div class="p-6">
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2 text-sm">
                            <div><dt class="font-medium text-gray-500">{{ __('Category') }}</dt><dd class="mt-0.5">{{ \App\Models\User::registrationCategoryOptions()[$user->registration_category] ?? '—' }}</dd></div>
                            <div><dt class="font-medium text-gray-500">{{ __('Status') }}</dt><dd class="mt-0.5 capitalize">{{ $user->registration_status }}</dd></div>
                            <div><dt class="font-medium text-gray-500">{{ __('Name') }}</dt><dd class="mt-0.5">{{ $user->first_name }} {{ $user->middle_name }} {{ $user->last_name }}</dd></div>
                            <div><dt class="font-medium text-gray-500">{{ __('Email') }}</dt><dd class="mt-0.5">{{ $user->email }}</dd></div>
                            <div><dt class="font-medium text-gray-500">{{ __('Phone') }}</dt><dd class="mt-0.5">{{ $user->phone ?? '—' }}</dd></div>
                            <div><dt class="font-medium text-gray-500">{{ __('Region') }}</dt><dd class="mt-0.5">{{ $user->region ?? '—' }}</dd></div>
                            <div><dt class="font-medium text-gray-500">{{ __('District') }}</dt><dd class="mt-0.5">{{ $user->district ?? '—' }}</dd></div>
                            <div><dt class="font-medium text-gray-500">{{ __('Gender') }}</dt><dd class="mt-0.5">{{ $user->gender ? __(ucfirst($user->gender)) : '—' }}</dd></div>
                            <div><dt class="font-medium text-gray-500">{{ __('Date of birth') }}</dt><dd class="mt-0.5">{{ $user->date_of_birth?->format('Y-m-d') ?? '—' }}</dd></div>
                            <div><dt class="font-medium text-gray-500">{{ __('Position') }}</dt><dd class="mt-0.5">{{ $user->position ? __(str_replace('_', ' ', ucfirst($user->position))) : '—' }}</dd></div>
                            <div><dt class="font-medium text-gray-500">{{ __('Company / Private') }}</dt><dd class="mt-0.5">{{ $user->company_or_private ? __(ucfirst($user->company_or_private)) : '—' }}</dd></div>
                            @if($user->company_name)
                                <div class="sm:col-span-2"><dt class="font-medium text-gray-500">{{ __('Company name') }}</dt><dd class="mt-0.5">{{ $user->company_name }}</dd></div>
                                <div class="sm:col-span-2"><dt class="font-medium text-gray-500">{{ __('Company address') }}</dt><dd class="mt-0.5">{{ $user->company_address ?? '—' }}</dd></div>
                            @endif
                            <div><dt class="font-medium text-gray-500">{{ __('Registered') }}</dt><dd class="mt-0.5">{{ $user->created_at->format('Y-m-d H:i') }}</dd></div>
                        </dl>
                    </div>
                </div>

                <div class="space-y-6">
                    @if($user->isNewApplicant() && $user->educationBackgrounds->isNotEmpty())
                        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                            <h3 class="px-6 py-3 bg-gray-50 border-b font-medium text-gray-900">{{ __('Education background') }}</h3>
                            <div class="p-6 space-y-4">
                                @foreach($user->educationBackgrounds as $eb)
                                    <div class="border border-gray-200 rounded-lg p-4 flex flex-wrap items-start justify-between gap-3">
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
                                                <button type="button" onclick="document.getElementById('preview-edu-{{ $eb->id }}').classList.remove('hidden'); document.body.classList.add('overflow-hidden');"
                                                        class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                                    {{ __('Preview') }}
                                                </button>
                                                <a href="{{ $certUrl }}" target="_blank" rel="noopener noreferrer"
                                                   class="inline-flex items-center px-3 py-1.5 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                                                    {{ __('Open') }}
                                                </a>
                                            </div>
                                            <div id="preview-edu-{{ $eb->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                                <div class="flex min-h-full items-center justify-center p-4">
                                                    <div class="fixed inset-0 bg-black/60" onclick="document.getElementById('preview-edu-{{ $eb->id }}').classList.add('hidden'); document.body.classList.remove('overflow-hidden');"></div>
                                                    <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col">
                                                        <div class="flex items-center justify-between p-3 border-b">
                                                            <span class="font-medium text-gray-900">{{ __('Education certificate') }}</span>
                                                            <button type="button" onclick="document.getElementById('preview-edu-{{ $eb->id }}').classList.add('hidden'); document.body.classList.remove('overflow-hidden');"
                                                                    class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                                                        </div>
                                                        <div class="flex-1 min-h-0 p-2">
                                                            @if($isPdf)
                                                                <iframe src="{{ $certUrl }}#toolbar=1" class="w-full h-[75vh] rounded border border-gray-200"></iframe>
                                                            @else
                                                                <img src="{{ $certUrl }}" alt="{{ __('Certificate') }}" class="max-w-full max-h-[75vh] mx-auto block rounded border border-gray-200" />
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if($user->isTrainedPerson() && $legacyApplication)
                        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                            <h3 class="px-6 py-3 bg-gray-50 border-b font-medium text-gray-900">{{ __('Previous training') }}</h3>
                            <div class="p-6 space-y-3 text-sm">
                                <p><span class="font-medium text-gray-500">{{ __('Course') }}:</span> {{ $legacyApplication->course->name ?? '—' }}</p>
                                <p><span class="font-medium text-gray-500">{{ __('Year trained') }}:</span> {{ $legacyApplication->trained_year ?? '—' }}</p>
                                <p><span class="font-medium text-gray-500">{{ __('Previous registration number') }}:</span> {{ $legacyApplication->legacy_registration_number ?? '—' }}</p>
                                @if($legacyApplication->certificate_path)
                                    @php
                                        $certUrl = route('app-management.registrations.training-certificate', $legacyApplication);
                                        $isPdf = in_array(strtolower(pathinfo($legacyApplication->certificate_path, PATHINFO_EXTENSION)), ['pdf']);
                                    @endphp
                                    <div class="pt-2 flex items-center gap-2">
                                        <button type="button" onclick="document.getElementById('preview-training-cert').classList.remove('hidden'); document.body.classList.add('overflow-hidden');"
                                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                            {{ __('Preview training certificate') }}
                                        </button>
                                        <a href="{{ $certUrl }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center px-3 py-1.5 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                                            {{ __('Open') }}
                                        </a>
                                    </div>
                                    <div id="preview-training-cert" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                        <div class="flex min-h-full items-center justify-center p-4">
                                            <div class="fixed inset-0 bg-black/60" onclick="document.getElementById('preview-training-cert').classList.add('hidden'); document.body.classList.remove('overflow-hidden');"></div>
                                            <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col">
                                                <div class="flex items-center justify-between p-3 border-b">
                                                    <span class="font-medium text-gray-900">{{ __('Training certificate') }}</span>
                                                    <button type="button" onclick="document.getElementById('preview-training-cert').classList.add('hidden'); document.body.classList.remove('overflow-hidden');"
                                                            class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                                                </div>
                                                <div class="flex-1 min-h-0 p-2">
                                                    @if($isPdf)
                                                        <iframe src="{{ $certUrl }}#toolbar=1" class="w-full h-[75vh] rounded border border-gray-200"></iframe>
                                                    @else
                                                        <img src="{{ $certUrl }}" alt="{{ __('Certificate') }}" class="max-w-full max-h-[75vh] mx-auto block rounded border border-gray-200" />
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if($user->registration_status === 'pending')
                        <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
                            <h3 class="font-medium text-gray-900">{{ __('Actions') }}</h3>
                            <form method="POST" action="{{ route('app-management.registrations.approve', $user) }}">
                                @csrf
                                <x-primary-button type="submit" class="bg-green-600 hover:bg-green-700">{{ __('Approve registration') }}</x-primary-button>
                            </form>
                            <form method="POST" action="{{ route('app-management.registrations.reject', $user) }}" class="space-y-3 pt-4 border-t">
                                @csrf
                                <div>
                                    <x-input-label for="registration_rejection_reason" :value="__('Rejection reason')" />
                                    <textarea id="registration_rejection_reason" name="registration_rejection_reason" rows="3" required
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('registration_rejection_reason') }}</textarea>
                                    <x-input-error :messages="$errors->get('registration_rejection_reason')" class="mt-2" />
                                </div>
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
                                    {{ __('Reject registration') }}
                                </button>
                            </form>
                        </div>
                    @elseif($user->registration_rejection_reason)
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm">
                            <p class="font-medium text-red-800">{{ __('Rejection reason') }}</p>
                            <p class="mt-1 text-red-900">{{ $user->registration_rejection_reason }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
