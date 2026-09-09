<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Interview Users') }}</h2>
                <p class="mt-1 text-sm text-gray-500">{{ __('Manage interview accounts, roles, and module access') }}</p>
            </div>
            <a href="{{ route('interview.user-roles.create') }}"
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-md shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('Add User') }}
            </a>
        </div>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            @include('interview._alerts')
            @include('interview._nav')

            <div class="mb-4 p-3 rounded-lg bg-blue-50 border border-blue-100 text-sm text-blue-800">
                {{ __('Interview roles are separate from training roles. An “Interview only” user sees Interviews and nothing in Application Management or Training.') }}
            </div>

            {{-- Filters --}}
            <form method="GET" action="{{ route('interview.user-roles.index') }}" class="mb-4 filter-bar items-stretch sm:items-center">
                <select name="interview_role" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('All Roles') }}</option>
                    @foreach($rolesConfig as $key => $label)
                        <option value="{{ $key }}" @selected(($filters['interview_role'] ?? '') === $key)>{{ $label }}</option>
                    @endforeach
                </select>

                <select name="type" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('All Types') }}</option>
                    <option value="interview_only" @selected(($filters['type'] ?? '') === 'interview_only')>{{ __('Interview only') }}</option>
                    <option value="shared" @selected(($filters['type'] ?? '') === 'shared')>{{ __('Shared (has training role)') }}</option>
                </select>

                <select name="status" class="rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>{{ __('Inactive') }}</option>
                </select>

                <div class="relative flex-1 min-w-[200px] w-full sm:w-auto">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                    <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                           placeholder="{{ __('Search by name or email...') }}"
                           class="w-full pl-9 rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <button type="submit" class="px-3 py-1.5 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-700">{{ __('Filter') }}</button>
                @if(request()->hasAny(['q', 'interview_role', 'type', 'status']))
                    <a href="{{ route('interview.user-roles.index') }}" class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900 text-center">{{ __('Clear') }}</a>
                @endif
            </form>

            {{-- Users table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Name') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Email') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Type') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Interview roles') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($users as $user)
                                @php
                                    $initials = collect(explode(' ', $user->name))->map(fn ($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                                    $isInterviewOnly = $user->role === 'interview';
                                    $isActive = ($user->interview_status ?? 'active') === 'active';
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="shrink-0 w-9 h-9 rounded-full bg-[#0a71ab] text-white flex items-center justify-center text-xs font-semibold uppercase">
                                                {{ $initials }}
                                            </div>
                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate">{{ $user->name }}</div>
                                                @unless($isInterviewOnly)
                                                    <div class="text-[11px] text-gray-400">{{ __('System:') }} {{ str_replace('_', ' ', $user->role) }}</div>
                                                @endunless
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        @if($isInterviewOnly)
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800">{{ __('Interview only') }}</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">{{ __('Shared') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap gap-1">
                                            @forelse($user->interviewRoles as $assignment)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-xs font-medium">
                                                    {{ $assignment->roleLabel() }}
                                                    <form method="POST" action="{{ route('interview.user-roles.destroy', [$user, $assignment->role]) }}" class="inline"
                                                          onsubmit="return confirm('{{ __('Remove :role?', ['role' => $assignment->roleLabel()]) }}')">
                                                        @csrf @method('DELETE')
                                                        <button type="submit" class="text-emerald-600 hover:text-red-600 leading-none" title="{{ __('Remove') }}">×</button>
                                                    </form>
                                                </span>
                                            @empty
                                                <span class="text-xs text-gray-400">{{ __('None') }}</span>
                                            @endforelse
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($isActive)
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">{{ __('Active') }}</span>
                                        @else
                                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                        <a href="{{ route('interview.user-roles.edit', $user) }}" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                                            {{ __('Edit') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500">
                                        {{ __('No interview users found.') }}
                                        <a href="{{ route('interview.user-roles.create') }}" class="text-indigo-600 font-medium ms-1">{{ __('Add one') }}</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($users->hasPages())
                    <div class="px-4 py-3 border-t border-gray-100">
                        <x-table-pagination :paginator="$users" />
                    </div>
                @endif
            </div>

            {{-- Grant access to existing training/staff user --}}
            @if($assignableUsers->isNotEmpty())
                <div class="mt-6 bg-white shadow-sm sm:rounded-lg p-6" x-data="{ open: false }">
                    <button type="button" @click="open = !open" class="flex items-center justify-between w-full text-left">
                        <div>
                            <h3 class="font-semibold text-gray-900 text-sm">{{ __('Grant interview access to an existing user') }}</h3>
                            <p class="text-xs text-gray-500 mt-0.5">{{ __('Use this when a staff/admin/trainer should also work in Interviews without creating a new account.') }}</p>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <form x-show="open" x-cloak method="POST" action="{{ route('interview.user-roles.store') }}" class="mt-4 space-y-4 border-t border-gray-100 pt-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('Existing user') }}</label>
                            <select name="user_id" required class="block w-full rounded-md border-gray-300 shadow-sm text-sm">
                                <option value="">— {{ __('select') }} —</option>
                                @foreach($assignableUsers as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }}) — {{ $u->role }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-2">{{ __('Interview roles') }} <span class="text-gray-400 font-normal text-xs">({{ __('tick all that apply') }})</span></p>
                            <div class="flex flex-wrap gap-3">
                                @foreach($rolesConfig as $key => $label)
                                    <label class="inline-flex items-center gap-1.5 text-sm">
                                        <input type="checkbox" name="roles[]" value="{{ $key }}" class="rounded border-gray-300 text-indigo-600">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">{{ __('Grant access') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
