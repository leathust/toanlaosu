<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EnrollStudent
{
    /**
     * @param  array{period_number:int,status?:string,starts_at?:mixed,ends_at?:mixed,source?:?string}  $attributes
     */
    public function execute(User $actor, Course $course, User $student, array $attributes): Enrollment
    {
        Gate::forUser($actor)->authorize('update', $course);

        if (! $student->isActive()) {
            throw ValidationException::withMessages(['user_id' => 'Không thể ghi danh tài khoản không hoạt động.']);
        }

        return DB::transaction(function () use ($actor, $course, $student, $attributes): Enrollment {
            $enrollment = Enrollment::query()->firstOrCreate(
                ['course_id' => $course->id, 'user_id' => $student->id, 'period_number' => $attributes['period_number']],
                [
                    'status' => $attributes['status'] ?? 'active',
                    'starts_at' => $attributes['starts_at'] ?? null,
                    'ends_at' => $attributes['ends_at'] ?? null,
                    'source' => $attributes['source'] ?? 'manual',
                    'granted_by' => $actor->id,
                ],
            );

            if ($enrollment->wasRecentlyCreated) {
                AuditLog::query()->create([
                    'actor_id' => $actor->id,
                    'event' => 'enrollment.created',
                    'auditable_type' => Enrollment::class,
                    'auditable_id' => $enrollment->id,
                    'new_values' => $enrollment->only(['course_id', 'user_id', 'period_number', 'status', 'starts_at', 'ends_at']),
                    'occurred_at' => now(),
                    'retention_expires_at' => now()->addMonths(6),
                ]);
            }

            return $enrollment->refresh();
        });
    }
}
