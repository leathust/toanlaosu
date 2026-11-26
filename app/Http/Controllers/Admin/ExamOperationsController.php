<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\RecoverExamAttempt;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminExamRecoveryRequest;
use App\Models\ExamAttempt;
use App\Models\User;
use App\Services\Admin\AdminExamOperationsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class ExamOperationsController extends Controller
{
    public function index(Request $request, AdminExamOperationsQuery $query): Response
    {
        Gate::authorize('viewAny', User::class);
        $filters = [
            'status' => $this->allowed($request->string('status')->toString(), ['in_progress', 'submitted', 'graded', 'expired']),
            'issue' => $this->allowed($request->string('issue')->toString(), ['overdue', 'stuck']),
        ];
        $filters = array_filter($filters);
        $data = $query->overview($filters);

        return Inertia::render('Admin/Exams/Index', [
            ...$data,
            'filters' => $filters,
            'filterOptions' => [
                'statuses' => ['in_progress', 'submitted', 'graded', 'expired'],
                'issues' => ['overdue', 'stuck'],
            ],
        ]);
    }

    public function show(int $attempt, AdminExamOperationsQuery $query): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('Admin/Exams/Show', $query->detail($attempt));
    }

    public function recover(AdminExamRecoveryRequest $request, ExamAttempt $attempt, RecoverExamAttempt $action): RedirectResponse
    {
        $validated = $request->validated();
        $result = $action->execute($request->user(), $attempt, $validated['action'], $validated['reason'], $validated['idempotency_key']);

        return redirect()->route('admin.exams.attempts.show', $attempt->getKey())->with('status', $result['idempotent'] ? 'Recovery request already applied.' : 'Attempt expired according to the recovery runbook.');
    }

    private function allowed(string $value, array $allowed): ?string
    {
        return in_array($value, $allowed, true) ? $value : null;
    }
}
