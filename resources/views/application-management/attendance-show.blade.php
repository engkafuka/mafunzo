<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $session->name }} — {{ $session->session_date->format('Y-m-d') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            <div class="mb-4">
                <x-back-link :href="route('app-management.attendance')">{{ __('Back to sessions') }}</x-back-link>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
                        <h3 class="font-medium text-gray-900">{{ __('QR Code for attendance') }}</h3>
                        <a href="{{ route('app-management.attendance.export.qr', $session) }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-700 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                            {{ __('Download QR PDF') }}
                        </a>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">{{ __('Trainees scan this QR or open the link and enter their registration number.') }}</p>
                    <div class="flex justify-center p-4 bg-gray-50 rounded-lg">
                        <img src="{{ $qrDataUri }}" alt="{{ __('QR Code') }}" class="w-48 h-48">
                    </div>
                    <p class="mt-4 text-xs text-gray-500 break-all">{{ $scanUrl }}</p>
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-medium text-gray-900">{{ __('Attendance recorded') }} ({{ $attendanceRecords->total() }})</h3>
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('app-management.attendance.export.pdf', $session) }}"
                               class="inline-flex items-center px-4 py-2 bg-gray-700 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                                {{ __('Download PDF') }}
                            </a>
                            <a href="{{ route('app-management.attendance.export.csv', $session) }}"
                               class="inline-flex items-center px-4 py-2 bg-emerald-700 text-white text-sm font-medium rounded-md hover:bg-emerald-800">
                                {{ __('Download CSV') }}
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Registration') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Time') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($attendanceRecords as $rec)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-mono">{{ $rec->trainingApplication->registration_number }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $rec->trainingApplication->first_name }} {{ $rec->trainingApplication->last_name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $rec->scanned_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-6 text-sm text-gray-500">{{ __('No attendance recorded yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-table-pagination :paginator="$attendanceRecords" />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
