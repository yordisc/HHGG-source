<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserAdminController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', 'all');
        if (!in_array($role, ['all', 'admins', 'users'], true)) {
            $role = 'all';
        }

        $query = User::query()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($nestedQuery) use ($search): void {
                    $nestedQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            })
            ->when($role === 'admins', fn($query) => $query->where('is_admin', true))
            ->when($role === 'users', fn($query) => $query->where('is_admin', false));

        $users = $query->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        $adminUsersCount = User::query()->where('is_admin', true)->count();
        $regularUsersCount = User::query()->where('is_admin', false)->count();

        return view('admin.users.index', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'adminUsersCount' => $adminUsersCount,
            'regularUsersCount' => $regularUsersCount,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.create', $this->formViewData(new User()));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $generatedEmail = $this->generateInternalEmail((string) $data['name']);
        $user = User::query()->create([
            'name' => trim((string) $data['name']),
            'email' => $generatedEmail,
            'password' => (string) $data['password'],
            'email_verified_at' => ($data['email_verified'] ?? false) ? now() : null,
        ]);

        AuditLog::log('create', 'User', $user->id, $user->name);

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Usuario creado correctamente.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', $this->formViewData($user));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $payload = [
            'name' => trim((string) $data['name']),
            'email_verified_at' => ($data['email_verified'] ?? false) ? ($user->email_verified_at ?? now()) : null,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = (string) $data['password'];
        }

        $oldValues = $user->toArray();
        $user->update($payload);

        $changes = array_diff_assoc($payload, $oldValues);
        if (!empty($changes)) {
            AuditLog::log('update', 'User', $user->id, $user->name, $changes);
        }

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $name = $user->name;
        $id = $user->id;
        $user->delete();

        AuditLog::log('delete', 'User', $id, $name);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Usuario eliminado correctamente.');
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', 'all');
        if (!in_array($role, ['all', 'admins', 'users'], true)) {
            $role = 'all';
        }
        $fileName = 'users_export_' . now()->format('Ymd_His') . '.csv';

        AuditLog::log('export', 'User', null, 'CSV export', [
            'search' => $search ?: '*',
            'role' => $role,
        ]);

        return response()->streamDownload(function () use ($search, $role): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fputcsv($output, [
                'id',
                'name',
                'email',
                'email_verified_at',
                'created_at',
                'updated_at',
            ]);

            User::query()
                ->when($search !== '', function ($query) use ($search): void {
                    $query->where(function ($nestedQuery) use ($search): void {
                        $nestedQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
                })
                ->when($role === 'admins', fn($query) => $query->where('is_admin', true))
                ->when($role === 'users', fn($query) => $query->where('is_admin', false))
                ->orderBy('id')
                ->chunkById(200, function ($users) use ($output): void {
                    foreach ($users as $user) {
                        fputcsv($output, [
                            $user->id,
                            $user->name,
                            $user->email,
                            optional($user->email_verified_at)->toDateTimeString(),
                            optional($user->created_at)->toDateTimeString(),
                            optional($user->updated_at)->toDateTimeString(),
                        ]);
                    }
                });

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function importCsvForm(): View
    {
        return view('admin.users.import-csv', [
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function importCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ]);

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        if ($extension !== 'csv') {
            return redirect()
                ->route('admin.users.import.form')
                ->withErrors(['file' => 'El archivo debe ser de tipo CSV']);
        }

        $created = 0;
        $updated = 0;
        $errors = [];
        $handle = null;

        try {
            $fullPath = $file->getRealPath();
            if ($fullPath === false || $fullPath === null) {
                $fullPath = $file->path();
            }

            $handle = fopen($fullPath, 'r');
            if ($handle === false) {
                throw new \RuntimeException('No se pudo abrir el archivo CSV');
            }

            // Skip header row.
            fgetcsv($handle);

            $rowNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;

                if (! isset($row[1])) {
                    continue;
                }

                $name = trim((string) $row[1]);
                if ($name === '') {
                    $errors[] = 'Fila ' . $rowNumber . ': nombre vacío';
                    continue;
                }

                $providedEmail = trim((string) ($row[2] ?? ''));
                $email = filter_var($providedEmail, FILTER_VALIDATE_EMAIL)
                    ? $providedEmail
                    : $this->generateInternalEmail($name);

                $user = User::query()->where('email', $email)->first();
                if ($user) {
                    $user->update(['name' => $name]);
                    $updated++;
                    continue;
                }

                $plainPassword = trim((string) ($row[3] ?? ''));
                if ($plainPassword === '') {
                    $plainPassword = Str::random(16);
                }

                User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($plainPassword),
                ]);
                $created++;
            }

            AuditLog::log('import', 'User', null, 'CSV import', [
                'created' => $created,
                'updated' => $updated,
                'errors' => count($errors),
            ]);

            $message = "Importacion completada: {$created} usuarios creados, {$updated} actualizados";
            if (! empty($errors)) {
                $message .= '. Errores: ' . implode('; ', array_slice($errors, 0, 5));
            }

            return redirect()
                ->route('admin.users.index')
                ->with('status', $message);
        } catch (\Throwable $e) {
            Log::error('CSV import failed with unexpected error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return redirect()
                ->route('admin.users.import.form')
                ->with('error', 'Error inesperado al importar CSV: ' . $e->getMessage());
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }
    }

    private function formViewData(User $user): array
    {
        return [
            'user' => $user,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ];
    }

    private function generateInternalEmail(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'user';
        }

        do {
            $suffix = Str::lower(Str::random(10));
            $email = $base . '.' . $suffix . '@users.local';
        } while (User::query()->where('email', $email)->exists());

        return $email;
    }
}
