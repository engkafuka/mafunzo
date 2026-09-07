<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Interview companies') }}</h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            @include('interview._alerts')
            @include('interview._nav')

            <div class="mb-4">
                <a href="{{ route('interview.companies.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md text-xs font-semibold text-white uppercase tracking-widest hover:bg-indigo-700">
                    {{ __('Add company') }}
                </a>
            </div>

            <form method="GET" action="{{ route('interview.companies.index') }}" class="mb-4 filter-bar items-stretch sm:items-center">
                <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ __('Inactive') }}</option>
                </select>

                <div class="relative flex-1 min-w-[200px] w-full sm:w-auto">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                           placeholder="{{ __('Search by name, registration, contact...') }}"
                           class="w-full pl-9 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <button type="submit" class="px-3 py-1.5 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700">{{ __('Filter') }}</button>
                @if(request()->hasAny(['q', 'status']))
                    <a href="{{ route('interview.companies.index') }}" class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 text-center">{{ __('Clear') }}</a>
                @endif
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <x-responsive-table>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Registration') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Sessions') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($companies as $company)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $company->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 font-mono">{{ $company->registration_number ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $company->sessions_count }}</td>
                                    <td class="px-4 py-3 text-sm">
                                        @if($company->status === 'active')
                                            <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">{{ __('Active') }}</span>
                                        @else
                                            <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                        <a href="{{ route('interview.companies.edit', $company) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>

                                        @if($company->canBeDeleted())
                                            <form method="POST"
                                                  action="{{ route('interview.companies.destroy', $company) }}"
                                                  class="inline ms-2"
                                                  onsubmit="return confirm(@js(__('Permanently delete this company? This cannot be undone.')))">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                            </form>
                                        @elseif($company->status === 'active')
                                            <form method="POST"
                                                  action="{{ route('interview.companies.deactivate', $company) }}"
                                                  class="inline ms-2"
                                                  onsubmit="return confirm(@js(__('This company has interview sessions. Deactivate it instead of deleting?')))">
                                                @csrf
                                                <button type="submit" class="text-amber-700 hover:text-amber-900">{{ __('Deactivate') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No companies match the selected filters.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-responsive-table>
                <div class="p-4 pagination-responsive">{{ $companies->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
