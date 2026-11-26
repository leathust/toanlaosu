<?php

namespace App\Actions\Admin;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Question;
use App\Models\User;
use App\Models\VisualScene;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApproveContent
{
    public function execute(User $actor, Course|Question|VisualScene $content, string $reason): Course|Question|VisualScene
    {
        Gate::forUser($actor)->authorize('viewAny', User::class);

        return DB::transaction(function () use ($actor, $content, $reason): Course|Question|VisualScene {
            $content->update(['status' => 'published', 'published_at' => now(), 'updated_by' => $actor->getKey()]);
            AuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'event' => 'content.approved',
                'auditable_type' => $content::class,
                'auditable_id' => $content->getKey(),
                'new_values' => ['status' => 'published', 'reason' => $reason],
                'retention_expires_at' => now()->addMonths(6),
            ]);

            return $content->fresh();
        });
    }
}
