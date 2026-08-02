<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('License change requests') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="mb-4">
                <x-back-link :href="route('app-management.index')">{{ __('Back to Application Management') }}</x-back-link>
            </div>

            <form method="GET" class="mb-6 flex flex-wrap gap-2 items-center">
                <select name="status" class="rounded-md border-gray-300 text-sm">
                    <option value="pending" {{ ($statusFilter ?? 'pending') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                    <option value="approved" {{ ($statusFilter ?? '') === 'approved' ? 'selected' : '' }}>{{ __('Approved') }}</option>
                    <option value="rejected" {{ ($statusFilter ?? '') === 'rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                    <option value="cancelled" {{ ($statusFilter ?? '') === 'cancelled' ? 'selected' : '' }}>{{ __('Cancelled') }}</option>
                    <option value="all" {{ ($statusFilter ?? '') === 'all' ? 'selected' : '' }}>{{ __('All') }}</option>
                </select>
                <button type="submit" class="px-3 py-1.5 bg-gray-200 rounded-md text-sm hover:bg-gray-300">{{ __('Filter') }}</button>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Staff') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Organization') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Change') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Requested by') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Submitted') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($requests as $item)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $item->nomination?->user?->name ?? '—' }}
                                        <div class="text-xs text-gray-500 font-mono">{{ $item->nomination?->registration_number }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $item->nomination?->organization_name ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $item->summaryLabel() }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 capitalize">{{ $item->requested_by }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span @class([
                                            'inline-flex px-2 py-0.5 rounded text-xs font-medium capitalize',
                                            'bg-amber-100 text-amber-800' => $item->status === 'pending',
                                            'bg-green-100 text-green-800' => $item->status === 'approved',
                                            'bg-red-100 text-red-800' => $item->status === 'rejected',
                                            'bg-gray-100 text-gray-700' => $item->status === 'cancelled',
                                        ])>{{ $item->status }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $item->created_at?->format('Y-m-d H:i') }}</td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <a href="{{ route('app-management.licensing-change-requests.show', $item) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ __('Review') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">{{ __('No change requests found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($requests->hasPages())
                    <x-table-pagination :paginator="$requests" />
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
