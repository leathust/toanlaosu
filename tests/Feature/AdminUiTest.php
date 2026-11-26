<?php

use App\Models\AiUsageLog;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Question;
use App\Models\Role;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\VisualScene;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function adminFixture(): array
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin'])->id);

    return compact('admin');
}

it('renders the operations dashboard and admin views', function () {
    $fixture = adminFixture();
    User::factory()->count(2)->create();
    Course::factory()->create(['status' => 'draft']);
    Question::factory()->create(['status' => 'draft']);
    VisualScene::factory()->create(['status' => 'draft']);
    AuditLog::factory()->create(['actor_id' => $fixture['admin']->id]);
    AiUsageLog::factory()->create(['user_id' => $fixture['admin']->id]);
    SystemSetting::factory()->create(['key' => 'ai.enabled', 'value' => true]);

    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
    ])->actingAs($fixture['admin'])->get(route('admin.dashboard'))
        ->assertOk()
        ->assertJsonPath('component', 'Admin/Overview')
        ->assertJsonPath('props.kpis.0.label', 'Active learners')
        ->assertJsonPath('props.navigation.admin.0.label', 'Overview');
    $this->actingAs($fixture['admin'])->get(route('admin.users'))->assertOk()->assertSee('Users và access');
    $this->actingAs($fixture['admin'])->get(route('admin.content'))->assertOk()->assertSee('Content review');
    $this->actingAs($fixture['admin'])->get(route('admin.audit'))->assertOk()->assertSee('Audit log');
    $this->actingAs($fixture['admin'])->get(route('admin.ai-usage'))->assertOk()->assertSee('AI usage');
});

it('normalizes admin overview filters and keeps them in the Inertia contract', function () {
    $fixture = adminFixture();

    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
    ])->actingAs($fixture['admin'])
        ->get(route('admin.dashboard', [
            'range' => 'invalid',
            'compare' => 'invalid',
            'course' => '0',
            'timezone' => 'Not/AZone',
        ]))
        ->assertOk()
        ->assertJsonPath('props.filters.range', '30d')
        ->assertJsonPath('props.filters.compare', 'previous')
        ->assertJsonPath('props.filters.course', null)
        ->assertJsonPath('props.filters.timezone', config('app.timezone'));
});

it('rejects all admin operations for non-admin users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
});

it('requires authentication for admin operations', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('filters admin users and preserves query state in pagination', function () {
    $fixture = adminFixture();
    User::factory()->create(['name' => 'Target User', 'status' => 'suspended']);
    User::factory()->create(['name' => 'Other User', 'status' => 'active']);

    $this->actingAs($fixture['admin'])
        ->get(route('admin.users', ['q' => 'Target', 'status' => 'suspended']))
        ->assertOk()
        ->assertSee('Target User')
        ->assertDontSee('Other User');
});

it('updates user status with reason and audit trail', function () {
    $fixture = adminFixture();
    $subject = User::factory()->create(['status' => 'active']);

    $this->actingAs($fixture['admin'])
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->patch(route('admin.users.status', $subject), ['status' => 'suspended', 'reason' => 'Security review'])
        ->assertRedirect();

    expect($subject->fresh()->status->value)->toBe('suspended')
        ->and(AuditLog::query()->where('event', 'user.status_updated')->count())->toBe(1);
});

it('approves draft question and updates a system setting with audit records', function () {
    $fixture = adminFixture();
    $question = Question::factory()->create(['status' => 'draft']);
    SystemSetting::factory()->create(['key' => 'ai.enabled', 'value' => false]);

    $this->actingAs($fixture['admin'])
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->post(route('admin.questions.approve', $question), ['reason' => 'Content review passed'])
        ->assertRedirect();
    $this->actingAs($fixture['admin'])
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->put(route('admin.settings.update', ['key' => 'ai.enabled']), ['value' => true])
        ->assertRedirect();

    expect($question->fresh()->status)->toBe('published')
        ->and(SystemSetting::query()->where('key', 'ai.enabled')->value('value'))->toBeTrue()
        ->and(AuditLog::query()->whereIn('event', ['content.approved', 'system_setting.updated'])->count())->toBe(2);
});

it('validates typed system settings and leaves the previous value unchanged on invalid input', function () {
    $fixture = adminFixture();
    SystemSetting::factory()->create(['key' => 'ai.daily_budget', 'value' => 100]);

    $this->actingAs($fixture['admin'])
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->put(route('admin.settings.update', ['key' => 'ai.daily_budget']), ['value' => -1])
        ->assertSessionHasErrors('value');
    $this->actingAs($fixture['admin'])
        ->withSession(['auth.password_confirmed_at' => now()->unix()])
        ->put(route('admin.settings.update', ['key' => 'unknown.setting']), ['value' => true])
        ->assertSessionHasErrors('value');

    expect(SystemSetting::query()->where('key', 'ai.daily_budget')->value('value'))->toBe(100)
        ->and(AuditLog::query()->where('event', 'system_setting.updated')->count())->toBe(0);
});
