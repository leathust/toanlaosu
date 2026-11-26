<?php

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

function adminAccessFixture(): User
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin'])->id);

    return $admin;
}

it('filters users and exposes a redacted detail page', function () {
    $admin = adminAccessFixture();
    $target = User::factory()->create(['name' => 'Verified Target', 'email_verified_at' => now()]);
    User::factory()->create(['name' => 'Hidden Target', 'email_verified_at' => null]);

    $this->actingAs($admin)->get(route('admin.users', ['q' => 'Verified', 'verified' => 1]))
        ->assertOk()->assertSee('Verified Target')->assertDontSee('Hidden Target');
    $this->actingAs($admin)->get(route('admin.users.show', $target))
        ->assertOk()->assertSee('Redacted audit timeline')->assertDontSee('password')->assertDontSee('remember_token');
});

it('prevents removing the last admin role', function () {
    $admin = adminAccessFixture();
    $student = User::factory()->create();
    $student->roles()->attach(Role::factory()->create(['name' => 'student'])->id);

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->patch(route('admin.users.roles', $admin), ['roles' => ['student']])->assertSessionHasErrors('roles');
});

it('revokes all target sessions with a redacted audit event', function () {
    $admin = adminAccessFixture();
    $target = User::factory()->create();
    DB::table('sessions')->insert(['id' => 'target-session', 'user_id' => $target->id, 'ip_address' => '127.0.0.1', 'user_agent' => 'test', 'payload' => 'opaque', 'last_activity' => now()->unix()]);

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('admin.users.sessions.revoke', $target))->assertRedirect();

    expect(DB::table('sessions')->where('id', 'target-session')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('event', 'admin.user_sessions_revoked')->count())->toBe(1);
});

it('exports only allow-listed user fields', function () {
    $admin = adminAccessFixture();
    User::factory()->create(['name' => 'Export Target', 'email' => 'private@example.test']);

    $response = $this->actingAs($admin)->get(route('admin.users.export'));
    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->getContent())->toContain('Export Target')->not->toContain('private@example.test')
        ->and(AuditLog::query()->where('event', 'admin.users_exported')->count())->toBe(1);
});

it('supports pending invite filter and partial bulk status results', function () {
    $admin = adminAccessFixture();
    $pending = User::factory()->create(['email' => 'pending@example.test']);
    UserInvitation::factory()->create(['email' => $pending->email]);
    $other = User::factory()->create();

    $this->actingAs($admin)->get(route('admin.users', ['invite_state' => 'pending']))->assertOk()->assertSee('pending@example.test');
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->post(route('admin.users.bulk-status'), [
        'user_ids' => [$pending->id, $other->id], 'status' => 'suspended', 'reason' => 'Security review',
    ])->assertRedirect();

    expect($pending->fresh()->status->value)->toBe('suspended')->and($other->fresh()->status->value)->toBe('suspended');
});
