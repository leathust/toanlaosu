<?php

namespace App\Http\Controllers;

use App\Actions\Admin\ReviewContent;
use App\Http\Requests\AdminContentBatchReviewRequest;
use App\Http\Requests\AdminContentReviewRequest;
use App\Models\Course;
use App\Models\Question;
use App\Models\User;
use App\Models\VisualScene;
use App\Services\Admin\AdminContentReviewQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AdminContentReviewController extends Controller
{
    public function index(Request $request, AdminContentReviewQuery $query): View
    {
        Gate::authorize('viewAny', User::class);

        $type = $request->string('type')->toString() ?: null;
        $reviewerId = $request->integer('reviewer') ?: null;
        $reviewers = User::query()
            ->where('status', 'active')
            ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'admin'))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.content-review', [
            'items' => $query->list(['type' => $type, 'assigned_reviewer_id' => $reviewerId]),
            'filters' => ['type' => $type, 'reviewer' => $reviewerId],
            'reviewers' => $reviewers,
        ]);
    }

    public function show(string $type, int $id): View
    {
        Gate::authorize('viewAny', User::class);
        [$content, $version] = $this->resolve($type, $id);

        return view('admin.content-review-detail', compact('content', 'version', 'type'));
    }

    public function review(AdminContentReviewRequest $request, string $type, int $id, ReviewContent $action): RedirectResponse
    {
        [$content] = $this->resolve($type, $id);
        $action->execute($request->user(), $content, $request->validated('decision'), $request->validated('reason'), $request->validated('expected_version'));

        return redirect()->route('admin.content.review')->with('status', 'Review decision saved.');
    }

    public function batchReview(AdminContentBatchReviewRequest $request, ReviewContent $action): RedirectResponse
    {
        foreach ($request->validated('content_ids') as $id) {
            $action->execute($request->user(), Course::query()->findOrFail($id), $request->validated('decision'), $request->validated('reason'));
        }

        return back()->with('status', 'Batch review completed.');
    }

    /** @return array{0: Course|Question|VisualScene, 1: mixed} */
    private function resolve(string $type, int $id): array
    {
        $model = match ($type) {
            'course' => Course::query()->findOrFail($id),
            'question' => Question::query()->with(['versions' => fn ($query) => $query->latest('version')])->findOrFail($id),
            'scene' => VisualScene::query()->with(['versions' => fn ($query) => $query->latest('version')])->findOrFail($id),
            default => abort(404),
        };

        return [$model, $model instanceof Course ? null : $model->versions->first()];
    }
}
