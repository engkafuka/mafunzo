<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Notifications') }}
            </h2>
            @if(Auth::user()->unreadNotifications->isNotEmpty())
                <form method="POST" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-sm text-[#0a71ab] hover:underline">{{ __('Mark all as read') }}</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-4xl space-y-4">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            @forelse($notifications as $notification)
                @php $data = $notification->data; @endphp
                <div class="bg-white shadow-sm sm:rounded-lg p-5 {{ $notification->read_at ? '' : 'border-l-4 border-[#0a71ab]' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="font-medium text-gray-900">{{ $data['title'] ?? __('Notification') }}</h3>
                            <p class="mt-1 text-sm text-gray-600">{{ $data['message'] ?? '' }}</p>
                            <p class="mt-2 text-xs text-gray-400">{{ $notification->created_at->format('Y-m-d H:i') }}</p>
                        </div>
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            <button type="submit" class="text-sm font-medium text-[#0a71ab] hover:underline">
                                {{ $data['action_label'] ?? __('Open') }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-6 text-sm text-gray-500">
                    {{ __('No notifications yet.') }}
                </div>
            @endforelse

            <x-table-pagination :paginator="$notifications" />
        </div>
    </div>
</x-app-layout>
