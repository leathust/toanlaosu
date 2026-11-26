<?php

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

it('lists sessions without exposing raw identifiers and supports password-confirmed revocation', function () {
    $user = User::factory()->create(['password' => 'current-password']);
    $this->actingAs($user)->get(route('account.settings'))->assertOk()->assertSee('Phiên đăng nhập');
    DB::table('sessions')->insert([
        'id' => 'secondary-session',
        'user_id' => $user->id,
        'ip_address' => '192.168.1.42',
        'user_agent' => 'Mozilla/5.0 Chrome/1.0',
        'payload' => 'payload',
        'last_activity' => now()->subMinute()->timestamp,
    ]);

    $key = hash('sha256', 'secondary-session');
    $this->delete(route('account.sessions.revoke', $key))
        ->assertRedirect(route('password.confirm'));
    $this->post(route('password.confirm.store'), ['password' => 'current-password'])
        ->assertRedirect(route('account.settings'));
    $this->delete(route('account.sessions.revoke', $key))->assertRedirect();

    expect(DB::table('sessions')->where('id', 'secondary-session')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('event', 'account.session_revoked')->value('new_values'))->toBe(['sessions_revoked' => 1]);
});

it('rejects cross-user session revocation and expired password confirmation', function () {
    $owner = User::factory()->create(['password' => 'owner-password']);
    $other = User::factory()->create(['password' => 'other-password']);
    DB::table('sessions')->insert([
        'id' => 'owned-by-owner',
        'user_id' => $owner->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => 'payload',
        'last_activity' => now()->timestamp,
    ]);

    $this->actingAs($other)->delete(route('account.sessions.revoke', hash('sha256', 'owned-by-owner')))
        ->assertRedirect(route('password.confirm'));
    $this->post(route('password.confirm.store'), ['password' => 'other-password'])->assertRedirect(route('account.settings'));
    $this->withSession(['auth.password_confirmed_at' => now()->subSeconds(config('auth.password_timeout') + 1)->unix()])
        ->actingAs($other)
        ->delete(route('account.sessions.revoke', hash('sha256', 'owned-by-owner')))
        ->assertRedirect(route('password.confirm'));

    expect(DB::table('sessions')->where('id', 'owned-by-owner')->exists())->toBeTrue();
});

it('revokes all other sessions and uses safe cookie policy defaults', function () {
    $user = User::factory()->create(['password' => 'current-password']);
    $this->actingAs($user)->get(route('account.settings'));
    DB::table('sessions')->insert([
        'id' => 'secondary-one', 'user_id' => $user->id, 'payload' => 'payload', 'last_activity' => now()->timestamp,
    ]);
    DB::table('sessions')->insert([
        'id' => 'secondary-two', 'user_id' => $user->id, 'payload' => 'payload', 'last_activity' => now()->timestamp,
    ]);

    $this->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->delete(route('account.sessions.revoke-others'))
        ->assertRedirect();

    expect(DB::table('sessions')->where('id', 'secondary-one')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', 'secondary-two')->exists())->toBeFalse()
        ->and(config('session.http_only'))->toBeTrue()
        ->and(config('session.same_site'))->toBe('lax')
        ->and(config('session.path'))->toBe('/');
});

it('records login and logout audit events without credential data', function () {
    $user = User::factory()->create(['password' => 'current-password']);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSessionHasErrors('email');
    expect(AuditLog::query()->where('event', 'auth.login_failed')->value('new_values'))->toBe(['result' => 'failed']);

    $this->post(route('login.store'), ['email' => $user->email, 'password' => 'current-password'])->assertRedirect();
    $this->post(route('logout'))->assertRedirect(route('login'));
    expect(AuditLog::query()->where('event', 'auth.login_succeeded')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event', 'auth.logout')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('event', 'auth.login_failed')->first()->new_values)->not->toHaveKey('email');
});
