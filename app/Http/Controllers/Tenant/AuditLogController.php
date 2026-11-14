<?php

namespace App\Http\Controllers\Tenant;

use App\Core\MultiTenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function __construct(
        protected TenantContext $context
    ) {}

    /**
     * Display listing of tenant audit trail & login activity.
     */
    public function index(Request $request): View
    {
        $tenant = $this->context->getTenant();
        abort_unless($tenant, 403, 'Tenant context required.');

        $query = AuditLog::where('tenant_id', $tenant->id)
            ->with('user');

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $logs = $query->latest()->paginate(20)->withQueryString();

        return view('tenant.audit.index', compact('logs', 'tenant'));
    }
}
