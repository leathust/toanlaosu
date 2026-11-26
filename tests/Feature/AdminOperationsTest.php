<?php

use App\Jobs\Admin\GenerateAuditExport;
use App\Models\AdminAuditExport;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Admin\AdminOperationsQuery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

uses(LazilyRefreshDatabase::class);

function adm010Admin(): User
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin'])->id);

    return $admin;
}

function adm010Inertia(User $admin, array $query = []): TestResponse
{
    return test()->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json'))])->actingAs($admin)->get(route('admin.operations', $query));
}

it('filters audit records with stable ordering and safe redacted detail', function () {
    $this->travelTo('2026-01-15 12:00:00');
    Cache::flush();
    $admin = adm010Admin();
    $older = AuditLog::factory()->create(['actor_id' => $admin->id, 'event' => 'content.reviewed', 'occurred_at' => now()->subHour(), 'new_values' => ['token' => 'secret-token', 'note' => '<script>alert(1)</script>']]);
    $newer = AuditLog::factory()->create(['actor_id' => $admin->id, 'event' => 'auth.login_failed', 'occurred_at' => now()->subMinutes(10)]);

    $response = adm010Inertia($admin, ['event' => 'login_failed', 'actor_id' => $admin->id, 'range' => '7d']);
    $response->assertOk()->assertJsonPath('component', 'Admin/System/Operations')->assertJsonPath('props.audits.data.0.id', $newer->id)->assertJsonPath('props.audits.meta.total', 1);

    $detail = adm010Inertia($admin, ['range' => '7d']);
    expect($detail->getContent())->not->toContain('secret-token');
    $detailResponse = $this->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json'))])->actingAs($admin)->get(route('admin.operations.audit.show', $older));
    $detailResponse->assertOk()->assertJsonPath('props.audit.newValues.token', '[REDACTED]')->assertJsonPath('props.audit.newValues.note', '<script>alert(1)</script>');
});

it('exposes queue, webhook, scheduler, retention and provider health with policy limits', function () {
    $this->travelTo('2026-01-15 12:00:00');
    Cache::flush();
    $admin = adm010Admin();
    Cache::put('ops:health:last_check', now()->toIso8601String());
    Cache::put('ops:retention:last_run', now()->toIso8601String());
    Cache::put('ai:circuit:open', true);
    WebhookEvent::factory()->create(['status' => 'failed', 'failed_at' => now()->subHour()]);
    SystemSetting::factory()->create(['key' => 'audit_retention_months', 'value' => 6]);
    SystemSetting::factory()->create(['key' => 'exam_retention_years', 'value' => 1]);

    $props = adm010Inertia($admin, ['range' => '7d'])->json('props');
    expect($props['health']['status'])->toBe('attention')
        ->and($props['health']['webhooks']['failedLast7d'])->toBe(1)
        ->and($props['health']['scheduler']['status'])->toBe('healthy')
        ->and($props['health']['retentionPolicy']['auditMonths'])->toBe(6)
        ->and($props['health']['retentionPolicy']['examYears'])->toBe(1)
        ->and($props['health']['provider']['status'])->toBe('open');
});

it('retries a failed webhook idempotently and requires a reason', function () {
    $admin = adm010Admin();
    $webhook = WebhookEvent::factory()->create(['status' => 'failed', 'failed_at' => now(), 'error_message' => 'provider failed']);
    $fingerprint = hash('sha256', 'webhook:'.$webhook->id);
    $payload = ['source' => 'webhook', 'source_id' => (string) $webhook->id, 'action' => 'retry', 'reason' => 'Provider recovered', 'idempotency_key' => 'retry-webhook-once'];

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->post(route('admin.operations.health.action', $fingerprint), $payload)->assertRedirect();
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->post(route('admin.operations.health.action', $fingerprint), $payload)->assertRedirect();

    expect($webhook->fresh()->status)->toBe('received')->and(AuditLog::query()->where('event', 'admin.health_retried')->count())->toBe(1);

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->post(route('admin.operations.health.action', $fingerprint), [...$payload, 'reason' => ''])->assertSessionHasErrors('reason');
});

it('queues capped audit exports and protects download ownership and expiry', function () {
    $admin = adm010Admin();
    $otherAdmin = adm010Admin();
    AuditLog::factory()->create(['actor_id' => $admin->id]);
    Queue::fake();

    $response = $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->post(route('admin.operations.audit.export'), ['range' => '7d', 'idempotency_key' => 'audit-export-once']);
    $response->assertRedirect();
    $export = AdminAuditExport::query()->firstOrFail();
    Queue::assertPushed(GenerateAuditExport::class, 1);
    expect(AuditLog::query()->where('event', 'admin.audit_export_requested')->count())->toBe(1);

    Storage::fake('local');
    app(GenerateAuditExport::class, ['exportId' => $export->id])->handle(app(AdminOperationsQuery::class));
    $export->refresh();
    expect($export->status)->toBe('completed')->and($export->row_count)->toBeGreaterThanOrEqual(1);
    $this->actingAs($admin)->get(route('admin.operations.audit.export.download', $export->public_id))->assertOk();
    $this->actingAs($otherAdmin)->get(route('admin.operations.audit.export.download', $export->public_id))->assertNotFound();
    $export->update(['expires_at' => now()->subSecond()]);
    $this->actingAs($admin)->get(route('admin.operations.audit.export.download', $export->public_id))->assertNotFound();
});

it('rejects operations for non-admin users and preserves legacy audit route', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.operations'))->assertForbidden();
});
