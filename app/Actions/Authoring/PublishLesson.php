<?php

namespace App\Actions\Authoring;

use App\Models\AuditLog;
use App\Models\ContentRevision;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class PublishLesson
{
    public function execute(User $actor, Lesson $lesson): Lesson
    {
        $lesson->loadMissing('unit.course');
        Gate::forUser($actor)->authorize('update', $lesson->unit->course);

        return DB::transaction(function () use ($actor, $lesson): Lesson {
            $lockedLesson = Lesson::query()->lockForUpdate()->findOrFail($lesson->getKey());
            if ($lockedLesson->status === 'published') {
                throw new ConflictHttpException('Lesson đã publish là bất biến.');
            }
            $oldValues = $lockedLesson->only(['status', 'published_at']);
            $lockedLesson->forceFill([
                'status' => 'published',
                'published_at' => now(),
                'updated_by' => $actor->getKey(),
            ])->save();

            $version = (int) ContentRevision::query()
                ->where('content_type', 'lesson')
                ->where('content_id', $lockedLesson->getKey())
                ->max('version') + 1;
            ContentRevision::query()->create([
                'content_type' => 'lesson',
                'content_id' => $lockedLesson->getKey(),
                'version' => $version,
                'snapshot' => $lockedLesson->only(['title', 'summary', 'status', 'lock_version', 'published_at']),
                'change_summary' => 'Lesson published',
                'created_by' => $actor->getKey(),
            ]);
            AuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'event' => 'lesson.published',
                'auditable_type' => Lesson::class,
                'auditable_id' => $lockedLesson->getKey(),
                'old_values' => $oldValues,
                'new_values' => $lockedLesson->only(['status', 'published_at']),
                'retention_expires_at' => now()->addMonths(6),
            ]);

            return $lockedLesson->fresh();
        });
    }
}
