<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function show(string $serial): View
    {
        $certificate = Certificate::where('serial', $serial)->firstOrFail();

        return view('cert.show', [
            'certificate' => $certificate,
            'linkedinUrl' => $this->linkedinAddUrl($certificate),
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function result(string $serial): View
    {
        $certificate = Certificate::where('serial', $serial)->firstOrFail();

        return view('result', [
            'certificate' => $certificate,
            'linkedinUrl' => $this->linkedinAddUrl($certificate),
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function downloadPdf(string $serial): Response
    {
        $certificate = Certificate::where('serial', $serial)->firstOrFail();

        $pdf = Pdf::loadView('pdf.certificate', ['certificate' => $certificate]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$certificate->serial.'.pdf"',
        ]);
    }

    private function linkedinAddUrl(Certificate $certificate): string
    {
        $issuedAt = $certificate->issued_at ?? now();
        $expiresAt = $certificate->expires_at ?? now()->addYear();
        $organizationId = trim((string) env('LINKEDIN_ORG_ID', ''));

        $params = [
            'startTask' => 'CERTIFICATION_NAME',
            'name' => __('app.brand_name').' - '.__('app.result_'.$certificate->result_key),
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

        return 'https://www.linkedin.com/profile/add?'.http_build_query($params);
    }
}
