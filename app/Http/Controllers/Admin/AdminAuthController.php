<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login', [
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $configuredKey = trim((string) env('ADMIN_ACCESS_KEY', ''));

        if ($configuredKey === '') {
            return back()->withErrors([
                'admin_key' => 'ADMIN_ACCESS_KEY no esta configurada en .env.',
            ]);
        }

        $providedKey = (string) $request->validated('admin_key');

        if (!hash_equals($configuredKey, $providedKey)) {
            return back()->withErrors([
                'admin_key' => 'Clave admin incorrecta.',
            ])->onlyInput('admin_key');
        }

        $request->session()->regenerate();
        session(['admin_authenticated' => true]);

        return redirect()->route('admin.dashboard');
    }

    public function logout(): RedirectResponse
    {
        session()->forget('admin_authenticated');
        session()->regenerate();

        return redirect()->route('admin.login')->with('status', 'Sesion admin cerrada.');
    }
}
