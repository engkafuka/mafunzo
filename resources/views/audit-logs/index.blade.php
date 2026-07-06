<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Audit Trail') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            <p class="mb-4 text-sm text-gray-600">
                {{ __('Track system changes made by administrators, staff, and trainers.') }}
            </p>

            <form method="GET" action="{{ route('audit-logs.index') }}" class="mb-6 flex flex-wrap gap-3 items-end bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div>
                    <label for="q" class="block text-xs font-medium text-gray-500 mb-1">{{ __('Search') }}</label>
                    <input type="text" id="q" name="q" value="{{ request('q') }}" placeholder="{{ __('Description, user, model...') }}"
                           class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm w-48 sm:w-56">
                </div>
                <div>
                    <label for="event" class="block text-xs font-medium text-gray-500 mb-1">{{ __('Event') }}</label>
                    <select id="event" name="event" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">{{ __('All events') }}</option>
                        @foreach($events as $value => $label)
                            <option value="{{ $value }}" @selected(request('event') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="user_id" class="block text-xs font-medium text-gray-500 mb-1">{{ __('Changed by') }}</label>
                    <select id="user_id" name="user_id" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">{{ __('Anyone') }}</option>
                        @foreach($actors as $actor)
                            <option value="{{ $actor->id }}" @selected((string) request('user_id') === (string) $actor->id)>
                                {{ $actor->name }} ({{ __(ucfirst(str_replace('_', ' ', $actor->role))) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="from" class="block text-xs font-medium text-gray-500 mb-1">{{ __('From') }}</label>
                    <input type="date" id="from" name="from" value="{{ request('from') }}"
                           class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <div>
                    <label for="to" class="block text-xs font-medium text-gray-500 mb-1">{{ __('To') }}</label>
                    <input type="date" id="to" name="to" value="{{ request('to') }}"
                           class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                </div>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                    {{ __('Filter') }}
                </button>
                @if(request()->hasAny(['q', 'event', 'user_id', 'from', 'to']))
                    <a href="{{ route('audit-logs.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-300">
                        {{ __('Clear') }}
                    </a>
                @endif
            </form>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <x-responsive-table>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('When') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('User') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Event') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Description') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Details') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($logs as $log)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap" data-label="{{ __('When') }}">
                                        {{ $log->created_at->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900" data-label="{{ __('User') }}">
                                        {{ $log->user_name ?? '—' }}
                                        @if($log->user_role)
                                            <span class="block text-xs text-gray-500">{{ __(ucfirst(str_replace('_', ' ', $log->user_role))) }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm" data-label="{{ __('Event') }}">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full
                                            @if($log->event === 'created') bg-green-100 text-green-800
                                            @elseif($log->event === 'updated') bg-blue-100 text-blue-800
                                            @elseif($log->event === 'deleted') bg-red-100 text-red-800
                                            @else bg-amber-100 text-amber-800
                                            @endif">
                                            {{ $log->eventLabel() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900" data-label="{{ __('Description') }}">
                                        {{ $log->description }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm" data-label="{{ __('Details') }}">
                                        <a href="{{ route('audit-logs.show', $log) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                                            {{ __('View') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No audit entries found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </x-responsive-table>
                <x-table-pagination :paginator="$logs" />
            </div>
        </div>
    </div>
</x-app-layout>
