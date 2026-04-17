<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Support\CertificateIntegrityService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function show(string $serial): View
    {
        $certificate = Certificate::where('serial', $serial)->firstOrFail();
        $integrity = app(CertificateIntegrityService::class);
        $certificate = $this->ensureIntegrityMetadata($certificate, $integrity);

        return view('cert.show', [
            'certificate' => $certificate,
            'linkedinUrl' => $this->linkedinAddUrl($certificate),
            'verificationUrl' => $integrity->verificationUrl($certificate),
            'verificationQrUrl' => $integrity->verificationQrUrl($certificate),
            'integrityHash' => $certificate->content_hash,
            'verificationChecked' => false,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function verify(Request $request, string $serial, string $token): View|JsonResponse
    {
        $certificate = Certificate::where('serial', $serial)->firstOrFail();
        $integrity = app(CertificateIntegrityService::class);
        $certificate = $this->ensureIntegrityMetadata($certificate, $integrity);
        $wantsJson = $this->wantsStructuredJson($request);

        $basePayload = [
            'serial' => $certificate->serial,
            'status' => $certificate->revoked_at ? 'revoked' : 'active',
            'revoked_at' => $certificate->revoked_at?->toAtomString(),
            'revoked_reason' => $certificate->revoked_reason,
            'integrity_hash' => $certificate->content_hash,
            'verification_checked_at' => now()->toAtomString(),
            'certificate' => [
                'full_name' => trim($certificate->first_name . ' ' . $certificate->last_name),
                'result_key' => $certificate->result_key,
                'issued_at' => $certificate->issued_at?->toAtomString(),
                'expires_at' => $certificate->expires_at?->toAtomString(),
            ],
        ];

        if ($certificate->revoked_at !== null) {
            if ($wantsJson) {
                return response()->json(array_merge($basePayload, [
                    'verified' => false,
                    'error' => 'certificate_revoked',
                    'message' => 'Certificado revocado.',
                ]), 410);
            }

            abort(410, 'Certificado revocado.');
        }

        if (! $integrity->isValidVerificationToken($certificate, $token)) {
            if ($wantsJson) {
                return response()->json(array_merge($basePayload, [
                    'verified' => false,
                    'error' => 'invalid_token',
                    'message' => 'Token de verificación inválido.',
                ]), 403);
            }

            abort(403, 'Token de verificación inválido.');
        }

        if ($wantsJson) {
            return response()->json(array_merge($basePayload, [
                'verified' => true,
                'verification_url' => $integrity->verificationUrl($certificate),
            ]));
        }

        return view('cert.show', [
            'certificate' => $certificate,
            'linkedinUrl' => $this->linkedinAddUrl($certificate),
            'verificationUrl' => $integrity->verificationUrl($certificate),
            'verificationQrUrl' => $integrity->verificationQrUrl($certificate),
            'integrityHash' => $certificate->content_hash,
            'verificationChecked' => true,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    private function wantsStructuredJson(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->query('format') === 'json';
    }

    public function result(string $serial): View
    {
        $certificate = Certificate::where('serial', $serial)->firstOrFail();
        $integrity = app(CertificateIntegrityService::class);
        $certificate = $this->ensureIntegrityMetadata($certificate, $integrity);

        return view('result', [
            'certificate' => $certificate,
            'linkedinUrl' => $this->linkedinAddUrl($certificate),
            'verificationUrl' => $integrity->verificationUrl($certificate),
            'verificationQrUrl' => $integrity->verificationQrUrl($certificate),
            'integrityHash' => $certificate->content_hash,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function downloadPdf(string $serial): Response
    {
        $certificate = Certificate::with('certification.certificateTemplate')
            ->where('serial', $serial)
            ->firstOrFail();

        $currentLocale = app()->getLocale();
        app()->setLocale('en');

        $template = $this->resolveTemplateForCertificate($certificate);
        $integrity = app(CertificateIntegrityService::class);
        $certificate = $this->ensureIntegrityMetadata($certificate, $integrity);
        $verificationUrl = $integrity->verificationUrl($certificate);
        $verificationQrUrl = $integrity->verificationQrUrl($certificate);
        $allowPdfImages = $this->isGdAvailable();

        if ($template !== null && trim((string) $template->html_template) !== '') {
            $pdf = Pdf::loadView('pdf.certificate-template', [
                'cssTemplate' => (string) ($template->css_template ?? ''),
                'renderedHtml' => $this->renderTemplateHtml($template->html_template, $certificate, $verificationUrl, $verificationQrUrl, $allowPdfImages),
            ]);
        } else {
            $pdf = Pdf::loadView('pdf.certificate', [
                'certificate' => $certificate,
                'verificationUrl' => $verificationUrl,
                'verificationQrUrl' => $verificationQrUrl,
                'integrityHash' => $certificate->content_hash,
                'showLegalDisclaimer' => ! $this->isOfficialCertificateMode(),
                'allowPdfImages' => $allowPdfImages,
            ]);
        }

        app()->setLocale($currentLocale);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $certificate->serial . '.pdf"',
        ]);
    }

    private function linkedinAddUrl(Certificate $certificate): string
    {
        $issuedAt = $certificate->issued_at ?? now();
        $expiresAt = $certificate->expires_at ?? now()->addYear();
        $organizationId = trim((string) env('LINKEDIN_ORG_ID', ''));

        $params = [
            'startTask' => 'CERTIFICATION_NAME',
            'name' => __('app.brand_name') . ' - ' . __('app.result_' . $certificate->result_key),
            'issueYear' => $issuedAt->format('Y'),
            'issueMonth' => $issuedAt->format('m'),
            'expirationYear' => $expiresAt->format('Y'),
            'expirationMonth' => $expiresAt->format('m'),
            'certUrl' => route('cert.show', ['serial' => $certificate->serial]),
            'certId' => $certificate->serial,
        ];

        if ($organizationId !== '') {
            $params['organizationId'] = $organizationId;
        }

        return 'https://www.linkedin.com/profile/add?' . http_build_query($params);
    }

    private function resolveTemplateForCertificate(Certificate $certificate): ?CertificateTemplate
    {
        $custom = $certificate->certification?->certificateTemplate;
        if ($custom !== null) {
            return $custom;
        }

        return CertificateTemplate::getDefault();
    }

    private function renderTemplateHtml(string $htmlTemplate, Certificate $certificate, string $verificationUrl, string $verificationQrUrl, bool $allowPdfImages = true): string
    {
        $issuedAt = $certificate->issued_at ?? $certificate->created_at ?? now();
        $validUntil = $certificate->expires_at?->format('Y-m-d') ?? '';

        $replacements = [
            '{{nombre}}' => trim($certificate->first_name . ' ' . $certificate->last_name),
            '{{fecha}}' => $issuedAt->format('d/m/Y'),
            '{{serial}}' => $certificate->serial,
            '{{competencia}}' => (string) ($certificate->certification?->name ?? __('app.brand_name', [], 'en')),
            '{{nota}}' => (string) __('app.result_' . $certificate->result_key, [], 'en'),
            '{{pais}}' => (string) $certificate->country,
            '{{vigencia}}' => $validUntil,
            '{{verificacion_url}}' => $verificationUrl,
            '{{verificacion_qr}}' => $allowPdfImages ? $verificationQrUrl : '',
            '{{integridad_hash}}' => (string) ($certificate->content_hash ?? ''),
            '{{logo_institucion}}' => $allowPdfImages ? public_path('apple-touch-icon.png') : '',
            '{{firma_director}}' => $allowPdfImages ? public_path('Signature/Benjamin_Netanyahu.png') : '',
        ];

        $rendered = str_replace(array_keys($replacements), array_values($replacements), $htmlTemplate);

        if (! $allowPdfImages) {
            // Prevent Dompdf from trying to load image resources when GD is not available.
            $rendered = (string) preg_replace('/<img\b[^>]*>/i', '', $rendered);
        }

        return $rendered;
    }

    private function isGdAvailable(): bool
    {
        return extension_loaded('gd');
    }

    private function isOfficialCertificateMode(): bool
    {
        return config('app.certificate_mode', 'demo') === 'official';
    }

    private function ensureIntegrityMetadata(Certificate $certificate, CertificateIntegrityService $integrity): Certificate
    {
        $updates = [];

        if (empty($certificate->content_hash)) {
            $updates['content_hash'] = $integrity->contentHash($certificate);
        }

        if (empty($certificate->verification_token_hash)) {
            $updates['verification_token_hash'] = $integrity->verificationTokenHash($certificate);
        }

        if ($updates !== []) {
            $certificate->update($updates);
            $certificate->refresh();
        }

        return $certificate;
    }
}
