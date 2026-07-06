<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Applications') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            <div class="mb-4">
                <a href="{{ route('app-management.index') }}" class="text-indigo-600 hover:text-indigo-800">{{ __('&larr; Back to Application Management') }}</a>
            </div>

            <form method="GET" class="filter-bar mb-6">
                <select name="course_id" class="rounded-md border-gray-300 text-sm">
                    <option value="">{{ __('All courses') }}</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
                <select name="status_filter" class="rounded-md border-gray-300 text-sm">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="pending_review" {{ request('status_filter') === 'pending_review' ? 'selected' : '' }}>{{ __('Pending review') }}</option>
                    <option value="pending_account" {{ request('status_filter') === 'pending_account' ? 'selected' : '' }}>{{ __('Pending account verify') }}</option>
                    <option value="pending_payment" {{ request('status_filter') === 'pending_payment' ? 'selected' : '' }}>{{ __('Pending payment verify') }}</option>
                </select>
                <button type="submit" class="px-3 py-1.5 bg-gray-200 rounded-md text-sm hover:bg-gray-300">{{ __('Filter') }}</button>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Registration') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Course') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status / Review') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($applications as $app)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-mono">{{ $app->registration_number ?? $app->control_number ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $app->first_name }} {{ $app->last_name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $app->course->name }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span class="capitalize">{{ $app->status }}</span>
                                        @if($app->application_review_status !== 'pending')
                                            <span class="text-gray-500">/ {{ $app->application_review_status }}</span>
                                        @endif
                                        @if(!$app->account_verified_at && $app->status !== 'pending_registration')<span class="text-amber-600">· {{ __('No account verify') }}</span>@endif
                                        @if(!$app->payment_verified_at && in_array($app->status, ['pending_payment', 'payment_completed'], true))<span class="text-amber-600">· {{ __('No payment verify') }}</span>@endif
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('app-management.applications.show', $app) }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No applications found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($applications->hasPages())
                    <x-table-pagination :paginator="$applications" />
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
