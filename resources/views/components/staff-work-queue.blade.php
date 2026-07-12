@props(['workQueue' => []])

@php
    $items = [
        [
            'count' => $workQueue['pending_registrations'] ?? 0,
            'label' => __('Pending registrations'),
            'url' => route('app-management.registrations.index', ['status' => 'pending']),
        ],
        [
            'count' => $workQueue['pending_application_reviews'] ?? 0,
            'label' => __('Applications to review'),
            'url' => route('app-management.applications'),
        ],
        [
            'count' => $workQueue['pending_payment_verifications'] ?? 0,
            'label' => __('Payment verifications'),
            'url' => route('app-management.applications'),
        ],
        [
            'count' => $workQueue['unpublished_exam_results'] ?? 0,
            'label' => __('Exam results to publish'),
            'url' => route('app-management.exam-results'),
        ],
        [
            'count' => $workQueue['eligible_id_cards'] ?? 0,
            'label' => __('Eligible for ID cards'),
            'url' => route('app-management.identity-cards.index', ['status_filter' => 'eligible']),
        ],
        [
            'count' => $workQueue['draft_id_cards'] ?? 0,
            'label' => __('Draft ID cards to publish'),
            'url' => route('app-management.identity-cards.index', ['status_filter' => 'draft']),
        ],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow-sm sm:rounded-lg p-6']) }}>
    <h3 class="font-medium text-gray-900 mb-4">{{ __('Work for today') }}</h3>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($items as $item)
            <a href="{{ $item['url'] }}" class="rounded-lg border border-gray-200 px-4 py-3 hover:border-[#0a71ab] hover:bg-[#0a71ab]/5 transition">
                <p class="text-2xl font-semibold text-[#0a71ab]">{{ $item['count'] }}</p>
                <p class="mt-1 text-sm text-gray-700">{{ $item['label'] }}</p>
            </a>
        @endforeach
    </div>
</div>
