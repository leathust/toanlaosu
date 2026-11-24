<?php

use App\Models\ExamVersion;
use App\Models\ExportJob;
use App\Models\LatexTemplate;
use App\Models\LatexTemplateVersion;
use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('keeps versioned latex templates and links export jobs to exact versions', function () {
    $template = LatexTemplate::factory()->create();
    $version = LatexTemplateVersion::factory()->for($template, 'template')->create([
        'version' => 1,
        'status' => 'published',
    ]);
    $template->update(['active_version_id' => $version->id]);
    $examVersion = ExamVersion::factory()->create();
    $job = ExportJob::factory()->for($examVersion)->for($version, 'latexTemplateVersion')->create([
        'idempotency_key' => 'export-key-1',
        'status' => 'queued',
    ]);

    expect($template->fresh()->activeVersion->is($version))->toBeTrue()
        ->and($job->fresh()->latexTemplateVersion->is($version))->toBeTrue()
        ->and($job->fresh()->examVersion->is($examVersion))->toBeTrue();
});

it('prevents duplicate export idempotency keys', function () {
    $key = 'same-export-key';

    ExportJob::factory()->create(['idempotency_key' => $key]);

    expect(fn () => ExportJob::factory()->create(['idempotency_key' => $key]))
        ->toThrow(QueryException::class);
});

it('deduplicates webhook events by provider event identity', function () {
    WebhookEvent::factory()->create([
        'provider' => 'video_provider',
        'provider_event_id' => 'evt-123',
        'payload' => ['status' => 'ready'],
    ]);

    expect(fn () => WebhookEvent::factory()->create([
        'provider' => 'video_provider',
        'provider_event_id' => 'evt-123',
    ]))->toThrow(QueryException::class);

    expect(WebhookEvent::query()->where('provider_event_id', 'evt-123')->count())->toBe(1);
});
