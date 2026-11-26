<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\AdminAiFilters;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\AdminAiOperationsQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AiOperationsController extends Controller
{
    public function index(Request $request, AdminAiOperationsQuery $query): Response
    {
        Gate::authorize('viewAny', User::class);
        $filters = AdminAiFilters::fromRequest($request);
        $data = $query->overview($filters);

        return Inertia::render('Admin/AI/Operations', [
            ...$data,
            'filters' => [
                'range' => $filters->range,
                'compare' => $filters->compare,
                'course' => $filters->courseId,
                'provider' => $filters->provider,
                'model' => $filters->model,
                'operation' => $filters->operation,
                'timezone' => $filters->timezone,
                'message_id' => $filters->messageId,
            ],
        ]);
    }
}
