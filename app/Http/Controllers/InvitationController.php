<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptInviteRequest;
use App\Http\Requests\CreateInviteRequest;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function store(CreateInviteRequest $request): RedirectResponse
    {
        $invitation = $this->createInvitation($request->user(), $request->validated(), 'invite.created');

        return back()->with('status', 'Lời mời đã được tạo và gửi tới email người nhận.');
    }

    public function resend(UserInvitation $invitation, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);
        abort_if($invitation->accepted_at !== null, 422, 'Lời mời đã được sử dụng.');

        $data = [
            'name' => $invitation->name,
            'email' => $invitation->email,
            'role' => $invitation->role()->value('name'),
        ];
        $invitation->forceFill(['revoked_at' => now()])->save();
        $this->createInvitation($request->user(), $data, 'invite.resent');

        return back()->with('status', 'Đã tạo và gửi lại lời mời.');
    }

    public function revoke(UserInvitation $invitation, Request $request): RedirectResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);
        if ($invitation->isUsable()) {
            $invitation->forceFill(['revoked_at' => now()])->save();
            $this->audit($request->user(), 'invite.revoked', $invitation, ['result' => 'revoked']);
        }

        return back()->with('status', 'Lời mời đã được thu hồi.');
    }

    public function show(string $token): View
    {
        $invitation = $this->findInvitation($token);

        if (! $invitation?->isUsable()) {
            return view('auth.invite-invalid');
        }

        return view('auth.accept-invite', compact('invitation', 'token'));
    }

    public function accept(AcceptInviteRequest $request, string $token): RedirectResponse
    {
        $invitation = $this->findInvitation($token);

        if (! $invitation?->isUsable()) {
            return redirect()->route('invite.accept', ['token' => $token])
                ->withErrors(['password' => 'Lời mời không hợp lệ hoặc đã hết hạn.']);
        }

        $user = DB::transaction(function () use ($invitation, $request): User {
            $lockedInvitation = UserInvitation::query()->lockForUpdate()->findOrFail($invitation->getKey());
            abort_unless($lockedInvitation->isUsable(), 410, 'Lời mời không hợp lệ hoặc đã hết hạn.');
            abort_if(User::query()->where('email', $lockedInvitation->email)->exists(), 422, 'Email này đã có tài khoản.');

            $user = User::query()->create([
                'name' => $lockedInvitation->name,
                'email' => $lockedInvitation->email,
                'email_verified_at' => now(),
                'status' => 'active',
                'password' => $request->validated('password'),
            ]);
            $user->roles()->attach($lockedInvitation->role_id, ['assigned_at' => now()]);
            $lockedInvitation->forceFill(['accepted_at' => now()])->save();
            $this->audit($user, 'invite.accepted', $lockedInvitation, ['result' => 'accepted', 'role_id' => $lockedInvitation->role_id]);

            return $user;
        });

        return redirect()->route('login')->with('status', 'Tài khoản đã được kích hoạt. Hãy đăng nhập bằng mật khẩu mới.');
    }

    /**
     * @param  array{name: string, email: string, role: string}  $data
     */
    private function createInvitation(User $actor, array $data, string $event): UserInvitation
    {
        $role = Role::query()->where('name', $data['role'])->firstOrFail();
        $token = Str::random(64);

        $invitation = UserInvitation::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $role->getKey(),
            'invited_by' => $actor->getKey(),
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(config('auth.invites.expire', 1440)),
        ]);
        $invitation->setRelation('role', $role);
        Notification::route('mail', $invitation->email)->notify(new UserInvitationNotification($invitation, $token));
        $this->audit($actor, $event, $invitation, ['email' => $invitation->email, 'role_id' => $role->getKey()]);

        return $invitation;
    }

    private function findInvitation(string $token): ?UserInvitation
    {
        return UserInvitation::query()->with('role')->where('token_hash', hash('sha256', $token))->first();
    }

    /**
     * @param  array<string, mixed>  $newValues
     */
    private function audit(User $actor, string $event, UserInvitation $invitation, array $newValues): void
    {
        AuditLog::query()->create([
            'actor_id' => $actor->getKey(),
            'event' => $event,
            'auditable_type' => UserInvitation::class,
            'auditable_id' => $invitation->getKey(),
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'occurred_at' => now(),
            'retention_expires_at' => now()->addMonths(6),
        ]);
    }
}
