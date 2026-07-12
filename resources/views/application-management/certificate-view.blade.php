<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Certificate') }} — {{ $application->registration_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", Times, serif;
            background: #e8e8e8;
            color: #111;
        }
        .toolbar {
            max-width: 210mm;
            margin: 1rem auto;
            text-align: center;
        }
        .toolbar a, .toolbar button {
            display: inline-block;
            margin: 0 0.35rem;
            padding: 0.55rem 1rem;
            border-radius: 0.35rem;
            border: 0;
            cursor: pointer;
            text-decoration: none;
            font-family: system-ui, sans-serif;
            font-size: 0.875rem;
        }
        .btn-print { background: #0a71ab; color: #fff; }
        .btn-back { background: #e2e8f0; color: #1e293b; }
        .sheet {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto 2rem;
            padding: 10mm;
            background:
                radial-gradient(ellipse at center, #eaf7fb 0%, #e4f5d8 55%, #d8efc2 100%);
            position: relative;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .border-frame {
            position: absolute;
            inset: 6mm;
            border: 3px solid #7cb342;
            outline: 1px solid #c5e1a5;
            outline-offset: 3px;
            pointer-events: none;
            background-image:
                repeating-linear-gradient(0deg, transparent, transparent 7px, rgba(124,179,66,0.08) 7px, rgba(124,179,66,0.08) 8px),
                repeating-linear-gradient(90deg, transparent, transparent 7px, rgba(124,179,66,0.08) 7px, rgba(124,179,66,0.08) 8px);
        }
        .inner {
            position: relative;
            z-index: 1;
            min-height: calc(297mm - 20mm);
            padding: 8mm 10mm;
            display: flex;
            flex-direction: column;
        }
        .header {
            display: grid;
            grid-template-columns: 28mm 1fr 28mm;
            gap: 6mm;
            align-items: center;
            text-align: center;
        }
        .header img.govt {
            width: 26mm;
            height: 26mm;
            object-fit: contain;
            background: #fff;
            border-radius: 4px;
        }
        .header img.board {
            width: 26mm;
            height: 26mm;
            object-fit: contain;
            background: #fff;
            border-radius: 50%;
            padding: 1mm;
        }
        .org {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 15pt;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: #111;
            line-height: 1.15;
            margin: 0;
        }
        .cert-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 20pt;
            font-weight: 800;
            color: #8b1e2d;
            text-transform: uppercase;
            margin: 3mm 0 0;
            letter-spacing: 0.03em;
        }
        .body {
            flex: 1;
            text-align: center;
            padding-top: 18mm;
            padding-bottom: 8mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0;
        }
        .awarded {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 15pt;
            font-weight: 700;
            color: #2e7d32;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0 0 10mm;
        }
        .recipient {
            font-family: "Palatino Linotype", "Book Antiqua", Palatino, Georgia, serif;
            font-size: 34pt;
            font-style: italic;
            font-weight: 700;
            color: #111;
            margin: 0 auto;
            padding-bottom: 3mm;
            border-bottom: 1.5px solid #333;
            display: inline-block;
            min-width: 75%;
            line-height: 1.2;
        }
        .desc {
            margin: 12mm auto 0;
            max-width: 165mm;
            font-size: 16pt;
            line-height: 1.5;
            color: #111;
        }
        .reg-line {
            margin: 10mm auto 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 15pt;
            font-weight: 700;
            color: #111;
            letter-spacing: 0.02em;
        }
        .date-line {
            margin-top: 10mm;
            font-size: 15pt;
            font-style: italic;
            color: #222;
            line-height: 1.45;
        }
        .footer {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 6mm;
            align-items: end;
            margin-top: 0;
            padding-top: 8mm;
            padding-bottom: 4mm;
        }
        .qr-block { text-align: left; }
        .qr-block img {
            width: 24mm;
            height: 24mm;
            display: block;
            background: #fff;
            border: 1px solid #ccc;
        }
        .csn {
            margin-top: 2mm;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .seal {
            width: 28mm;
            height: 28mm;
            margin: 0 auto;
            border-radius: 50%;
            border: 2px solid #7cb342;
            background:
                conic-gradient(from 0deg, #c8e6c9, #fff59d, #81d4fa, #f8bbd0, #c8e6c9);
            box-shadow: inset 0 0 0 4px rgba(255,255,255,0.55);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            font-weight: 800;
            color: #1b5e20;
            text-align: center;
            line-height: 1.1;
        }
        .sign-block { text-align: center; }
        .sign-block img {
            max-width: 42mm;
            max-height: 16mm;
            object-fit: contain;
            display: block;
            margin: 0 auto 1mm;
        }
        .sign-line {
            width: 42mm;
            margin: 0 auto 1mm;
            border-bottom: 1px solid #333;
            min-height: 14mm;
        }
        .md-title {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            font-weight: 700;
            margin: 0;
        }
        .missing-sign {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            color: #b71c1c;
            font-style: italic;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .sheet {
                margin: 0;
                box-shadow: none;
                width: 210mm;
                min-height: 297mm;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" class="btn-print" onclick="window.print()">{{ __('Print certificate') }}</button>
        <a href="{{ route('app-management.certificates') }}" class="btn-back">{{ __('Back to list') }}</a>
    </div>

    <div class="sheet">
        <div class="border-frame" aria-hidden="true"></div>
        <div class="inner">
            <div class="header">
                <img class="govt" src="{{ $govtLogoUrl }}" alt="{{ __('United Republic of Tanzania') }}">
                <div>
                    <p class="org">{{ $organization }}</p>
                    <p class="cert-title">{{ $title }}</p>
                </div>
                <img class="board" src="{{ $boardLogoUrl }}" alt="WRRB">
            </div>

            <div class="body">
                <p class="awarded">{{ $awardedTo }}</p>
                <div class="recipient">{{ $fullName }}</div>
                <p class="desc">{{ $completionLine }}</p>
                <p class="reg-line">{{ __('Registration number') }}: {{ $application->registration_number }}</p>
                <p class="date-line">{{ $dateLine }}</p>
            </div>

            <div class="footer">
                <div class="qr-block">
                    <img src="{{ $qrDataUri }}" alt="QR">
                    <div class="csn">CSN.{{ $application->registration_number }}</div>
                </div>
                <div class="seal">WRRB<br>SEAL</div>
                <div class="sign-block">
                    @if($signatureUrl)
                        <img src="{{ $signatureUrl }}" alt="{{ __('Managing Director signature') }}">
                    @else
                        <div class="sign-line"></div>
                        <p class="missing-sign">{{ __('Signature not uploaded') }}</p>
                    @endif
                    <p class="md-title">{{ $mdTitle }}</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
