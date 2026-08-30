<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Warehouse Operator Interview') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            @include('interview._alerts')
            @include('interview._nav')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">{{ __('My pending scoring') }}</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $pendingScoring }}</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">{{ __('Under chair review') }}</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $underReview }}</div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <div class="text-sm text-gray-500">{{ __('Recent sessions') }}</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $sessions->count() }}</div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-4 border-b border-gray-200 font-medium">{{ __('Recent interview sessions') }}</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Code') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Company') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Interviewee') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($sessions as $session)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-medium text-indigo-700">{{ $session->session_code }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $session->company->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $session->interviewee_name }}</td>
                                    <td class="px-4 py-3 text-sm">{{ $session->statusLabel() }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <a href="{{ route('interview.sessions.show', $session) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('View') }}</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No interview sessions yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if(Auth::user()->hasInterviewRole('admin', 'viewer'))
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg" x-data="{ open: false }">
                    <button type="button"
                            @click="open = !open"
                            class="w-full p-4 flex items-center justify-between gap-3 text-left hover:bg-gray-50 border-b border-transparent"
                            :class="open && 'border-gray-200'">
                        <div>
                            <div class="font-medium text-gray-900">{{ __('Recent audit activity') }}</div>
                            <p class="text-xs text-gray-500 mt-0.5" x-show="!open">{{ __('Click to expand') }}</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition-transform shrink-0" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <ul class="divide-y divide-gray-100" x-show="open" x-cloak>
                        @forelse($recentAudit as $log)
                            <li class="px-4 py-3 text-sm">
                                <div class="font-medium text-gray-900">{{ $log->description }}</div>
                                <div class="text-gray-500">{{ $log->created_at?->format('Y-m-d H:i') }} · {{ $log->user?->name ?? __('System') }}</div>
                            </li>
                        @empty
                            <li class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No audit entries yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
