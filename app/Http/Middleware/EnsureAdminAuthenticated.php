<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (session('admin_authenticated') === true) {
            return $next($request);
        }

        return redirect()->route('admin.login')
            ->with('status', 'Ingresa la clave admin para continuar.');
    }
}
