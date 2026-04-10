<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; background: #f8fafc; }
        .wrap { border: 3px solid #1d3557; padding: 28px; background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
        .title { font-size: 24px; font-weight: 700; margin-bottom: 8px; letter-spacing: 0.06em; text-transform: uppercase; }
        .big { font-size: 22px; font-weight: 700; margin: 16px 0 8px; }
        .badge { display: inline-block; margin-top: 8px; padding: 6px 12px; border-radius: 999px; background: #1d3557; color: white; font-size: 12px; font-weight: 700; }
        .meta { margin-top: 20px; font-size: 12px; color: #4b5563; line-height: 1.7; }
        .divider { height: 1px; background: #e5e7eb; margin: 18px 0; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="title">{{ __('app.brand_name', [], 'en') }}</div>
        <p>{{ __('app.pdf_intro', [], 'en') }}</p>
        <span class="badge">{{ __('app.pdf_award', [], 'en') }}</span>

        <div class="divider"></div>

        <div class="big">{{ $certificate->first_name }} {{ $certificate->last_name }}</div>
        <p>{{ __('app.pdf_award', [], 'en') }}: <strong>{{ __('app.result_' . $certificate->result_key, [], 'en') }}</strong></p>

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
        </div>

        @if (!empty($verificationQrUrl))
            <div class="meta" style="margin-top: 14px;">
                <p>Verification QR</p>
                <img src="{{ $verificationQrUrl }}" alt="Verification QR" style="width: 90px; height: 90px;">
            </div>
        @endif
    </div>
</body>
</html>
