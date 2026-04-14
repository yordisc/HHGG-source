<?php

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\QuizRateLimit;
use App\Http\Middleware\EnsureAdminAuthenticated;
use App\Http\Middleware\SecureHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->appendToGroup('web', SecureHeaders::class);
        $middleware->appendToGroup('web', SetLocale::class);
        $middleware->alias([
            'quiz.rate.limit' => QuizRateLimit::class,
            'admin.auth' => EnsureAdminAuthenticated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
