<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Certification;
use App\Support\CertificateIntegrityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateAdminController extends Controller
{
    public function index(Request $request): View
    {
        [$status, $certificationId, $search] = $this->filtersFromRequest($request);

        $certificates = $this->baseQuery($status, $certificationId, $search)
            ->paginate(20)
            ->withQueryString();

        $this->attachVerificationUrl($certificates->getCollection());

        return view('admin.certificates.index', [
            'certificates' => $certificates,
            'certifications' => Certification::query()->ordered()->get(['id', 'name', 'slug']),
            'status' => $status,
            'certificationId' => $certificationId,
            'search' => $search,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$status, $certificationId, $search] = $this->filtersFromRequest($request);
        $fileName = 'certificates_export_'.now()->format('Ymd_His').'.csv';

        AuditLog::log('export', 'Certificate', null, 'CSV export certificates', [
            'status' => $status,
            'certification_id' => $certificationId,
            'search' => $search,
        ]);

        return response()->streamDownload(function () use ($status, $certificationId, $search): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fputcsv($output, [
                'id',
                'serial',
                'first_name',
                'last_name',
                'certification_id',
                'certification_name',
                'result_key',
                'issued_at',
                'expires_at',
                'status',
                'revoked_at',
                'revoked_reason',
                'content_hash',
            ]);

            $this->baseQuery($status, $certificationId, $search)
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($output): void {
                    foreach ($rows as $certificate) {
                        fputcsv($output, [
                            $certificate->id,
                            $certificate->serial,
                            $certificate->first_name,
                            $certificate->last_name,
                            $certificate->certification_id,
                            $certificate->certification?->name,
                            $certificate->result_key,
                            optional($certificate->issued_at)->toDateTimeString(),
                            optional($certificate->expires_at)->toDateTimeString(),
                            $certificate->revoked_at ? 'revoked' : 'active',
                            optional($certificate->revoked_at)->toDateTimeString(),
                            $certificate->revoked_reason,
                            $certificate->content_hash,
                        ]);
                    }
                });

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    public function apiIndex(Request $request): JsonResponse
    {
        [$status, $certificationId, $search] = $this->filtersFromRequest($request);
        $perPage = max(1, min(200, (int) $request->query('per_page', 50)));

        $certificates = $this->baseQuery($status, $certificationId, $search)
            ->paginate($perPage)
            ->withQueryString();

        $this->attachVerificationUrl($certificates->getCollection());

        AuditLog::log('read', 'Certificate', null, 'API certificates index', [
            'status' => $status,
            'certification_id' => $certificationId,
            'search' => $search,
            'per_page' => $perPage,
        ]);

        return response()->json([
            'filters' => [
                'status' => $status,
                'certification_id' => $certificationId,
                'search' => $search,
                'per_page' => $perPage,
            ],
            'data' => $certificates->items(),
            'pagination' => [
                'current_page' => $certificates->currentPage(),
                'last_page' => $certificates->lastPage(),
                'per_page' => $certificates->perPage(),
                'total' => $certificates->total(),
            ],
        ]);
    }

    private function filtersFromRequest(Request $request): array
    {
        $status = (string) $request->query('status', 'all');
        $certificationId = (int) $request->query('certification_id', 0);
        $search = trim((string) $request->query('search', ''));

        if (!in_array($status, ['all', 'active', 'revoked'], true)) {
            $status = 'all';
        }

        return [$status, $certificationId, $search];
    }

    private function baseQuery(string $status, int $certificationId, string $search)
    {
        $query = Certificate::query()
            ->with('certification')
            ->latest('issued_at')
            ->latest('id');

        if ($status === 'revoked') {
            $query->whereNotNull('revoked_at');
        } elseif ($status === 'active') {
            $query->whereNull('revoked_at');
        }

        if ($certificationId > 0) {
            $query->where('certification_id', $certificationId);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('serial', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function attachVerificationUrl(Collection $collection): void
    {
        $integrity = app(CertificateIntegrityService::class);

        $collection->transform(function (Certificate $certificate) use ($integrity): Certificate {
            $certificate->setAttribute('verification_url', $integrity->verificationUrl($certificate));

            return $certificate;
        });
    }
}
