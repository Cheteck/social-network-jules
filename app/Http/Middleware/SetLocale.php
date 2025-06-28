<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session; // If using session to store locale

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = null;

        // Priority 1: Locale from URL parameter (e.g., /fr/some-route) - needs route setup
        // if ($request->segment(1) && in_array($request->segment(1), config('app.supported_locales'))) {
        //     $locale = $request->segment(1);
        // }

        // Priority 2: Locale from session (if user has set it previously)
        if (Session::has('locale') && in_array(Session::get('locale'), config('app.supported_locales', []))) {
            $locale = Session::get('locale');
        }

        // Priority 3: Locale from 'Accept-Language' header
        if (!$locale) {
            $headerLocale = $request->getPreferredLanguage(config('app.supported_locales', ['en']));
            if ($headerLocale) {
                $locale = $headerLocale;
            }
        }

        // Priority 4: Default locale from config
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }

        // Validate if the determined locale is supported, otherwise use fallback
        if (!in_array($locale, config('app.supported_locales', ['en']))) {
            $locale = config('app.fallback_locale', 'en');
        }

        App::setLocale($locale);

        // Optional: Store the locale in session for subsequent requests if it was determined by header or default
        if ($request->hasHeader('Accept-Language') || !Session::has('locale')) {
            Session::put('locale', $locale);
        }

        // Optional: Set locale for Carbon dates
        // \Carbon\Carbon::setLocale($locale);

        return $next($request);
    }
}
