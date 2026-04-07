<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certification;
use App\Models\Question;
use App\Models\User;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'certificationsCount' => Certification::query()->count(),
            'activeCertificationsCount' => Certification::query()->active()->count(),
            'questionsCount' => Question::query()->count(),
            'usersCount' => User::query()->count(),
            'recentCertifications' => Certification::query()->ordered()->limit(6)->get(),
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }
}
