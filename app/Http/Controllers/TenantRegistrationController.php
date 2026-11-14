<?php

namespace App\Http\Controllers;

use App\Core\MultiTenancy\TenantManager;
use App\Http\Requests\RegisterTenantRequest;
use App\Services\TenantRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TenantRegistrationController extends Controller
{
    /**
     * Show the tenant registration form.
     */
    public function create(): View
    {
        return view('auth.register-tenant');
    }

    /**
     * Handle the registration request.
     */
    public function store(
        RegisterTenantRequest $request,
        TenantRegistrationService $registrationService,
        TenantManager $tenantManager
    ): RedirectResponse {
        $result = $registrationService->register($request->validated());

        // Log the newly registered owner in
        Auth::login($result['owner']);

        // Activate tenant in manager/context
        $tenantManager->activateTenant($result['tenant']);

        return redirect()->route('dashboard')->with('success', "Welcome to Veggitable SaaS! Your business '{$result['tenant']->name}' and headquarters branch '{$result['branch']->name}' have been created.");
    }
}
