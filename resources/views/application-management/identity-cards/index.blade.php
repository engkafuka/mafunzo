<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Warehouse ID cards') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-4">
                <a href="{{ route('app-management.index') }}" class="text-indigo-600 hover:text-indigo-800">{{ __('&larr; Back to Application Management') }}</a>
            </div>

            <p class="mb-4 text-sm text-gray-600">
                {{ __('Generate and publish warehouse worker identity cards for trainees with a registration number who completed all approvals, passed the exam, and have a profile photo.') }}
            </p>

            <form method="GET" class="mb-6 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Course') }}</label>
                    <select name="course_id" class="rounded-md border-gray-300 text-sm">
                        <option value="">{{ __('All courses') }}</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" @selected(request('course_id') == $course->id)>{{ $course->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Application status') }}</label>
                    <select name="status_filter" class="rounded-md border-gray-300 text-sm">
                        <option value="all" @selected($statusFilter === 'all')>{{ __('All with registration number') }}</option>
                        <option value="eligible" @selected($statusFilter === 'eligible')>{{ __('Ready to generate') }}</option>
                        <option value="draft" @selected($statusFilter === 'draft')>{{ __('Draft') }}</option>
                        <option value="published" @selected($statusFilter === 'published')>{{ __('Published') }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">{{ __('Generated card status') }}</label>
                    <select name="card_status" class="rounded-md border-gray-300 text-sm">
                        <option value="">{{ __('All generated') }}</option>
                        <option value="draft" @selected(request('card_status') === 'draft')>{{ __('Draft') }}</option>
                        <option value="published" @selected(request('card_status') === 'published')>{{ __('Published') }}</option>
                        <option value="revoked" @selected(request('card_status') === 'revoked')>{{ __('Revoked') }}</option>
                    </select>
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">{{ __('Filter') }}</button>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-8">
                <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900">{{ __('Generated identity cards') }}</h3>
                    <p class="mt-1 text-xs text-gray-500">{{ __('All ID cards that have been generated. Use the view button to preview the PDF.') }}</p>
                </div>
                <x-responsive-table>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Registration') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Course') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Generated') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Valid until') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($generatedCards as $generatedCard)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-mono">{{ $generatedCard->registration_number }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $generatedCard->full_name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $generatedCard->course_name ?? $generatedCard->trainingApplication?->course?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $generatedCard->statusLabel() }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $generatedCard->generated_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $generatedCard->expires_at?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <div class="inline-flex items-center gap-3">
                                            <a href="{{ route('app-management.identity-cards.view', $generatedCard) }}" target="_blank" rel="noopener noreferrer"
                                               class="inline-flex items-center justify-center text-indigo-600 hover:text-indigo-800"
                                               title="{{ __('View ID card') }}">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                <span class="sr-only">{{ __('View') }}</span>
                                            </a>
                                            <a href="{{ route('app-management.identity-cards.show', $generatedCard->training_application_id) }}" class="text-gray-600 hover:text-gray-800 font-medium">{{ __('Manage') }}</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">{{ __('No identity cards have been generated yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-responsive-table>
                @if($generatedCards->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200">
                        {{ $generatedCards->links() }}
                    </div>
                @endif
            </div>

            <div class="mb-4">
                <h3 class="text-sm font-semibold text-gray-900">{{ __('Applications') }}</h3>
                <p class="mt-1 text-xs text-gray-500">{{ __('Review eligibility and generate new identity cards.') }}</p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <x-responsive-table>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Registration') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Course') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('ID status') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($applications as $application)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-mono">{{ $application->registration_number }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $application->first_name }} {{ $application->last_name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $application->course?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($application->warehouseIdentityCard)
                                            <span class="text-gray-900">{{ $application->warehouseIdentityCard->statusLabel() }}</span>
                                        @elseif($application->isEligibleForIdentityCard())
                                            <span class="text-green-700">{{ __('Ready') }}</span>
                                        @else
                                            <span class="text-amber-700">{{ $application->identityCardIneligibilityReason() }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <a href="{{ route('app-management.identity-cards.show', $application) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ __('Manage') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No applications found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-responsive-table>
                <x-table-pagination :paginator="$applications" />
            </div>
        </div>
    </div>
</x-app-layout>
