<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use App\Models\CertificationStatistic;
use App\Models\Certificate;
use App\Models\Question;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $totalCertifications = Certification::count();
        $activeCertifications = Certification::active()->count();
        $totalQuestions = Question::count();
        $totalCertificates = Certificate::count();
        $totalUsers = User::count();
        $adminUsersCount = User::query()->where('is_admin', true)->count();
        $regularUsersCount = User::query()->where('is_admin', false)->count();

        // Estadísticas de los últimos 30 días
        $certStats = CertificationStatistic::whereBetween('date', [
            now()->subDays(30),
            now(),
        ])->orderBy('date')->get();

        $totalAttempts = $certStats->sum('attempts_count');
        $totalCompletions = $certStats->sum('completions_count');
        $totalPasses = $certStats->sum('passes_count');
        $totalAbandonments = $certStats->sum('abandonment_count');

        $averagePassRate = $totalCompletions > 0
            ? ($totalPasses / $totalCompletions) * 100
            : 0;

        $abandonmentRate = $totalAttempts > 0
            ? ($totalAbandonments / $totalAttempts) * 100
            : 0;

        // Datos para gráfico de línea: últimos 30 días
        $dailyData = $certStats->groupBy('date')->map(function ($stats) {
            return [
                'date' => $stats->first()->date,
                'attempts' => $stats->sum('attempts_count'),
                'completions' => $stats->sum('completions_count'),
                'passes' => $stats->sum('passes_count'),
            ];
        })->values();

        // Preparar etiquetas y datos para Chart.js
        $chartDates = $dailyData->count() > 0
            ? $dailyData->pluck('date')->map(fn($date) => $date->format('M d'))->toJson()
            : json_encode([]);

        $chartAttempts = $dailyData->count() > 0
            ? $dailyData->pluck('attempts')->toJson()
            : json_encode([]);

        $chartCompletions = $dailyData->count() > 0
            ? $dailyData->pluck('completions')->toJson()
            : json_encode([]);

        // Top certificaciones
        $topCertifications = Certification::withCount('certificates')
            ->orderBy('certificates_count', 'desc')
            ->take(5)
            ->get();

        // Certificaciones menos usadas
        $underutilizedCertifications = Certification::active()
            ->withCount('certificates')
            ->orderBy('certificates_count', 'asc')
            ->take(5)
            ->get();

        // Estadísticas por certificación (últimos 30 días)
        $certificationStats = Certification::active()
            ->pluck('id', 'name')
            ->map(function ($certId, $certName) {
                $stats = CertificationStatistic::where('certification_id', $certId)
                    ->whereBetween('date', [now()->subDays(30), now()])
                    ->get();

                $attempts = $stats->sum('attempts_count');
                $passes = $stats->sum('passes_count');
                $passRate = $attempts > 0 ? ($passes / $attempts) * 100 : 0;

                return [
                    'name' => $certName,
                    'attempts' => $attempts,
                    'passes' => $passes,
                    'passRate' => round($passRate, 1),
                ];
            })->sortByDesc('attempts')->take(8)->values();

        // Preparar datos para gráfico de barras
        $certNames = $certificationStats->pluck('name')->toJson();
        $certPassRates = $certificationStats->pluck('passRate')->toJson();

        return view('admin.dashboard', [
            'certificationsCount' => $totalCertifications,
            'activeCertificationsCount' => $activeCertifications,
            'questionsCount' => $totalQuestions,
            'usersCount' => $totalUsers,
            'adminUsersCount' => $adminUsersCount,
            'regularUsersCount' => $regularUsersCount,
            'certificatesCount' => $totalCertificates,
            'totalAttempts' => $totalAttempts,
            'totalCompletions' => $totalCompletions,
            'totalPasses' => $totalPasses,
            'totalAbandonments' => $totalAbandonments,
            'averagePassRate' => round($averagePassRate, 1),
            'abandonmentRate' => round($abandonmentRate, 1),
            'recentCertifications' => Certification::ordered()->limit(6)->get(),
            'topCertifications' => $topCertifications,
            'underutilizedCertifications' => $underutilizedCertifications,
            'recentCertificates' => Certificate::latest()->limit(10)->get(),
            'chartDates' => $chartDates,
            'chartAttempts' => $chartAttempts,
            'chartCompletions' => $chartCompletions,
            'certNames' => $certNames,
            'certPassRates' => $certPassRates,
            'certificationStats' => $certificationStats,
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }
}
