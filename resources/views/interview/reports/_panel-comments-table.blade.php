<div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto border border-gray-200">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Date') }}</th>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Session') }}</th>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Company') }}</th>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Panelist') }}</th>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Q#') }}</th>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Category') }}</th>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500">{{ __('Score') }}</th>
                <th class="px-4 py-2 text-left text-xs uppercase text-gray-500 min-w-[280px]">{{ __('Comment') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($rows as $row)
                <tr>
                    <td class="px-4 py-3 text-sm">{{ $row->session?->interview_date?->format('Y-m-d') ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm font-medium text-indigo-700">{{ $row->session?->session_code ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm">{{ $row->session?->company?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm">
                        {{ $row->panelist?->name ?? '—' }}
                        <span class="block text-xs text-gray-500">
                            {{ $row->session ? \App\Support\Interview\InterviewPanelCommentsReport::panelistRole($row->session, (int) $row->panelist_user_id) : '' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm">{{ $row->question?->sort_order ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm">{{ $row->question?->categoryLabel() ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm">{{ $row->score ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-800 whitespace-pre-wrap">{{ $row->comment ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No panel comments match the selected filters.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <div class="p-4">{{ $rows->links() }}</div>
</div>
