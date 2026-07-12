<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Identity card') }} — {{ $application->registration_number }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-5xl space-y-6">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div>
                <a href="{{ route('app-management.identity-cards.index') }}" class="text-indigo-600 hover:text-indigo-800">{{ __('&larr; Back to ID cards') }}</a>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-900 mb-4">{{ __('Applicant') }}</h3>
                    <div class="flex gap-4">
                        @if($application->user?->hasProfilePhoto())
                            <img src="{{ route('profile-photos.show', $application->user) }}" alt="{{ __('Profile photo') }}" class="h-28 w-24 rounded-lg object-cover border border-gray-200">
                        @else
                            <div class="h-28 w-24 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-xs text-gray-500 text-center p-2">{{ __('No photo') }}</div>
                        @endif
                        <dl class="text-sm space-y-2">
                            <div><dt class="text-gray-500">{{ __('Name') }}</dt><dd class="font-medium">{{ $application->first_name }} {{ $application->last_name }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('Registration number') }}</dt><dd class="font-mono">{{ $application->registration_number }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('Course') }}</dt><dd>{{ $application->course?->name }}</dd></div>
                            @if($application->trained_year)
                                <div><dt class="text-gray-500">{{ __('Year trained') }}</dt><dd>{{ $application->trained_year }}</dd></div>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-900 mb-4">{{ __('Eligibility checklist') }}</h3>
                    <x-identity-card-checklist :checklist="$application->identityCardEligibilityChecklist()" />

                    @if($application->isEligibleForIdentityCard())
                        <p class="mt-4 text-sm text-green-700">{{ __('This applicant meets all requirements for an identity card.') }}</p>
                    @endif

                    @php $card = $application->warehouseIdentityCard; @endphp

                    @if($card)
                        <dl class="mt-4 text-sm space-y-2 border-t border-gray-100 pt-4">
                            <div><dt class="text-gray-500">{{ __('Card status') }}</dt><dd class="font-medium">{{ $card->statusLabel() }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('Issued') }}</dt><dd>{{ $card->issued_at->format('Y-m-d') }}</dd></div>
                            <div><dt class="text-gray-500">{{ __('Valid until') }}</dt><dd>{{ $card->expires_at->format('Y-m-d') }}</dd></div>
                            @if($card->published_at)
                                <div><dt class="text-gray-500">{{ __('Published') }}</dt><dd>{{ $card->published_at->format('Y-m-d H:i') }}</dd></div>
                            @endif
                        </dl>
                    @endif

                    <div class="mt-6 flex flex-wrap gap-3">
                        @if($application->isEligibleForIdentityCard() && (! $card || $card->isDraft()))
                            <form method="POST" action="{{ route('app-management.identity-cards.generate', $application) }}">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                    {{ $card ? __('Regenerate draft') : __('Generate draft') }}
                                </button>
                            </form>
                        @endif

                        @if($card?->isDraft())
                            <form method="POST" action="{{ route('app-management.identity-cards.publish', $card) }}" onsubmit="return confirm('{{ __('Publish this identity card to the trainee account?') }}');">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-green-600 text-white text-sm rounded-md hover:bg-green-700">{{ __('Publish to trainee') }}</button>
                            </form>
                        @endif

                        @if($card)
                            <a href="{{ route('app-management.identity-cards.view', $card) }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                {{ __('View ID card') }}
                            </a>
                        @endif

                        @if($card?->isPublished())
                            <a href="{{ route('app-management.identity-cards.download', $card) }}" target="_blank" class="px-4 py-2 bg-gray-700 text-white text-sm rounded-md hover:bg-gray-800">{{ __('Download PDF') }}</a>
                            <a href="{{ $card->verificationUrl() }}" target="_blank" class="px-4 py-2 bg-slate-600 text-white text-sm rounded-md hover:bg-slate-700">{{ __('Verification page') }}</a>
                        @endif

                        @if($card?->isPublished() && auth()->user()->isAdminOrSuperAdmin())
                            <form method="POST" action="{{ route('app-management.identity-cards.revoke', $card) }}" onsubmit="return confirm('{{ __('Revoke this identity card?') }}');">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-red-600 text-white text-sm rounded-md hover:bg-red-700">{{ __('Revoke') }}</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-900 mb-4">{{ __('Activity timeline') }}</h3>
                @forelse(($timeline ?? []) as $entry)
                    <div class="border-l-2 border-[#0a71ab]/30 pl-4 pb-4 last:pb-0">
                        <p class="text-sm text-gray-900">{{ $entry->description }}</p>
                        <p class="mt-1 text-xs text-gray-500">
                            {{ $entry->user_name ?? __('System') }}
                            @if($entry->user_role) ({{ $entry->user_role }}) @endif
                            · {{ $entry->created_at?->format('Y-m-d H:i') }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('No activity recorded yet for this application or identity card.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
