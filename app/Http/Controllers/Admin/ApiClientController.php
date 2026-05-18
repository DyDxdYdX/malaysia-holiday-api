<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ApiClientController extends Controller
{
    public function index(): View
    {
        return view('admin.api-clients.index', [
            'apiClients' => ApiClient::query()->latest()->paginate(20),
        ]);
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:1', 'max:10000'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
        ]);

        $rawApiKey = Str::random(48);
        $apiClient = ApiClient::query()->create([
            ...$validated,
            'api_key_hash' => hash('sha256', $rawApiKey),
        ]);

        $auditLogger->logFromRequest(
            request: $request,
            action: 'api_client_created',
            entityType: 'api_client',
            entityId: $apiClient->id,
            newValues: $auditLogger->modelSnapshot($apiClient),
        );

        return redirect()
            ->route('admin.api-clients.index')
            ->with('status', "API client created. Key: {$rawApiKey}");
    }

    public function disable(Request $request, ApiClient $apiClient, AuditLogger $auditLogger): RedirectResponse
    {
        $oldValues = $apiClient->toArray();
        $apiClient->update(['status' => 'disabled']);

        $auditLogger->logFromRequest(
            request: $request,
            action: 'api_client_disabled',
            entityType: 'api_client',
            entityId: $apiClient->id,
            oldValues: $oldValues,
            newValues: $apiClient->fresh()?->toArray(),
        );

        return redirect()
            ->route('admin.api-clients.index')
            ->with('status', "API client {$apiClient->name} disabled.");
    }
}
