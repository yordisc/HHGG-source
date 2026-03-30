<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'currentLocale' => app()->getLocale(),
            'supportedLocales' => config('app.supported_locales', ['en']),
        ]);
    }

    public function search(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:4', 'max:80'],
        ]);

        $query = trim($data['query']);

        $certificate = null;

        if (str_starts_with(strtoupper($query), 'CERT-')) {
            $certificate = Certificate::where('serial', strtoupper($query))->first();
        }

        if ($certificate === null) {
            $lookup = Certificate::documentLookupHash($query);
            $certificate = Certificate::where('doc_lookup_hash', $lookup)
                ->latest('issued_at')
                ->first();
        }

        if ($certificate !== null) {
            return redirect()->route('cert.show', ['serial' => $certificate->serial]);
        }

        return back()->with('search_message', __('app.search_not_found'));
    }
}
