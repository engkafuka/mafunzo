<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My warehouse ID cards') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-4xl space-y-6">
            <p class="text-sm text-gray-600">{{ __('Download your official WRRB warehouse worker identity card after staff publish it to your account.') }}</p>

            @forelse($cards as $card)
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 class="font-medium text-gray-900">{{ $card->course_name }}</h3>
                            <p class="mt-1 text-sm text-gray-500 font-mono">{{ $card->registration_number }}</p>
                            <dl class="mt-4 grid gap-2 sm:grid-cols-2 text-sm">
                                <div><dt class="text-gray-500">{{ __('Issued') }}</dt><dd>{{ $card->issued_at->format('Y-m-d') }}</dd></div>
                                <div><dt class="text-gray-500">{{ __('Valid until') }}</dt><dd>{{ $card->expires_at->format('Y-m-d') }}</dd></div>
                                <div><dt class="text-gray-500">{{ __('Published') }}</dt><dd>{{ $card->published_at?->format('Y-m-d H:i') }}</dd></div>
                                <div><dt class="text-gray-500">{{ __('Status') }}</dt><dd>{{ $card->isExpired() ? __('Expired') : __('Active') }}</dd></div>
                            </dl>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('training.identity-cards.download', $card) }}" target="_blank" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">{{ __('Download PDF') }}</a>
                            <a href="{{ $card->verificationUrl() }}" target="_blank" class="px-4 py-2 bg-gray-200 text-gray-800 text-sm rounded-md hover:bg-gray-300">{{ __('Verification link') }}</a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-500">
                    {{ __('No identity cards have been published to your account yet.') }}
                </div>
            @endforelse

            <x-table-pagination :paginator="$cards" />
        </div>
    </div>
</x-app-layout>
