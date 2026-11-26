<?php

namespace App\Actions\Admin;

use App\Models\User;
use App\UserStatus;
use Illuminate\Validation\ValidationException;

class BulkUpdateUserStatus
{
    /** @param list<int> $userIds @return array{updated: int, failed: array<int, string>} */
    public function execute(User $actor, array $userIds, UserStatus $status, string $reason): array
    {
        $updated = 0;
        $failed = [];
        $action = app(UpdateUserStatus::class);
        foreach ($userIds as $userId) {
            $subject = User::query()->find($userId);
            if ($subject === null) {
                $failed[$userId] = 'User not found.';

                continue;
            }
            try {
                $action->execute($actor, $subject, $status, $reason);
                $updated++;
            } catch (ValidationException $exception) {
                $failed[$userId] = (string) $exception->getMessage();
            }
        }

        return compact('updated', 'failed');
    }
}
