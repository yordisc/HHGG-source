<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: letter;
            margin: 18px;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            background: #f8fafc;
        }

        .wrap {
            box-sizing: border-box;
            min-height: calc(100% - 2px);
            border: 3px solid #1d3557;
            padding: 20px 22px 18px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            page-break-inside: avoid;
            overflow: hidden;
        }

        .title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .big {
            font-size: 21px;
            font-weight: 700;
            margin: 12px 0 8px;
        }

        .badge {
            display: inline-block;
            margin-top: 6px;
            padding: 5px 11px;
            border-radius: 999px;
            background: #1d3557;
            color: white;
            font-size: 11px;
            font-weight: 700;
        }

        .meta {
            margin-top: 14px;
            font-size: 11px;
            color: #4b5563;
            line-height: 1.45;
        }

        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 14px 0;
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="title">{{ __('app.brand_name', [], 'en') }}</div>
        <p>{{ __('app.pdf_intro', [], 'en') }}</p>
        <span class="badge">{{ __('app.pdf_award', [], 'en') }}</span>

        <div class="divider"></div>

        <div class="big">{{ $certificate->first_name }} {{ $certificate->last_name }}</div>
        <p>{{ __('app.pdf_award', [], 'en') }}:
            <strong>{{ __('app.result_' . $certificate->result_key, [], 'en') }}</strong>
        </p>

        <div class="meta">
            <p>{{ __('app.serial', [], 'en') }}: {{ $certificate->serial }}</p>
            <p>{{ __('app.country', [], 'en') }}: {{ $certificate->country }}</p>
            <p>{{ __('app.valid_until', [], 'en') }}: {{ $certificate->expires_at?->format('Y-m-d') }}</p>
            @if ($certificate->revoked_at)
                <p>Revoked at: {{ $certificate->revoked_at->format('Y-m-d H:i') }}</p>
                @if ($certificate->revoked_reason)
                    <p>Revocation reason: {{ $certificate->revoked_reason }}</p>
                @endif
            @endif
            @if (!empty($verificationUrl))
                <p>Verification URL: {{ $verificationUrl }}</p>
            @endif
            @if (!empty($integrityHash))
                <p>Integrity hash: {{ $integrityHash }}</p>
            @endif
            @if (($showLegalDisclaimer ?? true) === true)
                <p>{{ __('cert.disclaimer_pdf', [], 'en') }}</p>
            @endif
            @if (!empty($directorName))
                <p>Director: {{ $directorName }}</p>
            @endif
        </div>

        @if (!empty($verificationQrDataUri))
            <div class="meta" style="margin-top: 10px;">
                <p>Verification QR</p>
                <img src="{{ $verificationQrDataUri }}" alt="Verification QR" style="width: 88px; height: 88px;">
            </div>
        @elseif (!empty($verificationUrl))
            <div class="meta" style="margin-top: 10px;">
                <p>Verification URL: {{ $verificationUrl }}</p>
            </div>
        @endif
    </div>
</body>

</html>
