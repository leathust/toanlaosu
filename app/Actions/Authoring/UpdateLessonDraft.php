<?php

namespace App\Actions\Authoring;

use App\Models\AuditLog;
use App\Models\ContentRevision;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UpdateLessonDraft
{
    /**
     * @param  array{lock_version:int,title:string,summary?:string|null}  $data
     */
    public function execute(User $actor, Lesson $lesson, array $data): Lesson
    {
        $lesson->loadMissing('unit.course');
        Gate::forUser($actor)->authorize('update', $lesson->unit->course);

        return DB::transaction(function () use ($actor, $lesson, $data): Lesson {
            $lockedLesson = Lesson::query()->lockForUpdate()->findOrFail($lesson->getKey());

            if ($lockedLesson->status === 'published') {
                throw new ConflictHttpException('Lesson đã publish là bất biến. Hãy tạo draft copy để chỉnh sửa.');
            }

            if ((int) $data['lock_version'] !== (int) $lockedLesson->lock_version) {
                throw new ConflictHttpException('Bản nháp đã thay đổi. Hãy tải lại phiên bản mới nhất trước khi lưu.');
            }

            $oldValues = $lockedLesson->only(['title', 'summary', 'status', 'lock_version']);
            $lockedLesson->forceFill([
                'title' => $data['title'],
                'summary' => $data['summary'] ?? null,
                'status' => 'draft',
                'updated_by' => $actor->getKey(),
                'lock_version' => $lockedLesson->lock_version + 1,
            ])->save();

            self::recordRevision($lockedLesson, $actor, 'Draft lesson updated');
            AuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'event' => 'lesson.draft_updated',
                'auditable_type' => Lesson::class,
                'auditable_id' => $lockedLesson->getKey(),
                'old_values' => $oldValues,
                'new_values' => $lockedLesson->only(['title', 'summary', 'status', 'lock_version']),
                'retention_expires_at' => now()->addMonths(6),
            ]);

            return $lockedLesson->fresh();
        });
    }

    private static function recordRevision(Lesson $lesson, User $actor, string $summary): void
    {
        $version = (int) ContentRevision::query()
            ->where('content_type', 'lesson')
            ->where('content_id', $lesson->getKey())
            ->max('version') + 1;

        ContentRevision::query()->create([
            'content_type' => 'lesson',
            'content_id' => $lesson->getKey(),
            'version' => $version,
            'snapshot' => $lesson->only(['title', 'summary', 'status', 'lock_version']),
            'change_summary' => $summary,
            'created_by' => $actor->getKey(),
        ]);
    }
}
