<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Record attendance') }} — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-lg shadow p-6">
        <h1 class="text-xl font-semibold text-gray-900 mb-2">{{ __('Record attendance') }}</h1>
        @if($session)
            <p class="text-sm text-gray-600 mb-4">{{ $session->name }} — {{ $session->course->name }} ({{ $session->session_date->format('Y-m-d') }})</p>
        @endif

        @if (session('status'))
            <div class="mb-4 p-3 rounded-md bg-green-50 text-green-800 text-sm">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 p-3 rounded-md bg-red-50 text-red-800 text-sm">
                @foreach ($errors->all() as $e) {{ $e }} @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('app-management.attendance.scan-submit') }}">
            @csrf
            <input type="hidden" name="qr_token" value="{{ $token ?? old('qr_token') }}">
            <div class="mb-4">
                <label for="registration_number" class="block text-sm font-medium text-gray-700">{{ __('Registration number') }}</label>
                <input type="text" id="registration_number" name="registration_number" value="{{ old('registration_number') }}" required
                       placeholder="e.g. WRRB/2026/1/0001" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">{{ __('Submit attendance') }}</button>
        </form>

        @if(!$token)
            <p class="mt-4 text-sm text-gray-500">{{ __('Open the link from the QR code shown in class to record attendance.') }}</p>
        @endif
    </div>
</body>
</html>
