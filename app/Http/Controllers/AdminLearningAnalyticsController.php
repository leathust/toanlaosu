<?php

namespace App\Http\Controllers;

use App\Data\Admin\AdminDashboardFilters;
use App\Models\User;
use App\Services\Admin\AdminLearningAnalyticsQuery;
use App\Support\SafeCsv;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminLearningAnalyticsController extends Controller
{
    public function __invoke(Request $request, AdminLearningAnalyticsQuery $query): View
    {
        Gate::authorize('viewAny', User::class);
        $filters = AdminDashboardFilters::fromRequest($request);

        return view('admin.learning-analytics', $query->overview($filters));
    }

    public function export(Request $request, AdminLearningAnalyticsQuery $query): Response
    {
        Gate::authorize('viewAny', User::class);
        $filters = AdminDashboardFilters::fromRequest($request);
        $data = $query->overview($filters);
        $rows = array_slice($data['questionDiagnostics'], 0, 1000);
        $csv = SafeCsv::row(['generated_at', 'from', 'to', 'timezone', 'question_id', 'attempts', 'accuracy', 'avg_response_time_ms']);
        foreach ($rows as $row) {
            $csv .= SafeCsv::row([$data['metadata']['generatedAt'], $data['metadata']['from'], $data['metadata']['to'], $data['metadata']['timezone'], $row['questionId'], $row['attempts'], $row['accuracy'] ?? '', $row['avgResponseTimeMs'] ?? '']);
        }

        return new Response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename=learning-analytics.csv', 'X-Export-Row-Limit' => '1000']);
    }

    public function drilldown(Request $request, string $type, int $id, AdminLearningAnalyticsQuery $query): View
    {
        Gate::authorize('viewAny', User::class);
        $filters = AdminDashboardFilters::fromRequest($request);

        return view('admin.learning-analytics', [...$query->overview($filters), 'focus' => ['type' => $type, 'id' => $id]]);
    }
}
