<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Review') }} · {{ $session->session_code }}</h2></x-slot>
    <div class="page-shell"><div class="page-inner-7xl">
        @include('interview._alerts')
        @include('interview._nav')

        @if($session->result)
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mb-4">
                    <div><span class="text-gray-500">{{ __('Total') }}</span><div class="text-lg font-semibold">{{ $session->result->total_score }} / {{ $session->result->max_possible_score }}</div></div>
                    <div><span class="text-gray-500">{{ __('Percentage') }}</span><div class="text-lg font-semibold">{{ $session->result->percentage }}%</div></div>
                    <div><span class="text-gray-500">{{ __('Pass mark') }}</span><div class="text-lg font-semibold">{{ $session->pass_mark }}%</div></div>
                    <div><span class="text-gray-500">{{ __('Outcome') }}</span><div class="text-lg font-semibold">{{ $session->result->passed ? __('Pass') : __('Fail') }}</div></div>
                </div>
                <a href="{{ route('interview.review.export.pdf', $session) }}" class="inline-flex px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">{{ __('Download consolidated PDF') }}</a>
            </div>

            @php($snapshot = $session->result->calculation_snapshot['questions'] ?? [])
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto mb-6">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50"><tr>
                        <th class="px-4 py-2 text-left">{{ __('Question') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('Average') }}</th>
                        <th class="px-4 py-2 text-left">{{ __('Max') }}</th>
                    </tr></thead>
                    <tbody class="divide-y divide-gray-200">
                    @foreach($snapshot as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row['question_text'] ?? '' }}</td>
                            <td class="px-4 py-3">{{ $row['average_score'] ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $row['max_mark'] ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(Auth::user()->hasInterviewRole('admin', 'chair') && $session->result && $session->result->decision_status === 'pending')
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="font-medium mb-3">{{ __('Chairperson review') }}</h3>
                <form method="POST" action="{{ route('interview.review.confirm', $session) }}" class="space-y-4 max-w-xl">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Recommendation') }}</label>
                        <select name="recommendation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="recommend">{{ __('Recommend approval') }}</option>
                            <option value="do_not_recommend">{{ __('Do not recommend') }}</option>
                            <option value="conditional">{{ __('Conditional recommendation') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Chair notes') }}</label>
                        <textarea name="chair_notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">{{ __('Confirm review') }}</button>
                </form>
            </div>
        @endif

        @if(Auth::user()->hasInterviewRole('admin', 'approver') && $session->result && $session->result->decision_status === 'confirmed')
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium mb-3">{{ __('Final approval') }}</h3>
                <div class="flex gap-3">
                    <form method="POST" action="{{ route('interview.review.approve', $session) }}">
                        @csrf
                        <input type="hidden" name="approved" value="1">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold">{{ __('Approve decision') }}</button>
                    </form>
                    <form method="POST" action="{{ route('interview.review.approve', $session) }}">
                        @csrf
                        <input type="hidden" name="approved" value="0">
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm font-semibold">{{ __('Reject decision') }}</button>
                    </form>
                </div>
            </div>
        @endif
    </div></div>
</x-app-layout>
