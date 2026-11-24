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

    $this->actingAs($fixture['admin'])->get(route('admin.dashboard'))->assertOk()->assertSee('Tổng quan hệ thống');
    $this->actingAs($fixture['admin'])->get(route('admin.users'))->assertOk()->assertSee('Users và access');
    $this->actingAs($fixture['admin'])->get(route('admin.content'))->assertOk()->assertSee('Content review');
    $this->actingAs($fixture['admin'])->get(route('admin.audit'))->assertOk()->assertSee('Audit log');
    $this->actingAs($fixture['admin'])->get(route('admin.ai-usage'))->assertOk()->assertSee('AI usage');
});

it('rejects all admin operations for non-admin users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
});

it('requires authentication for admin operations', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});
