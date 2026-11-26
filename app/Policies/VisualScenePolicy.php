<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisualScene;

class VisualScenePolicy
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
    public function view(User $user, VisualScene $visualScene): bool
    {
        return $user->hasRole('admin') || $visualScene->created_by === $user->id;
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
    public function update(User $user, VisualScene $visualScene): bool
    {
        return $this->view($user, $visualScene);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, VisualScene $visualScene): bool
    {
        return $this->update($user, $visualScene);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, VisualScene $visualScene): bool
    {
        return $this->update($user, $visualScene);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, VisualScene $visualScene): bool
    {
        return $user->hasRole('admin');
    }
}
