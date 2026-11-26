<?php

namespace App\Policies;

use App\Models\Question;
use App\Models\User;

class QuestionPolicy
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
    public function view(User $user, Question $question): bool
    {
        return $user->hasRole('admin') || $question->owner_id === $user->id;
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
    public function update(User $user, Question $question): bool
    {
        return $this->view($user, $question);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Question $question): bool
    {
        return $this->update($user, $question);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Question $question): bool
    {
        return $this->update($user, $question);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Question $question): bool
    {
        return $user->hasRole('admin');
    }
}
