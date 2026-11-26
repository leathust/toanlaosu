<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\AdminHealthAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminHealthActionRequest;
use App\Models\User;
use App\Services\Admin\AdminOperationsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SystemHealthController extends Controller
{
    public function index(AdminOperationsQuery $query): array
    {
        Gate::authorize('viewAny', User::class);

        return $query->health();
    }

    public function action(AdminHealthActionRequest $request, string $fingerprint, AdminHealthAction $action): RedirectResponse
    {
        Gate::authorize('viewAny', User::class);
        $validated = $request->validated();
        $action->execute($request->user(), $fingerprint, $validated['source'], $validated['source_id'], $validated['action'], $validated['reason'], $validated['idempotency_key']);

        return back()->with('status', 'Health action completed.');
    }
}
