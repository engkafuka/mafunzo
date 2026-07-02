<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Registration verification') }}
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
                <a href="{{ route('app-management.index') }}" class="text-indigo-600 hover:text-indigo-800">{{ __('&larr; Back to Application Management') }}</a>
            </div>

            <form method="GET" class="mb-6 flex flex-wrap gap-2 items-center">
                <select name="status" class="rounded-md border-gray-300 text-sm">
                    <option value="">{{ __('All statuses') }}</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('Pending') }}</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                </select>
                <select name="category" class="rounded-md border-gray-300 text-sm">
                    <option value="">{{ __('All categories') }}</option>
                    @foreach(\App\Models\User::registrationCategoryOptions() as $value => $label)
                        <option value="{{ $value }}" {{ request('category') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-1.5 bg-gray-200 rounded-md text-sm hover:bg-gray-300">{{ __('Filter') }}</button>
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Email') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Category') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Submitted') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($registrations as $registration)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $registration->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $registration->email }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ \App\Models\User::registrationCategoryOptions()[$registration->registration_category] ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        <span @class([
                                            'inline-flex px-2 py-0.5 rounded text-xs font-medium capitalize',
                                            'bg-amber-100 text-amber-800' => $registration->registration_status === 'pending',
                                            'bg-red-100 text-red-800' => $registration->registration_status === 'rejected',
                                        ])>{{ $registration->registration_status }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $registration->created_at->format('Y-m-d') }}</td>
                                    <td class="px-4 py-3 text-sm text-right">
                                        <a href="{{ route('app-management.registrations.show', $registration) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ __('Review') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('No registrations found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($registrations->hasPages())
                    <div class="px-4 py-3 border-t">{{ $registrations->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
