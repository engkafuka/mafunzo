<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('User Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-4 flex flex-wrap items-center gap-4">
                <a href="{{ route('users.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    {{ __('Add User') }}
                </a>
                <form method="GET" action="{{ route('users.index') }}" class="flex flex-wrap gap-2 items-center">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Search name or email...') }}"
                           class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <select name="role" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                        <option value="">{{ __('All roles') }}</option>
                        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>{{ __('Super Admin') }}</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>{{ __('Admin') }}</option>
                        <option value="trainer" {{ request('role') === 'trainer' ? 'selected' : '' }}>{{ __('Trainer') }}</option>
                        <option value="staff" {{ request('role') === 'staff' ? 'selected' : '' }}>{{ __('Staff') }}</option>
                        <option value="trainee" {{ request('role') === 'trainee' ? 'selected' : '' }}>{{ __('Trainee') }}</option>
                    </select>
                    <button type="submit" class="px-3 py-1.5 bg-gray-200 rounded-md text-sm hover:bg-gray-300">{{ __('Filter') }}</button>
                </form>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Email') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Role') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($users as $user)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $user->name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $user->email }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full
                                            @if($user->role === 'super_admin') bg-purple-100 text-purple-800
                                            @elseif($user->role === 'admin') bg-indigo-100 text-indigo-800
                                            @elseif($user->role === 'trainer') bg-blue-100 text-blue-800
                                            @elseif($user->role === 'staff') bg-amber-100 text-amber-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ __(ucfirst(str_replace('_', ' ', $user->role))) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <a href="{{ route('users.edit', $user) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ __('Edit') }}</a>
                                        @if($user->id !== auth()->id())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="inline-block ms-2" onsubmit="return confirm('{{ __('Are you sure you want to delete this user?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">{{ __('Delete') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('No users found.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($users->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200">{{ $users->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
