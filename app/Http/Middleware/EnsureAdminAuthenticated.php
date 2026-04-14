<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = Auth::user();

        if ($user && (bool) $user->is_admin) {
            return $next($request);
        }

        return redirect()->route('admin.login')
            ->with('status', 'Ingresa con una cuenta de administrador para continuar.');
    }
}
