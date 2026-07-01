<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $session->name }} — {{ $session->session_date->format('Y-m-d') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('app-management.attendance') }}" class="text-indigo-600 hover:text-indigo-800">{{ __('&larr; Back to sessions') }}</a>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-900 mb-2">{{ __('QR Code for attendance') }}</h3>
                    <p class="text-sm text-gray-600 mb-4">{{ __('Trainees scan this QR or open the link and enter their registration number.') }}</p>
                    <div class="flex justify-center p-4 bg-gray-50 rounded-lg">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($scanUrl) }}" alt="QR Code" class="w-48 h-48">
                    </div>
                    <p class="mt-4 text-xs text-gray-500 break-all">{{ $scanUrl }}</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="font-medium text-gray-900 mb-4">{{ __('Attendance recorded') }} ({{ $session->attendanceRecords->count() }})</h3>
                    <ul class="space-y-2">
                        @forelse($session->attendanceRecords as $rec)
                            <li class="text-sm flex justify-between">
                                <span>{{ $rec->trainingApplication->registration_number }} — {{ $rec->trainingApplication->first_name }} {{ $rec->trainingApplication->last_name }}</span>
                                <span class="text-gray-500">{{ $rec->scanned_at->format('H:i') }}</span>
                            </li>
                        @empty
                            <li class="text-gray-500">{{ __('No attendance recorded yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
