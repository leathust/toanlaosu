<?php

namespace App\Services\Admin;

use App\Models\Course;
use App\Models\Question;
use App\Models\VisualScene;
use Illuminate\Support\Collection;

final class AdminContentReviewQuery
{
    /** @return Collection<int, array<string, mixed>> */
    public function list(array $filters = []): Collection
    {
        $items = collect();
        $types = $filters['type'] ? [$filters['type']] : ['course', 'question', 'scene'];
        $reviewerId = isset($filters['assigned_reviewer_id']) && is_numeric($filters['assigned_reviewer_id'])
            ? (int) $filters['assigned_reviewer_id']
            : null;

        if (in_array('course', $types, true)) {
            Course::query()
                ->with(['creator', 'assignedReviewer:id,name'])
                ->whereIn('status', ['draft', 'review'])
                ->when($reviewerId !== null, fn ($query) => $query->where('assigned_reviewer_id', $reviewerId))
                ->latest('updated_at')
                ->limit(100)
                ->get()
                ->each(fn (Course $course) => $items->push($this->item('course', $course->id, $course->title, $course->status, $course->updated_at, $course->creator?->name, null, $course->assigned_reviewer_id, $course->assignedReviewer?->name)));
        }
        if (in_array('question', $types, true)) {
            Question::query()
                ->with(['creator', 'assignedReviewer:id,name', 'versions' => fn ($query) => $query->latest('version')->limit(1)])
                ->whereIn('status', ['draft', 'review'])
                ->when($reviewerId !== null, fn ($query) => $query->where('assigned_reviewer_id', $reviewerId))
                ->latest('updated_at')
                ->limit(100)
                ->get()
                ->each(function (Question $question) use ($items): void {
                    $version = $question->versions->first();
                    $items->push($this->item('question', $question->id, 'Question '.$question->public_id, $question->status, $question->updated_at, $question->creator?->name, $version?->version, $question->assigned_reviewer_id, $question->assignedReviewer?->name));
                });
        }
        if (in_array('scene', $types, true)) {
            VisualScene::query()
                ->with(['creator', 'assignedReviewer:id,name', 'versions' => fn ($query) => $query->latest('version')->limit(1)])
                ->whereIn('status', ['draft', 'review'])
                ->when($reviewerId !== null, fn ($query) => $query->where('assigned_reviewer_id', $reviewerId))
                ->latest('updated_at')
                ->limit(100)
                ->get()
                ->each(function (VisualScene $scene) use ($items): void {
                    $version = $scene->versions->first();
                    $items->push($this->item('scene', $scene->id, $scene->name, $scene->status, $scene->updated_at, $scene->creator?->name, $version?->version, $scene->assigned_reviewer_id, $scene->assignedReviewer?->name));
                });
        }

        return $items->when($filters['owner'] ?? null, fn (Collection $collection, string $owner): Collection => $collection->where('owner', $owner))->sortByDesc('updatedAt')->values();
    }

    /** @return array<string, mixed> */
    private function item(string $type, int $id, string $title, string $status, mixed $updatedAt, ?string $owner, ?int $version, ?int $assignedReviewerId, ?string $assignedReviewerName): array
    {
        $teacherHref = match ($type) {
            'course' => route('teacher.course', $id),
            'question' => $version === null ? null : route('teacher.questions.edit', ['question' => $id, 'version' => $version]),
            'scene' => $version === null ? null : route('teacher.scenes.edit', ['visualScene' => $id, 'version' => $version]),
            default => null,
        };

        return ['id' => $type.':'.$id, 'type' => $type, 'contentId' => $id, 'title' => $title, 'status' => $status, 'updatedAt' => $updatedAt?->toIso8601String(), 'owner' => $owner ?? 'Unknown', 'assignedReviewerId' => $assignedReviewerId, 'assignedReviewer' => $assignedReviewerName, 'version' => $version, 'href' => route('admin.content.review.show', [$type, $id]), 'teacherHref' => $teacherHref];
    }
}
