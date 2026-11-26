<?php

namespace App\Actions\Authoring;

use App\Models\AuditLog;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class UpdateQuestionVersion
{
    /** @param array<string, mixed> $data */
    public function execute(User $actor, Question $question, QuestionVersion $version, array $data): QuestionVersion
    {
        Gate::forUser($actor)->authorize('update', $question);

        return DB::transaction(function () use ($actor, $question, $version, $data): QuestionVersion {
            $lockedQuestion = Question::query()->lockForUpdate()->findOrFail($question->getKey());
            $lockedVersion = $lockedQuestion->versions()->lockForUpdate()->findOrFail($version->getKey());

            if ((int) $data['version'] !== (int) $lockedVersion->version || $lockedVersion->status === 'published' || $lockedQuestion->published_version_id === $lockedVersion->getKey()) {
                throw new ConflictHttpException('Phiên bản đã thay đổi hoặc đã publish. Hãy mở đúng draft mới nhất trước khi lưu.');
            }

            $oldValues = $lockedVersion->only(['version', 'stem', 'solution', 'solution_blocks', 'status']);
            $lockedVersion->forceFill([
                'stem' => $data['stem'],
                'solution' => $data['solution'] ?? null,
                'solution_blocks' => $data['solution_blocks'] ?? null,
                'content_hash' => hash('sha256', $data['stem']),
                'status' => 'draft',
            ])->save();
            $lockedQuestion->forceFill(['status' => 'draft', 'updated_by' => $actor->getKey()])->save();

            AuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'event' => 'content.question_version_updated',
                'auditable_type' => QuestionVersion::class,
                'auditable_id' => $lockedVersion->getKey(),
                'old_values' => $oldValues,
                'new_values' => $lockedVersion->only(['version', 'stem', 'solution', 'solution_blocks', 'status']),
                'retention_expires_at' => now()->addMonths(6),
            ]);

            return $lockedVersion->fresh();
        });
    }
}
