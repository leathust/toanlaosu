<?php

use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('keeps audit records append-only with six-month retention and nullable actors', function () {
    $actor = User::factory()->create();
    $audit = AuditLog::factory()->for($actor, 'actor')->create([
        'retention_expires_at' => now()->addMonths(6),
        'old_values' => ['status' => 'draft'],
        'new_values' => ['status' => 'published'],
    ]);

    $actor->delete();

    expect($audit->fresh()->actor_id)->toBeNull()
        ->and($audit->fresh()->old_values)->toBe(['status' => 'draft'])
        ->and($audit->fresh()->retention_expires_at->isFuture())->toBeTrue();
});

it('seeds system retention and feature settings idempotently', function () {
    $this->seed();
    $this->seed();

    expect(SystemSetting::query()->count())->toBe(3)
        ->and(SystemSetting::query()->where('key', 'audit_retention_months')->value('value'))->toBe(6)
        ->and(SystemSetting::query()->where('key', 'exam_retention_years')->value('value'))->toBe(1)
        ->and(SystemSetting::query()->where('key', 'ai_vector_search_enabled')->value('value'))->toBeFalse();
});

it('prevents duplicate system setting keys', function () {
    SystemSetting::factory()->create(['key' => 'audit_retention_months']);

    expect(fn () => SystemSetting::factory()->create(['key' => 'audit_retention_months']))
        ->toThrow(QueryException::class);
});
