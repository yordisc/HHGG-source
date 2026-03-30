<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; }
        .wrap { border: 2px solid #d4af37; padding: 28px; }
        .title { font-size: 24px; font-weight: 700; margin-bottom: 12px; }
        .big { font-size: 22px; font-weight: 700; margin: 12px 0; }
        .meta { margin-top: 16px; font-size: 12px; color: #4b5563; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="title">{{ __('app.brand_name') }}</div>
        <p>{{ __('app.pdf_intro') }}</p>

        <div class="big">{{ $certificate->first_name }} {{ $certificate->last_name }}</div>
        <p>{{ __('app.pdf_award') }}: <strong>{{ __('app.result_' . $certificate->result_key) }}</strong></p>

        <div class="meta">
            <p>{{ __('app.serial') }}: {{ $certificate->serial }}</p>
            <p>{{ __('app.country') }}: {{ $certificate->country }}</p>
            <p>{{ __('app.valid_until') }}: {{ $certificate->expires_at?->format('Y-m-d') }}</p>
            <p>{{ __('cert.disclaimer_pdf') }}</p>
        </div>
    </div>
</body>
</html>
