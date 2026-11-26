<?php

namespace App\Http\Controllers;

use App\Actions\Admin\AcknowledgeAlert;
use App\Http\Requests\AdminAlertStateRequest;
use App\Models\User;
use App\Services\Admin\AdminActionCenterQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AdminActionCenterController extends Controller
{
    public function index(Request $request, AdminActionCenterQuery $query): Response
    {
        Gate::authorize('viewAny', User::class);
        $filters = [
            'severity' => $this->allowed($request->string('severity')->toString(), ['critical', 'warning', 'info']),
            'source' => $this->allowed($request->string('source')->toString(), ['failed_job', 'webhook', 'exam', 'ai_provider', 'questions', 'scenes', 'courses', 'account_security']),
            'status' => $this->allowed($request->string('status')->toString(), ['open', 'acknowledged', 'resolved']),
        ];
        $filters = array_filter($filters);
        $data = $query->list($filters);

        return Inertia::render('Admin/ActionCenter', [
            'filters' => $filters,
            'alerts' => $data['alerts'],
            'counts' => $data['counts'],
            'filterOptions' => [
                'severities' => ['critical', 'warning', 'info'],
                'sources' => ['failed_job', 'webhook', 'exam', 'ai_provider', 'questions', 'scenes', 'courses', 'account_security'],
                'statuses' => ['open', 'acknowledged', 'resolved'],
            ],
        ]);
    }

    public function updateState(AdminAlertStateRequest $request, string $fingerprint, AcknowledgeAlert $action): RedirectResponse
    {
        $validated = $request->validated();
        $action->execute($request->user(), $fingerprint, $validated['source'], $validated['source_id'], $validated['status']);

        return back()->with('status', 'Alert state updated.');
    }

    private function allowed(string $value, array $allowed): ?string
    {
        return in_array($value, $allowed, true) ? $value : null;
    }
}
