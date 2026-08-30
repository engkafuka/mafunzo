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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
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
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $company->registration_number ?: '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $company->sessions_count }}</td>
                                    <td class="px-4 py-3 text-sm capitalize">{{ $company->status }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <a href="{{ route('interview.companies.edit', $company) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No companies yet.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $companies->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
