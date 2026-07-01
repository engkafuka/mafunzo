<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Attendance (QR Code)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('app-management.index') }}" class="text-indigo-600 hover:text-indigo-800">{{ __('&larr; Back to Application Management') }}</a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="font-medium text-gray-900 mb-4">{{ __('Create attendance session') }}</h3>
                <form method="POST" action="{{ route('app-management.attendance.store') }}" class="flex flex-wrap gap-4 items-end">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Course') }}</label>
                        <select name="course_id" required class="mt-1 rounded-md border-gray-300 text-sm">
                            @foreach($courses as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Session name') }}</label>
                        <input type="text" name="name" required placeholder="e.g. Day 1 Morning" class="mt-1 rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Date') }}</label>
                        <input type="date" name="session_date" required value="{{ date('Y-m-d') }}" class="mt-1 rounded-md border-gray-300 text-sm">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">{{ __('Create & show QR') }}</button>
                </form>
            </div>

            <form method="GET" class="mb-4">
                <select name="course_id" class="rounded-md border-gray-300 text-sm" onchange="this.form.submit()">
                    <option value="">{{ __('All courses') }}</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ request('course_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </form>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Session') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Course') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Date') }}</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($sessions as $s)
                            <tr>
                                <td class="px-4 py-3 text-sm">{{ $s->name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $s->course->name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $s->session_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('app-management.attendance.show', $s) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">{{ __('View QR & attendance') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('No sessions yet. Create one above.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($sessions->hasPages())
                    <div class="px-4 py-3 border-t">{{ $sessions->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
