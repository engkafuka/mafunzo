<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="utf-8">
    <title>{{ $titleSw }}</title>
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
        }
        .card {
            width: 242.65pt;
            height: 153pt;
            overflow: hidden;
            position: relative;
            background: rgba(10, 113, 171, 0.14);
        }
        .bg-blue { background: #0a71ab; color: #fff; }
        .logo {
            width: 16pt;
            height: 16pt;
            background: #fff;
            padding: 1pt;
        }
        .photo {
            width: 38pt;
            height: 48pt;
            border: 0.3pt solid #cbd5e1;
        }
        .qr {
            width: 24pt;
            height: 24pt;
        }
        .org {
            font-size: 3.4pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.05;
            text-align: center;
        }
        .title {
            font-size: 3.4pt;
            font-weight: bold;
            line-height: 1.05;
            margin-top: 1pt;
            text-align: center;
        }
        .name {
            font-size: 5pt;
            font-weight: bold;
            margin-bottom: 1.5pt;
            line-height: 1.08;
        }
        .label {
            font-size: 2.9pt;
            color: #64748b;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .value {
            font-size: 3.8pt;
            font-weight: bold;
            margin-bottom: 1.5pt;
            line-height: 1.08;
        }
        .qr-note {
            font-size: 3pt;
            color: #475569;
            line-height: 1.12;
        }
        .badge {
            display: inline-block;
            background: #ecfdf5;
            color: #047857;
            border: 0.3pt solid #6ee7b7;
            padding: 1pt 2pt;
            font-size: 2.6pt;
            font-weight: bold;
            margin-top: 1pt;
        }
        .notice {
            font-size: 5.2pt;
            line-height: 1.35;
            text-align: center;
            color: #0f172a;
        }

        /* Page 1 — header */
        .front-hdr {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 22pt;
            overflow: hidden;
        }
        .front-hdr .logo-wrap {
            position: absolute;
            top: 3pt;
            left: 4pt;
            z-index: 2;
        }
        .front-hdr .hdr-text {
            position: absolute;
            top: 2.5pt;
            left: 0;
            right: 0;
            padding: 0 22pt;
            text-align: center;
        }

        /* Page 1 — grid body (3 columns) */
        .g-photo {
            position: absolute;
            top: 25pt;
            left: 4pt;
            width: 40pt;
            height: 50pt;
            overflow: hidden;
        }
        .g-identity {
            position: absolute;
            top: 25pt;
            left: 48pt;
            width: 96pt;
            height: 72pt;
            overflow: hidden;
        }
        .g-meta {
            position: absolute;
            top: 25pt;
            left: 148pt;
            width: 90pt;
            height: 72pt;
            overflow: hidden;
        }
        .g-course {
            position: absolute;
            top: 78pt;
            left: 4pt;
            width: 140pt;
            height: 28pt;
            overflow: hidden;
        }

        /* Page 1 — footer with QR */
        .front-ftr {
            position: absolute;
            top: 110pt;
            left: 0;
            right: 0;
            height: 43pt;
            border-top: 0.3pt solid rgba(10, 113, 171, 0.25);
            overflow: hidden;
        }
        .front-ftr .qr-wrap {
            position: absolute;
            top: 4pt;
            left: 4pt;
        }
        .front-ftr .qr-text {
            position: absolute;
            top: 5pt;
            left: 32pt;
            right: 4pt;
        }
        .front-ftr .badge-wrap {
            position: absolute;
            top: 22pt;
            left: 32pt;
        }

        /* Page 2 — notice only */
        .back-notice {
            position: absolute;
            top: 28pt;
            left: 14pt;
            right: 14pt;
            bottom: 28pt;
            overflow: hidden;
        }
    </style>
</head>
<body>
    {{-- Ukurasa 1: mbele — taarifa zote --}}
    <div class="card">
        <div class="front-hdr bg-blue">
            <div class="logo-wrap">
                <img src="{{ $logoDataUri }}" class="logo" alt="WRRB">
            </div>
            <div class="hdr-text">
                <p class="org">{{ $organizationSw }}</p>
                <p class="title">{{ $titleSw }}</p>
            </div>
        </div>

        <div class="g-photo">
            <img src="{{ $photoDataUri }}" class="photo" alt="Picha">
        </div>

        <div class="g-identity">
            <p class="name">{{ $card->full_name }}</p>
            <p class="label">Nambari ya usajili</p>
            <p class="value">{{ $card->registration_number }}</p>
            <p class="label">Cheo</p>
            <p class="value">{{ $card->position ?? '—' }}</p>
            @if($card->company_name)
                <p class="label">Mwajiri</p>
                <p class="value">{{ \Illuminate\Support\Str::limit($card->company_name, 26) }}</p>
            @endif
        </div>

        <div class="g-meta">
            <p class="label">Imetolewa</p>
            <p class="value">{{ $card->issued_at->format('Y-m-d') }}</p>
            <p class="label">Ina uhalali hadi</p>
            <p class="value">{{ $card->expires_at->format('Y-m-d') }}</p>
            @if($card->trained_year)
                <p class="label">Mwaka wa mafunzo</p>
                <p class="value">{{ $card->trained_year }}</p>
            @endif
        </div>

        <div class="g-course">
            <p class="label">Kozi / kipindi</p>
            <p class="value">{{ \Illuminate\Support\Str::limit($card->course_name, 48) }}@if($card->session_year) ({{ $card->session_year }})@endif</p>
        </div>

        <div class="front-ftr">
            <div class="qr-wrap">
                <img src="{{ $qrDataUri }}" class="qr" alt="QR">
            </div>
            <div class="qr-text">
                <p class="qr-note">Skani msimbo huu kuthibitisha kitambulisho hiki.</p>
            </div>
            <div class="badge-wrap">
                <span class="badge">Kitambulisho Rasmi cha WRRB</span>
            </div>
        </div>
    </div>

    {{-- Ukurasa 2: nyuma — maandishi tu --}}
    <div class="card">
        <div class="back-notice">
            <p class="notice">
                Kitambulisho hiki ni mali ya Serikali ya Jamhuri ya Muungano wa Tanzania.
                Hairuhusiwi kubadilisha wala kuharibu. Ikiwa umekiokota, tafadhali kabidhi katika ofisi yoyote ya serikali iliyo karibu nawe.
            </p>
        </div>
    </div>
</body>
</html>
