<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Certification;
use App\Models\CertificateTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CertificateTemplateController extends Controller
{
    public function index(): View
    {
        return view('admin.certificates.templates.index', [
            'templates' => CertificateTemplate::query()
                ->where('certification_id', null)
                ->orderBy('is_default', 'desc')
                ->orderBy('name')
                ->paginate(20),
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function create(): View
    {
        $defaultHtmlTemplate = '<div class="certificate"><h1>{{nombre_completo}}</h1><p>Fecha: {{fecha}}</p></div>';
        $defaultCssTemplate = '.certificate { font-family: serif; text-align: center; padding: 2rem; }';

        return view('admin.certificates.templates.create', [
            'template' => new CertificateTemplate(),
            'templateContent' => $this->composeTemplateContent($defaultHtmlTemplate, $defaultCssTemplate),
            'certifications' => Certification::query()->active()->ordered()->get(),
            'templateResources' => $this->templateResources(),
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:certificate_templates,slug'],
            'name' => ['required', 'string', 'max:255'],
            'html_template' => ['required', 'string'],
            'css_template' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($data['is_default'] ?? false) {
            CertificateTemplate::query()
                ->where('is_default', true)
                ->where('certification_id', null)
                ->update(['is_default' => false]);
        }

        $template = CertificateTemplate::query()->create($data);
        AuditLog::log('create', 'CertificateTemplate', $template->id, $template->name, [
            'slug' => $template->slug,
            'is_default' => $template->is_default,
        ]);

        return redirect()
            ->route('admin.certificates.templates.index')
            ->with('status', 'Plantilla creada correctamente.');
    }

    public function edit(CertificateTemplate $template): View
    {
        return view('admin.certificates.templates.edit', [
            'template' => $template,
            'templateContent' => $this->composeTemplateContent($template->html_template, $template->css_template),
            'certifications' => Certification::query()->active()->ordered()->get(),
            'templateResources' => $this->templateResources(),
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function update(Request $request, CertificateTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:certificate_templates,slug,' . $template->id],
            'name' => ['required', 'string', 'max:255'],
            'html_template' => ['required', 'string'],
            'css_template' => ['nullable', 'string'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        if ($data['is_default'] ?? false) {
            CertificateTemplate::query()
                ->where('is_default', true)
                ->where('certification_id', null)
                ->where('id', '!=', $template->id)
                ->update(['is_default' => false]);
        }

        $oldValues = $template->toArray();
        $template->update($data);

        $changes = array_diff_assoc($data, $oldValues);
        if (!empty($changes)) {
            AuditLog::log('update', 'CertificateTemplate', $template->id, $template->name, $changes);
        }

        return redirect()
            ->route('admin.certificates.templates.edit', $template)
            ->with('status', 'Plantilla actualizada correctamente.');
    }

    public function destroy(CertificateTemplate $template): RedirectResponse
    {
        $name = $template->name;
        $id = $template->id;
        $template->delete();

        AuditLog::log('delete', 'CertificateTemplate', $id, $name);

        return redirect()
            ->route('admin.certificates.templates.index')
            ->with('status', 'Plantilla eliminada correctamente.');
    }

    public function certificationTemplates(Certification $certification): View
    {
        $customTemplate = CertificateTemplate::query()
            ->where('certification_id', $certification->id)
            ->first();

        $defaultTemplate = CertificateTemplate::getDefault();

        return view('admin.certificates.templates.certification', [
            'certification' => $certification,
            'customTemplate' => $customTemplate,
            'defaultTemplate' => $defaultTemplate,
            'defaultTemplateContent' => $defaultTemplate
                ? $this->composeTemplateContent($defaultTemplate->html_template, $defaultTemplate->css_template)
                : $this->composeTemplateContent('<div class="certificate"><h1>{{nombre_completo}}</h1><p>{{fecha}}</p></div>', '.certificate { font-family: serif; text-align: center; padding: 2rem; }'),
            'customTemplateContent' => $customTemplate
                ? $this->composeTemplateContent($customTemplate->html_template, $customTemplate->css_template)
                : $this->composeTemplateContent('<div class="certificate"><h1>{{nombre_completo}}</h1><p>{{fecha}}</p></div>', '.certificate { font-family: serif; text-align: center; padding: 2rem; }'),
            'templateResources' => $this->templateResources(),
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function saveCertificationTemplate(Request $request, Certification $certification): RedirectResponse
    {
        $data = $request->validate([
            'html_template' => ['required_if:use_custom,1', 'string'],
            'css_template' => ['nullable', 'string'],
            'use_custom' => ['nullable', 'boolean'],
        ]);

        if ($data['use_custom'] ?? false) {
            $oldTemplate = CertificateTemplate::query()
                ->where('certification_id', $certification->id)
                ->first();

            $templateData = [
                'slug' => $certification->slug . '_custom',
                'name' => $certification->name . ' (Personalizada)',
                'html_template' => $data['html_template'],
                'css_template' => $data['css_template'],
                'is_default' => false,
            ];

            if ($oldTemplate) {
                $oldTemplate->update($templateData);
                AuditLog::log('update', 'CertificateTemplate', $oldTemplate->id, $certification->name . ' (custom)', [
                    'html_template' => 'updated',
                    'css_template' => 'updated',
                ]);
            } else {
                $template = CertificateTemplate::query()->create(
                    array_merge($templateData, ['certification_id' => $certification->id])
                );
                AuditLog::log('create', 'CertificateTemplate', $template->id, $certification->name . ' (custom)', [
                    'html_template' => 'created',
                    'css_template' => 'created',
                ]);
            }
        } else {
            $oldTemplate = CertificateTemplate::query()
                ->where('certification_id', $certification->id)
                ->first();

            if ($oldTemplate) {
                $oldTemplate->delete();
                AuditLog::log('delete', 'CertificateTemplate', $oldTemplate->id, $certification->name . ' (custom)');
            }
        }

        return redirect()
            ->route('admin.certifications.edit', $certification)
            ->with('status', 'Plantilla de certificado actualizada correctamente.');
    }

    public function preview(CertificateTemplate $template): View
    {
        return view('admin.certificates.templates.preview', [
            'template' => $template,
            'sampleData' => [
                'nombre' => 'Juan',
                'nombre_completo' => 'Juan Pérez',
                'fecha' => now()->format('d/m/Y'),
                'serial' => 'CERT-' . strtoupper(uniqid()),
                'competencia' => 'Competencia Ejemplar',
                'nombre_certificacion' => 'Competencia Ejemplar',
                'nota' => 'Aprobado',
                'pais' => 'Colombia',
                'pais_origen' => 'Colombia',
                'documento_identidad' => 'CC 12345678',
                'horas_cursadas' => '40',
                'mencion_honorifica' => 'Mencion honorifica',
                'verificacion_url' => url('/cert/verify/CERT-DEMO/TOKEN-DEMO'),
                'verificacion_qr' => 'https://quickchart.io/qr?size=220&text=' . urlencode(url('/cert/verify/CERT-DEMO/TOKEN-DEMO')),
                'integridad_hash' => hash('sha256', 'CERT-DEMO-INTEGRIDAD'),
                'logo_institucion' => public_path('apple-touch-icon.png'),
                'firma_director' => public_path('Signature/Benjamin_Netanyahu.png'),
                'firma_director_nombre' => 'Benjamin Netanyahu',
            ],
        ]);
    }

    private function composeTemplateContent(?string $htmlTemplate, ?string $cssTemplate): string
    {
        $htmlTemplate = trim((string) $htmlTemplate);
        $cssTemplate = trim((string) $cssTemplate);

        if ($cssTemplate === '') {
            return $htmlTemplate;
        }

        return "<style>\n{$cssTemplate}\n</style>\n\n{$htmlTemplate}";
    }

    /**
     * @return array<int, array{name:string, path:string, url:string, type:string, previewUrl:string, isImage:bool}>
     */
    private function templateResources(): array
    {
        $groups = [
            ['type' => 'Certificates', 'directory' => public_path('Certificates')],
            ['type' => 'Signature', 'directory' => public_path('Signature')],
        ];

        $resources = [];
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

        foreach ($groups as $group) {
            $files = glob($group['directory'] . '/*') ?: [];
            sort($files);

            foreach ($files as $file) {
                if (!is_file($file)) {
                    continue;
                }

                $relativePath = ltrim(str_replace(public_path(), '', $file), DIRECTORY_SEPARATOR);
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $isImage = in_array($extension, $imageExtensions);

                $resources[] = [
                    'type' => $group['type'],
                    'name' => basename($file),
                    'path' => '/' . str_replace(DIRECTORY_SEPARATOR, '/', $relativePath),
                    'url' => asset($relativePath),
                    'previewUrl' => $isImage ? asset($relativePath) : null,
                    'isImage' => $isImage,
                ];
            }
        }

        return $resources;
    }
}
