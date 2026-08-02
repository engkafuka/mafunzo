<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Review license change request') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-3xl space-y-4">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="p-4 rounded-md bg-red-50 text-red-800">{{ $errors->first() }}</div>
            @endif

            <div>
                <x-back-link :href="route('app-management.licensing-change-requests.index')" class="text-sm">{{ __('Back to list') }}</x-back-link>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">{{ __('Request details') }}</h3>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    <p><span class="font-medium text-gray-500">{{ __('Staff') }}:</span> {{ $changeRequest->nomination?->user?->name ?? '—' }}</p>
                    <p><span class="font-medium text-gray-500">{{ __('Registration number') }}:</span> <span class="font-mono">{{ $changeRequest->nomination?->registration_number }}</span></p>
                    <p><span class="font-medium text-gray-500">{{ __('License application ID') }}:</span> <span class="font-mono">{{ $changeRequest->nomination?->licensing_application_id }}</span></p>
                    <p><span class="font-medium text-gray-500">{{ __('Nomination status') }}:</span> <span class="capitalize">{{ str_replace('_', ' ', $changeRequest->nomination?->status ?? '') }}</span></p>
                    <p><span class="font-medium text-gray-500">{{ __('Requested by') }}:</span> <span class="capitalize">{{ $changeRequest->requested_by }}</span></p>
                    <p><span class="font-medium text-gray-500">{{ __('Change type') }}:</span> <span class="capitalize">{{ $changeRequest->change_type }}</span></p>
                    <p><span class="font-medium text-gray-500">{{ __('Summary') }}:</span> {{ $changeRequest->summaryLabel() }}</p>
                    @if($changeRequest->change_type === 'update')
                        <p><span class="font-medium text-gray-500">{{ __('Position') }}:</span> {{ $changeRequest->currentPositionLabel() ?? '—' }} → {{ $changeRequest->proposedPositionLabel() ?? '—' }}</p>
                        <p><span class="font-medium text-gray-500">{{ __('Organization') }}:</span> {{ $changeRequest->current_organization_name ?: '—' }} → {{ $changeRequest->proposed_organization_name ?: '—' }}</p>
                    @endif
                    <p><span class="font-medium text-gray-500">{{ __('Reason') }}:</span> {{ $changeRequest->reason ?: '—' }}</p>
                    <p>
                        <span class="font-medium text-gray-500">{{ __('Status') }}:</span>
                        <span class="capitalize font-medium">{{ $changeRequest->status }}</span>
                    </p>
                    @if($changeRequest->reviewed_at)
                        <p><span class="font-medium text-gray-500">{{ __('Reviewed at') }}:</span> {{ $changeRequest->reviewed_at->format('Y-m-d H:i') }}</p>
                        <p><span class="font-medium text-gray-500">{{ __('Review notes') }}:</span> {{ $changeRequest->review_notes ?: '—' }}</p>
                    @endif
                </div>

                @if($changeRequest->isPending())
                    <div class="px-6 py-4 bg-gray-50 border-t space-y-4">
                        <form method="POST" action="{{ route('app-management.licensing-change-requests.approve', $changeRequest) }}" class="space-y-3">
                            @csrf
                            <div>
                                <x-input-label for="approve_notes" :value="__('Notes (optional)')" />
                                <textarea id="approve_notes" name="review_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('review_notes') }}</textarea>
                            </div>
                            <x-primary-button type="submit">{{ __('Approve & apply') }}</x-primary-button>
                        </form>

                        <form method="POST" action="{{ route('app-management.licensing-change-requests.reject', $changeRequest) }}" class="space-y-3" onsubmit="return confirm('{{ __('Reject this change request?') }}');">
                            @csrf
                            <div>
                                <x-input-label for="reject_notes" :value="__('Rejection reason')" />
                                <textarea id="reject_notes" name="review_notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>{{ old('review_notes') }}</textarea>
                            </div>
                            <x-danger-button type="submit">{{ __('Reject') }}</x-danger-button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
