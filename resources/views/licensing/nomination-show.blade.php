<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('License nomination') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-3xl space-y-4">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="p-4 rounded-md bg-red-50 text-red-800">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">{{ __('Nomination details') }}</h3>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    <p><span class="font-medium text-gray-500">{{ __('Organization') }}:</span> {{ $nomination->organization_name ?: '—' }}</p>
                    <p><span class="font-medium text-gray-500">{{ __('License application ID') }}:</span> <span class="font-mono">{{ $nomination->licensing_application_id }}</span></p>
                    <p><span class="font-medium text-gray-500">{{ __('Your registration number') }}:</span> <span class="font-mono">{{ $nomination->registration_number }}</span></p>
                    <p><span class="font-medium text-gray-500">{{ __('Nominated position') }}:</span> {{ $nomination->finalPositionLabel() }}</p>
                    <p>
                        <span class="font-medium text-gray-500">{{ __('Status') }}:</span>
                        <span class="capitalize font-medium">{{ str_replace('_', ' ', $nomination->status) }}</span>
                        @if($nomination->isReserved())
                            <span class="ml-1 text-amber-700">({{ __('reserved') }})</span>
                        @endif
                    </p>
                    @if($nomination->status === 'pending' && $nomination->expires_at)
                        <p><span class="font-medium text-gray-500">{{ __('Respond before') }}:</span> {{ $nomination->expires_at->format('Y-m-d H:i') }}</p>
                    @endif
                    @if($nomination->responded_at)
                        <p><span class="font-medium text-gray-500">{{ __('Responded at') }}:</span> {{ $nomination->responded_at->format('Y-m-d H:i') }}</p>
                    @endif
                    @if($nomination->license_number)
                        <p><span class="font-medium text-gray-500">{{ __('License number') }}:</span> <span class="font-mono">{{ $nomination->license_number }}</span></p>
                    @endif
                    @if($nomination->license_valid_from || $nomination->license_valid_until)
                        <p>
                            <span class="font-medium text-gray-500">{{ __('License validity') }}:</span>
                            {{ $nomination->license_valid_from?->format('Y-m-d') ?? '—' }}
                            →
                            {{ $nomination->license_valid_until?->format('Y-m-d') ?? '—' }}
                        </p>
                    @endif
                </div>

                @if($nomination->isPending())
                    <div class="px-6 py-4 bg-gray-50 border-t space-y-3">
                        <p class="text-sm text-gray-600">
                            {{ __('Accepting reserves you for this company until the license is issued, rejected, or cancelled. You cannot accept another nomination while reserved.') }}
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <form method="POST" action="{{ route('licensing.nominations.accept', $nomination) }}">
                                @csrf
                                <x-primary-button type="submit">{{ __('Accept') }}</x-primary-button>
                            </form>
                            <form method="POST" action="{{ route('licensing.nominations.reject', $nomination) }}"
                                  onsubmit="return confirm('{{ __('Reject this license nomination?') }}');">
                                @csrf
                                <x-danger-button type="submit">{{ __('Reject') }}</x-danger-button>
                            </form>
                        </div>
                    </div>
                @elseif($nomination->status === 'accepted')
                    <div class="px-6 py-4 bg-blue-50 border-t text-sm text-blue-900">
                        {{ __('You accepted this nomination. You are reserved for this company until the licensing system issues, rejects, or cancels the license.') }}
                    </div>
                @elseif($nomination->status === 'licensed' && $nomination->isLicenseActive())
                    <div class="px-6 py-4 bg-green-50 border-t text-sm text-green-900">
                        {{ __('Your license is active. You remain assigned to this company until the validity period ends or the license is revoked.') }}
                    </div>
                @elseif($nomination->status === 'licensed')
                    <div class="px-6 py-4 bg-gray-50 border-t text-sm text-gray-700">
                        {{ __('This license validity period has ended. You may be nominated by companies again.') }}
                    </div>
                @elseif($nomination->isExpired() || $nomination->status === 'expired')
                    <div class="px-6 py-4 bg-amber-50 border-t text-sm text-amber-900">
                        {{ __('This nomination has expired (7-day response window). The licensing office must nominate you again if still required.') }}
                    </div>
                @elseif(in_array($nomination->status, ['cancelled', 'revoked'], true))
                    <div class="px-6 py-4 bg-gray-50 border-t text-sm text-gray-700">
                        {{ __('This engagement was cancelled or revoked. You may accept other nominations again.') }}
                    </div>
                @endif
            </div>

            @if($nomination->allowsChangeRequests())
                @php
                    $pendingChange = $nomination->changeRequests->firstWhere('status', 'pending');
                @endphp

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">{{ __('Request a change') }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ __('Position or organization changes, or a release request, need WRRB staff approval.') }}</p>
                    </div>

                    @if($pendingChange)
                        <div class="p-6 space-y-3 text-sm">
                            <p class="text-amber-800">{{ __('You already have a pending change request awaiting WRRB review.') }}</p>
                            <p><span class="font-medium text-gray-500">{{ __('Summary') }}:</span> {{ $pendingChange->summaryLabel() }}</p>
                            <form method="POST" action="{{ route('licensing.change-requests.cancel', $pendingChange) }}"
                                  onsubmit="return confirm('{{ __('Cancel this pending change request?') }}');">
                                @csrf
                                <x-secondary-button type="submit">{{ __('Cancel request') }}</x-secondary-button>
                            </form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('licensing.change-requests.store', $nomination) }}" class="p-6 space-y-4" x-data="{ type: 'update' }">
                            @csrf
                            <div>
                                <x-input-label for="change_type" :value="__('Request type')" />
                                <select id="change_type" name="change_type" x-model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="update">{{ __('Change position and/or organization') }}</option>
                                    <option value="release">{{ __('Request release from this company') }}</option>
                                </select>
                            </div>

                            <div x-show="type === 'update'" class="space-y-4">
                                <div>
                                    <x-input-label for="proposed_final_position" :value="__('New position (optional)')" />
                                    <select id="proposed_final_position" name="proposed_final_position" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">{{ __('Keep current (:current)', ['current' => $nomination->finalPositionLabel()]) }}</option>
                                        @foreach($eligiblePositions as $key => $label)
                                            <option value="{{ $key }}" @selected(old('proposed_final_position') === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="proposed_organization_name" :value="__('New organization name (optional)')" />
                                    <x-text-input id="proposed_organization_name" name="proposed_organization_name" class="mt-1 block w-full" :value="old('proposed_organization_name', $nomination->organization_name)" />
                                </div>
                            </div>

                            <div>
                                <x-input-label for="reason" :value="__('Reason')" />
                                <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('reason') }}</textarea>
                            </div>

                            <x-primary-button type="submit">{{ __('Submit for WRRB approval') }}</x-primary-button>
                        </form>
                    @endif
                </div>
            @endif

            @if($nomination->changeRequests->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100">
                        <h3 class="font-medium text-gray-900">{{ __('Change request history') }}</h3>
                    </div>
                    <ul class="divide-y divide-gray-100">
                        @foreach($nomination->changeRequests as $item)
                            <li class="px-6 py-3 text-sm">
                                <div class="flex flex-wrap justify-between gap-2">
                                    <span>{{ $item->summaryLabel() }}</span>
                                    <span class="capitalize text-gray-500">{{ $item->status }} · {{ $item->created_at?->format('Y-m-d') }}</span>
                                </div>
                                @if($item->review_notes)
                                    <p class="mt-1 text-gray-600">{{ $item->review_notes }}</p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p>
                <x-back-link :href="route('notifications.index')" class="text-sm text-[#0a71ab] hover:text-[#085a89] hover:underline">{{ __('Back to notifications') }}</x-back-link>
            </p>
        </div>
    </div>
</x-app-layout>
