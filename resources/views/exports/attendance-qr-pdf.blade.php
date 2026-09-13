<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Attendance QR code') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111; text-align: center; padding: 24px; }
        h1 { font-size: 18px; margin: 0 0 16px; }
        .details { font-size: 12px; line-height: 1.6; margin-bottom: 20px; }
        .details strong { display: inline-block; min-width: 70px; }
        .qr-wrap { margin: 16px auto; padding: 12px; border: 1px solid #ddd; display: inline-block; }
        .qr-wrap img { width: 260px; height: 260px; }
        .hint { font-size: 10px; color: #555; margin-top: 16px; }
        .url { font-size: 8px; color: #666; word-break: break-all; margin-top: 8px; }
    </style>
</head>
<body>
    <h1>{{ __('Training attendance QR code') }}</h1>

    <div class="details">
        <p><strong>{{ __('Course') }}:</strong> {{ $session->course?->name ?? '—' }}</p>
        <p><strong>{{ __('Session') }}:</strong> {{ $session->name }}</p>
        <p><strong>{{ __('Date') }}:</strong> {{ $session->session_date?->format('d M Y') ?? '—' }}</p>
    </div>

    <div class="qr-wrap">
        <img src="{{ $qrDataUri }}" alt="{{ __('QR Code') }}">
    </div>

    <p class="hint">{{ __('Trainees scan this code or open the link below and enter their registration number.') }}</p>
    <p class="url">{{ $scanUrl }}</p>
</body>
</html>
