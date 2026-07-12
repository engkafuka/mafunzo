<x-guest-layout>
    <div class="page-shell py-8">
        <div class="page-inner-md mx-auto">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 bg-[#0a71ab] text-white text-center">
                    <h1 class="text-lg font-semibold">{{ config('identity_card.organization') }}</h1>
                    <p class="text-sm text-white/90 mt-1">{{ __('Identity card verification') }}</p>
                </div>

                <div class="p-6 space-y-4">
                    @if($card->isValid())
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-900">
                            <p class="font-semibold">{{ __('Valid identity card') }}</p>
                            <p class="text-sm mt-1">{{ __('This warehouse worker identity card is active and was issued by WRRB.') }}</p>
                        </div>
                    @elseif($card->isRevoked())
                        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-900">
                            <p class="font-semibold">{{ __('Revoked') }}</p>
                            <p class="text-sm mt-1">{{ __('This identity card is no longer valid.') }}</p>
                        </div>
                    @elseif($card->isExpired())
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-950">
                            <p class="font-semibold">{{ __('Expired') }}</p>
                            <p class="text-sm mt-1">{{ __('This identity card has passed its validity date.') }}</p>
                        </div>
                    @else
                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-gray-800">
                            <p class="font-semibold">{{ __('Not published') }}</p>
                            <p class="text-sm mt-1">{{ __('This identity card has not been published yet.') }}</p>
                        </div>
                    @endif

                    <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                        <div><dt class="text-gray-500">{{ __('Name') }}</dt><dd class="font-medium text-gray-900">{{ $card->full_name }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('Registration number') }}</dt><dd class="font-mono text-gray-900">{{ $card->registration_number }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('Position') }}</dt><dd class="text-gray-900">{{ $card->position ?? '—' }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('Course') }}</dt><dd class="text-gray-900">{{ $card->course_name }}</dd></div>
                        @if($card->session_year)
                            <div><dt class="text-gray-500">{{ __('Session year') }}</dt><dd class="text-gray-900">{{ $card->session_year }}</dd></div>
                        @endif
                        @if($card->trained_year)
                            <div><dt class="text-gray-500">{{ __('Year trained') }}</dt><dd class="text-gray-900">{{ $card->trained_year }}</dd></div>
                        @endif
                        <div><dt class="text-gray-500">{{ __('Issued') }}</dt><dd class="text-gray-900">{{ $card->issued_at->format('Y-m-d') }}</dd></div>
                        <div><dt class="text-gray-500">{{ __('Valid until') }}</dt><dd class="text-gray-900">{{ $card->expires_at->format('Y-m-d') }}</dd></div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
