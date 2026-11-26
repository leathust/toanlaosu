<?php

namespace App\Policies;

use App\Models\Exam;
use App\Models\User;

class ExamPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole('teacher', 'admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Exam $exam): bool
    {
        return $user->hasRole('admin') || ($exam->course_id !== null && $user->teachingCourses()->whereKey($exam->course_id)->wherePivotNull('revoked_at')->exists());
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole('teacher', 'admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Exam $exam): bool
    {
        return $this->view($user, $exam);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Exam $exam): bool
    {
        return $this->update($user, $exam);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Exam $exam): bool
    {
        return $user->hasRole('admin');
    }
}
