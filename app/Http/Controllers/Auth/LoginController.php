<?php

namespace App\Http\Controllers\Auth;

use App\Core\MultiTenancy\TenantManager;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show login form.
     */
    public function create(): View|RedirectResponse
    {
        if (Auth::check()) {
            return Auth::user()->isSuperAdmin()
                ? redirect()->route('superadmin.dashboard')
                : redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login submission.
     */
    public function store(Request $request, TenantManager $tenantManager): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            RateLimiter::hit($throttleKey, 60);

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($throttleKey);

        $user = Auth::user();

        // 1. Check User Status
        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Your user account is suspended or inactive. Please contact your business administrator.');
        }

        // 2. Check Tenant Status (if not platform super admin)
        if ($user->tenant_id && $user->tenant) {
            if ($user->tenant->status === TenantStatus::Suspended) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->with('error', 'Your business account is suspended. Please contact platform support.');
            }

            $tenantManager->activateTenant($user->tenant);
        }

        // 3. Record Audit Log
        AuditLog::record('auth.login', ['email' => $user->email], $user, $user->tenant_id);

        $request->session()->regenerate();

        if ($user->isSuperAdmin()) {
            return redirect()->intended(route('superadmin.dashboard'));
        }

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Handle logout.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user) {
            AuditLog::record('auth.logout', ['email' => $user->email], $user, $user->tenant_id);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been logged out successfully.');
    }
}
