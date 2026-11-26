<?php

use App\Models\AiUsageLog;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Admin\AdminActionCenterQuery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

function actionCenterAdmin(): User
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin'])->id);

    return $admin;
}

it('aggregates stable operational alerts and redacts sensitive payloads', function () {
    $admin = actionCenterAdmin();
    WebhookEvent::factory()->create(['status' => 'failed', 'failed_at' => now()->subHour(), 'payload' => ['secret' => 'do-not-return']]);
    AiUsageLog::factory()->create(['status' => 'failed', 'user_id' => $admin->id, 'error_code' => 'provider_secret']);
    DB::table('failed_jobs')->insert(['uuid' => fake()->uuid(), 'connection' => 'sync', 'queue' => 'critical', 'payload' => json_encode(['secret' => 'do-not-return']), 'exception' => 'private exception do-not-return', 'failed_at' => now()]);

    $response = $this->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json'))])
        ->actingAs($admin)->get(route('admin.action-center'));

    $response->assertOk()->assertJsonPath('component', 'Admin/ActionCenter');
    $body = $response->getContent();
    expect($body)->toContain('Failed queue job')->toContain('Webhook delivery failed')->toContain('AI provider failure')->not->toContain('do-not-return')->not->toContain('private exception');
    expect(app(AdminActionCenterQuery::class)->countOpen())->toBeGreaterThanOrEqual(3);
});

it('denies action center to non-admin users', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.action-center'))->assertForbidden();
});

it('acknowledges and resolves idempotently with an audit record', function () {
    $admin = actionCenterAdmin();
    $fingerprint = hash('sha256', 'webhook:42');
    $payload = ['status' => 'acknowledged', 'source' => 'webhook', 'source_id' => '42'];

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->patch(route('admin.action-center.state', $fingerprint), $payload)->assertRedirect();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->patch(route('admin.action-center.state', $fingerprint), $payload)->assertRedirect();

    expect(DB::table('admin_alert_states')->where('fingerprint', $fingerprint)->value('status'))->toBe('acknowledged')
        ->and(AuditLog::query()->where('event', 'admin.alert_acknowledged')->count())->toBe(1);
});

it('filters alerts by source and severity', function () {
    $admin = actionCenterAdmin();
    AiUsageLog::factory()->create(['status' => 'failed', 'user_id' => $admin->id]);

    $this->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json'))])
        ->actingAs($admin)->get(route('admin.action-center', ['source' => 'ai_provider', 'severity' => 'warning']))
        ->assertOk()->assertJsonPath('props.alerts.0.source', 'ai_provider')->assertJsonPath('props.alerts.0.severity', 'warning');
});
