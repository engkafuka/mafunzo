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
    .print-hint {
        max-width: 210mm;
        margin: 0.75rem auto 0;
        font-family: system-ui, sans-serif;
        font-size: 0.8125rem;
        color: #475569;
        text-align: center;
        line-height: 1.45;
    }
    .sheet {
        width: 210mm;
        min-height: 297mm;
        margin: 0 auto 2rem;
        padding: 10mm;
        background:
            radial-gradient(ellipse at center, #eaf7fb 0%, #e4f5d8 55%, #d8efc2 100%);
        background-color: #e4f5d8;
        position: relative;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .sheet + .sheet {
        margin-top: 2rem;
    }
    .border-frame {
        position: absolute;
        inset: 6mm;
        border: 3px solid #7cb342;
        outline: 1px solid #c5e1a5;
        outline-offset: 3px;
        pointer-events: none;
        z-index: 1;
        background-image:
            repeating-linear-gradient(0deg, transparent, transparent 7px, rgba(124,179,66,0.08) 7px, rgba(124,179,66,0.08) 8px),
            repeating-linear-gradient(90deg, transparent, transparent 7px, rgba(124,179,66,0.08) 7px, rgba(124,179,66,0.08) 8px);
    }
    .watermark {
        position: absolute;
        inset: 6mm;
        pointer-events: none;
        z-index: 0;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .watermark::before {
        content: '';
        position: absolute;
        left: 50%;
        top: 50%;
        width: 72%;
        max-width: 130mm;
        aspect-ratio: 1;
        transform: translate(-50%, -50%);
        background: var(--watermark-url) center / contain no-repeat;
        opacity: 0.11;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .inner {
        position: relative;
        z-index: 2;
        min-height: calc(297mm - 20mm);
        padding: 8mm 10mm;
        display: flex;
        flex-direction: column;
    }
    .header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 3mm;
        text-align: center;
    }
    .header img.govt,
    .header img.board {
        width: 28mm;
        height: 28mm;
        flex: 0 0 28mm;
        object-fit: contain;
        background: transparent;
    }
    .header-org {
        display: flex;
        flex-direction: column;
        justify-content: center;
        flex: 0 0 auto;
        gap: 0;
    }
    .org-line {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 16pt;
        font-weight: 800;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        color: #111;
        line-height: 1.15;
        margin: 0;
        white-space: nowrap;
    }
    .cert-heading {
        text-align: center;
        margin: 4mm 0 2mm;
    }
    .cert-title-main {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 26pt;
        font-weight: 800;
        color: #8b1e2d;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        line-height: 1.05;
        margin: 0;
    }
    .cert-title-sub {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 20pt;
        font-weight: 800;
        color: #8b1e2d;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        line-height: 1.1;
        margin: 1mm 0 0;
    }
    .body {
        flex: 1;
        text-align: center;
        padding-top: 12mm;
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
    .desc strong {
        font-weight: 700;
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
        background: transparent;
        border: 0;
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
        body {
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .toolbar { display: none !important; }
        .print-hint { display: none !important; }
        .sheet,
        .border-frame,
        .seal,
        .watermark,
        .watermark::before {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .sheet {
            margin: 0 auto;
            box-shadow: none;
            width: 210mm;
            min-height: 297mm;
            background:
                radial-gradient(ellipse at center, #eaf7fb 0%, #e4f5d8 55%, #d8efc2 100%);
            background-color: #e4f5d8;
            page-break-after: always;
            break-after: page;
        }
        .watermark::before {
            opacity: 0.14;
        }
        .sheet:last-child {
            page-break-after: auto;
            break-after: auto;
        }
    }
</style>
