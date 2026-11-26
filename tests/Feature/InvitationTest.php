<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

function invitationAdmin(): User
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin']));

    return $admin;
}

it('allows an admin to create an invite without storing the raw token', function () {
    Notification::fake();
    $admin = invitationAdmin();
    Role::factory()->create(['name' => 'student']);

    $this->actingAs($admin)->post(route('admin.invites.store'), [
        'name' => 'New Student',
        'email' => 'new.student@example.test',
        'role' => 'student',
    ])->assertRedirect()->assertSessionHas('status');

    $invitation = UserInvitation::query()->firstOrFail();
    expect($invitation->email)->toBe('new.student@example.test')
        ->and(strlen($invitation->token_hash))->toBe(64)
        ->and(AuditLog::query()->where('event', 'invite.created')->value('new_values'))->not->toContain($invitation->token_hash);
    Notification::assertSentOnDemand(UserInvitationNotification::class);
});

it('rejects invite creation for non-admin users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('admin.invites.store'), [
        'name' => 'Blocked',
        'email' => 'blocked@example.test',
        'role' => 'student',
    ])->assertForbidden();
});

it('accepts a valid invite and preserves the invited role', function () {
    $admin = invitationAdmin();
    $role = Role::factory()->create(['name' => 'teacher', 'display_name' => 'Giáo viên']);
    $token = 'raw-invite-token';
    $invitation = UserInvitation::factory()->create([
        'name' => 'Invited Teacher',
        'email' => 'invited.teacher@example.test',
        'role_id' => $role->id,
        'invited_by' => $admin->id,
        'token_hash' => hash('sha256', $token),
    ]);

    $this->get(route('invite.accept', ['token' => $token]))
        ->assertOk()
        ->assertSee('Thiết lập mật khẩu của bạn');

    $this->post(route('invite.store', ['token' => $token]), [
        'password' => 'invite-secure-password',
        'password_confirmation' => 'invite-secure-password',
    ])->assertRedirect(route('login'));

    $user = User::query()->where('email', $invitation->email)->firstOrFail();
    expect(Hash::check('invite-secure-password', $user->password))->toBeTrue()
        ->and($user->roles()->where('name', 'teacher')->exists())->toBeTrue()
        ->and($invitation->fresh()->accepted_at)->not->toBeNull()
        ->and(AuditLog::query()->where('event', 'invite.accepted')->exists())->toBeTrue();
});

it('revokes old invites when an admin resends and supports manual revoke', function () {
    Notification::fake();
    $admin = invitationAdmin();
    $role = Role::factory()->create(['name' => 'student']);
    $old = UserInvitation::factory()->create(['role_id' => $role->id, 'invited_by' => $admin->id]);

    $this->actingAs($admin)->post(route('admin.invites.resend', $old))
        ->assertRedirect()->assertSessionHas('status');

    $old->refresh();
    $new = UserInvitation::query()->where('email', $old->email)->whereNull('revoked_at')->firstOrFail();
    expect($old->revoked_at)->not->toBeNull()
        ->and($new->id)->not->toBe($old->id)
        ->and($new->token_hash)->not->toBe($old->token_hash);

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->delete(route('admin.invites.revoke', $new))
        ->assertRedirect()->assertSessionHas('status');
    expect($new->fresh()->revoked_at)->not->toBeNull();
});

it('rejects expired and reused invite tokens', function () {
    $role = Role::factory()->create(['name' => 'student']);
    $expiredToken = 'expired-invite-token';
    UserInvitation::factory()->create([
        'role_id' => $role->id,
        'token_hash' => hash('sha256', $expiredToken),
        'expires_at' => now()->subMinute(),
    ]);

    $this->get(route('invite.accept', ['token' => $expiredToken]))
        ->assertOk()
        ->assertSee('Liên kết không còn hiệu lực');

    $usedToken = 'used-invite-token';
    $invitation = UserInvitation::factory()->create([
        'role_id' => $role->id,
        'token_hash' => hash('sha256', $usedToken),
        'accepted_at' => now(),
    ]);

    $this->post(route('invite.store', ['token' => $usedToken]), [
        'password' => 'invite-secure-password',
        'password_confirmation' => 'invite-secure-password',
    ])->assertSessionHasErrors('password');
    expect(User::query()->where('email', $invitation->email)->exists())->toBeFalse();
});
