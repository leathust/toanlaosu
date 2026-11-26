<?php

use App\Models\AiFeedback;
use App\Models\AiUsageLog;
use App\Models\AuditLog;
use App\Models\ChatMessage;
use App\Models\ChatMessageSource;
use App\Models\ChatThread;
use App\Models\Course;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;

uses(LazilyRefreshDatabase::class);

function adm009Admin(): User
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin'])->id);

    return $admin;
}

function adm009InertiaGet(User $admin, array $query = []): TestResponse
{
    return test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
    ])->actingAs($admin)->get(route('admin.ai', $query));
}

it('calculates precise usage metrics and keeps provider/model breakdown isolated', function () {
    $this->travelTo('2026-01-15 12:00:00');
    Cache::flush();
    $admin = adm009Admin();
    $course = Course::factory()->create();
    $thread = ChatThread::factory()->create(['course_id' => $course->id]);
    $message = ChatMessage::factory()->create(['chat_thread_id' => $thread->id, 'created_at' => now()->subHour()]);
    AiUsageLog::factory()->create(['chat_message_id' => $message->id, 'provider' => 'openai', 'model' => 'gpt-4o-mini', 'status' => 'succeeded', 'prompt_tokens' => 100, 'completion_tokens' => 200, 'cost' => '0.123456', 'latency_ms' => 100, 'occurred_at' => now()->subHour()]);
    AiUsageLog::factory()->create(['chat_message_id' => $message->id, 'provider' => 'openai', 'model' => 'gpt-4o-mini', 'status' => 'succeeded', 'prompt_tokens' => 50, 'completion_tokens' => 50, 'cost' => '0.000001', 'latency_ms' => 200, 'occurred_at' => now()->subMinutes(30)]);
    AiUsageLog::factory()->create(['chat_message_id' => $message->id, 'provider' => 'azure', 'model' => 'small', 'status' => 'failed', 'prompt_tokens' => 20, 'completion_tokens' => 30, 'cost' => '0.000010', 'latency_ms' => 300, 'occurred_at' => now()->subMinutes(20), 'error_code' => 'timeout']);

    $response = adm009InertiaGet($admin, ['range' => '7d', 'course' => $course->id, 'timezone' => 'UTC']);
    $response->assertOk()->assertJsonPath('component', 'Admin/AI/Operations');
    $props = $response->json('props');

    expect($props['summary']['requests'])->toBe(3)
        ->and($props['summary']['tokens'])->toBe(450)
        ->and($props['summary']['cost'])->toBe(0.123467)
        ->and($props['summary']['successRate'])->toBe(66.67)
        ->and($props['summary']['latencyP50'])->toBe(200)
        ->and($props['summary']['latencyP95'])->toBe(300)
        ->and($props['breakdown'][0]['provider'])->toBe('openai')
        ->and($props['breakdown'][0]['requests'])->toBe(2);

    $filtered = adm009InertiaGet($admin, ['provider' => 'azure', 'model' => 'small', 'range' => '7d'])->json('props');
    expect($filtered['summary']['requests'])->toBe(1)->and($filtered['summary']['failed'])->toBe(1);
});

it('redacts failure details and distinguishes no traffic from provider outage', function () {
    $this->travelTo('2026-01-15 12:00:00');
    Cache::flush();
    $admin = adm009Admin();
    $message = ChatMessage::factory()->create(['content' => 'secret prompt sk-live-should-never-leak']);
    AiUsageLog::factory()->create(['chat_message_id' => $message->id, 'status' => 'failed', 'error_code' => 'provider_secret', 'occurred_at' => now()->subHour()]);

    $response = adm009InertiaGet($admin, ['range' => '7d']);
    expect($response->getContent())->toContain('Provider request failed.')->not->toContain('sk-live-should-never-leak');

    AiUsageLog::query()->delete();
    Cache::flush();
    Cache::put('ai:circuit:open', true, now()->addMinute());
    $outage = adm009InertiaGet($admin, ['range' => '7d'])->json('props');
    expect($outage['dataState'])->toBe('outage');

    Cache::forget('ai:circuit:open');
    Cache::flush();
    $empty = adm009InertiaGet($admin, ['range' => '7d'])->json('props');
    expect($empty['dataState'])->toBe('no_traffic');
});

it('summarizes feedback and citation quality with an explicit sample size', function () {
    $this->travelTo('2026-01-15 12:00:00');
    Cache::flush();
    $admin = adm009Admin();
    $thread = ChatThread::factory()->create();
    $message = ChatMessage::factory()->create(['chat_thread_id' => $thread->id, 'created_at' => now()->subHour()]);
    ChatMessageSource::factory()->create(['chat_message_id' => $message->id]);
    AiFeedback::factory()->create(['chat_message_id' => $message->id, 'user_id' => $admin->id, 'rating' => 5, 'created_at' => now()->subHour()]);

    $quality = adm009InertiaGet($admin, ['range' => '7d'])->json('props.quality');
    expect($quality['sampleSize'])->toBe(1)
        ->and($quality['averageRating'])->toBe(5)
        ->and($quality['positiveRate'])->toBe(100)
        ->and($quality['citationCoverage'])->toBe(100)
        ->and($quality['citationSampleSize'])->toBe(1);
});

it('validates budget boundaries and records password-confirmed changes in audit log', function () {
    $admin = adm009Admin();
    SystemSetting::factory()->create(['key' => 'ai.daily_budget', 'value' => 100]);

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->put(route('admin.settings.update', ['key' => 'ai.daily_budget']), ['value' => 250])
        ->assertRedirect();

    expect(SystemSetting::query()->where('key', 'ai.daily_budget')->value('value'))->toBe(250)
        ->and(AuditLog::query()->where('event', 'system_setting.updated')->count())->toBe(1);

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->put(route('admin.settings.update', ['key' => 'ai.daily_budget']), ['value' => -1])
        ->assertSessionHasErrors('value');

    $this->actingAs(User::factory()->create())->get(route('admin.ai'))->assertForbidden();
});
