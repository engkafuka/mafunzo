<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Interview sessions') }}</h2></x-slot>
    <div class="page-shell"><div class="page-inner-7xl">
        @include('interview._alerts') @include('interview._nav')
        @if(Auth::user()->hasInterviewRole('admin'))
            <div class="mb-4"><a href="{{ route('interview.sessions.create') }}" class="inline-flex px-4 py-2 bg-indigo-600 text-white rounded-md text-xs font-semibold uppercase">{{ __('Schedule session') }}</a></div>
        @endif
        <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50"><tr>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Code') }}</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Company') }}</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Interviewee') }}</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Date') }}</th>
                    <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Status') }}</th>
                    <th class="px-4 py-2 text-right text-xs uppercase text-gray-500">{{ __('Actions') }}</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-200">
                @forelse($sessions as $session)
                    <tr>
                        <td class="px-4 py-3 text-sm font-medium text-indigo-700">{{ $session->session_code }}</td>
                        <td class="px-4 py-3 text-sm">{{ $session->company->name }}</td>
                        <td class="px-4 py-3 text-sm">{{ $session->interviewee_name }}</td>
                        <td class="px-4 py-3 text-sm">{{ $session->interview_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm">{{ $session->statusLabel() }}</td>
                        <td class="px-4 py-3 text-right text-sm"><a href="{{ route('interview.sessions.show', $session) }}" class="text-indigo-600">{{ __('View') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No sessions yet.') }}</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="p-4">{{ $sessions->links() }}</div>
        </div>
    </div></div>
</x-app-layout>
