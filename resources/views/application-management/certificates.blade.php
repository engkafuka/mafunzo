<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Certificates') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-4">
                <a href="{{ route('app-management.index') }}" class="text-indigo-600 hover:text-indigo-800">{{ __('&larr; Back to Application Management') }}</a>
            </div>

            <form method="GET" class="mb-6">
                <select name="course_id" class="rounded-md border-gray-300" onchange="this.form.submit()">
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
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Registration') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Course') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Certificate') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($applications as $app)
                            <tr>
                                <td class="px-4 py-3 text-sm font-mono">{{ $app->registration_number }}</td>
                                <td class="px-4 py-3 text-sm">{{ $app->first_name }} {{ $app->last_name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $app->course->name }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($app->isEligibleForCertificate())
                                        <a href="{{ route('app-management.certificates.show', $app) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ __('View / Print') }}</a>
                                        @if(!$app->certificate_issued_at)
                                            <form method="POST" action="{{ route('app-management.certificates.issue', $app) }}" class="inline ms-2">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800 text-sm">{{ __('Mark as issued') }}</button>
                                            </form>
                                        @else
                                            <span class="text-gray-500 text-xs">({{ __('Issued') }} {{ $app->certificate_issued_at->format('Y-m-d') }})</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">{{ __('Not eligible') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('No eligible trainees.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($applications->hasPages())
                    <div class="px-4 py-3 border-t">{{ $applications->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
