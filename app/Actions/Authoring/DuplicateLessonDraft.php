<?php

namespace App\Actions\Authoring;

use App\Models\AuditLog;
use App\Models\ContentRevision;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DuplicateLessonDraft
{
    public function execute(User $actor, Lesson $lesson): Lesson
    {
        $lesson->loadMissing('unit.course');
        Gate::forUser($actor)->authorize('update', $lesson->unit->course);

        return DB::transaction(function () use ($actor, $lesson): Lesson {
            $sortOrder = (int) $lesson->unit->lessons()->max('sort_order') + 1;
            $copy = Lesson::query()->create([
                'unit_id' => $lesson->unit_id,
                'slug' => Str::slug($lesson->title).'-draft-'.Str::lower(Str::random(5)),
                'title' => $lesson->title.' (Draft copy)',
                'summary' => $lesson->summary,
                'status' => 'draft',
                'sort_order' => $sortOrder,
                'created_by' => $actor->getKey(),
                'updated_by' => $actor->getKey(),
            ]);
            ContentRevision::query()->create([
                'content_type' => 'lesson',
                'content_id' => $copy->getKey(),
                'version' => 1,
                'snapshot' => $copy->only(['title', 'summary', 'status', 'lock_version']),
                'change_summary' => 'Draft duplicated from lesson '.$lesson->getKey(),
                'created_by' => $actor->getKey(),
            ]);
            AuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'event' => 'lesson.draft_duplicated',
                'auditable_type' => Lesson::class,
                'auditable_id' => $copy->getKey(),
                'new_values' => ['source_lesson_id' => $lesson->getKey(), 'title' => $copy->title],
                'retention_expires_at' => now()->addMonths(6),
            ]);

            return $copy->fresh();
        });
    }
}
