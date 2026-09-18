@props([
    'application',
    'fullName',
    'organization',
    'title',
    'awardedTo',
    'bodyText',
    'dateLine',
    'mdTitle',
    'govtLogoUrl',
    'boardLogoUrl',
    'signatureUrl',
    'qrDataUri',
])

<div class="sheet">
    <div class="border-frame" aria-hidden="true"></div>
    <div class="watermark" style="--watermark-url: url('{{ $boardLogoUrl }}');" aria-hidden="true"></div>
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
            <p class="desc">{!! $bodyText !!}</p>
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
