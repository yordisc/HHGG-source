<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filter', 'all');
        $entity = $request->query('entity', null);

        $query = AuditLog::query()->recent();

        if ($entity && $entity !== 'all') {
            $query->byEntity($entity);
        }

        if ($filter !== 'all') {
            $query->byAction($filter);
        }

        $logs = $query->paginate(50);

        return view('admin.audit.index', [
            'logs' => $logs,
            'filter' => $filter,
            'entity' => $entity,
            'actions' => [
                'create' => 'Crear',
                'update' => 'Actualizar',
                'delete' => 'Eliminar',
                'import' => 'Importar',
                'export' => 'Exportar',
            ],
            'entities' => [
                'Certification' => 'Certificaciones',
                'Question' => 'Preguntas',
                'User' => 'Usuarios',
                'CertificateTemplate' => 'Plantillas',
            ],
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }
}
