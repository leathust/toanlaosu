<?php

namespace App\Actions\Admin;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Question;
use App\Models\User;
use App\Models\VisualScene;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReviewContent
{
    public function execute(User $actor, Course|Question|VisualScene $content, string $decision, string $reason, ?int $expectedVersion = null): Course|Question|VisualScene
    {
        Gate::forUser($actor)->authorize('viewAny', User::class);

        return DB::transaction(function () use ($actor, $content, $decision, $reason, $expectedVersion): Course|Question|VisualScene {
            $locked = $content->newQuery()->lockForUpdate()->findOrFail($content->getKey());
            $versionId = null;
            $version = null;
            if ($locked instanceof Question) {
                $version = $locked->versions()->latest('version')->first();
                $versionId = $version?->id;
            } elseif ($locked instanceof VisualScene) {
                $version = $locked->versions()->latest('version')->first();
                $versionId = $version?->id;
            }
            if ($expectedVersion !== null && $version?->version !== $expectedVersion) {
                throw ValidationException::withMessages(['expected_version' => 'Nội dung đã thay đổi. Hãy tải lại bản review mới nhất.']);
            }
            if ($decision === 'approve' && $version === null && ! ($locked instanceof Course)) {
                throw ValidationException::withMessages(['decision' => 'Nội dung chưa có version để publish.']);
            }
            if ($decision === 'approve') {
                $locked->status = 'published';
                $locked->updated_by = $actor->id;
                if ($locked instanceof Course) {
                    $locked->published_at = now();
                }
                if ($locked instanceof Question) {
                    $version->status = 'published';
                    $version->published_at = now();
                    $version->save();
                    $locked->published_version_id = $version->id;
                } elseif ($locked instanceof VisualScene) {
                    $version->status = 'published';
                    $version->published_at = now();
                    $version->save();
                    $locked->published_version_id = $version->id;
                }
            } else {
                $locked->status = 'draft';
                $locked->updated_by = $actor->id;
            }
            $locked->save();
            AuditLog::query()->create(['actor_id' => $actor->id, 'event' => 'content.reviewed', 'auditable_type' => $locked::class, 'auditable_id' => $locked->id, 'new_values' => ['decision' => $decision, 'reason' => $reason, 'version_id' => $versionId], 'retention_expires_at' => now()->addMonths(6)]);

            return $locked->fresh();
        });
    }
}
