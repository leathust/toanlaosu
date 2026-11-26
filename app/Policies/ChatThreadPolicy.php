<?php

namespace App\Policies;

use App\Models\ChatThread;
use App\Models\User;

class ChatThreadPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ChatThread $chatThread): bool
    {
        return $chatThread->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ChatThread $chatThread): bool
    {
        return $this->view($user, $chatThread);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ChatThread $chatThread): bool
    {
        return $this->view($user, $chatThread);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ChatThread $chatThread): bool
    {
        return $this->view($user, $chatThread);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ChatThread $chatThread): bool
    {
        return $this->view($user, $chatThread);
    }
}
