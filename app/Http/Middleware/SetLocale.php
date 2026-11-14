<?php

namespace App\Http\Middleware;

use App\Core\MultiTenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        // 1. Session preference
        if (Session::has('locale')) {
            $locale = Session::get('locale');
        }

        // 2. Authenticated user preference
        if (! $locale && $request->user() && ! empty($request->user()->preferences['locale'])) {
            $locale = $request->user()->preferences['locale'];
        }

        // 3. Active tenant default locale
        if (! $locale) {
            $tenant = $this->context->getTenant();
            if ($tenant && ! empty($tenant->default_locale)) {
                $locale = $tenant->default_locale;
            }
        }

        // 4. Default fallback
        $locale = $locale ?? config('app.locale', 'en');

        if (in_array($locale, ['en', 'ur'], true)) {
            App::setLocale($locale);
            Session::put('locale', $locale);
        }

        return $next($request);
    }
}
