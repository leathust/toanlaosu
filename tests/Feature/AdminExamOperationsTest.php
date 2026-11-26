<?php

use App\Models\AuditLog;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamEvent;
use App\Models\ExamVersion;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function examOperationsAdmin(): User
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin'])->id);

    return $admin;
}

it('shows server-time exam statuses, attempt incidents and redacted event signals', function () {
    $admin = examOperationsAdmin();
    $upcoming = Exam::factory()->create(['status' => 'published', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(2), 'title' => 'Upcoming exam']);
    Exam::factory()->create(['status' => 'published', 'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'title' => 'Live exam']);
    Exam::factory()->create(['status' => 'published', 'starts_at' => now()->subHours(3), 'ends_at' => now()->subHour(), 'title' => 'Ended exam']);
    $version = ExamVersion::factory()->for($upcoming)->create(['status' => 'published']);
    $overdue = ExamAttempt::factory()->for($version)->create(['status' => 'in_progress', 'deadline_at' => now()->subMinute()]);
    $stuck = ExamAttempt::factory()->for($version)->create(['status' => 'in_progress', 'started_at' => now()->subMinutes(30), 'deadline_at' => now()->addHour()]);
    ExamEvent::factory()->for($stuck, 'examAttempt')->create(['event_type' => 'answer_saved', 'occurred_at' => now()->subMinutes(20), 'ip_address' => '10.0.0.1', 'user_agent' => 'secret-agent']);
    ExamEvent::factory()->for($overdue, 'examAttempt')->create(['event_type' => 'save_conflict', 'metadata' => ['reason' => 'stale', 'secret' => 'do-not-return']]);
    ExamEvent::factory()->for($overdue, 'examAttempt')->create(['event_type' => 'reconnect']);
    ExamEvent::factory()->for($overdue, 'examAttempt')->create(['event_type' => 'submit_failed']);

    $headers = ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json'))];
    $response = $this->actingAs($admin)->withHeaders($headers)->get(route('admin.exams', ['issue' => 'overdue']));

    $response->assertOk()->assertJsonPath('component', 'Admin/Exams/Index')->assertJsonPath('props.examStatuses.upcoming', 1)->assertJsonPath('props.examStatuses.live', 1)->assertJsonPath('props.examStatuses.ended', 1)->assertJsonPath('props.issues.save_conflicts', 1)->assertJsonPath('props.issues.reconnects', 1)->assertJsonPath('props.issues.submit_failures', 1)->assertJsonPath('props.attempts.data.0.id', $overdue->id)->assertJsonPath('props.attempts.data.0.issue', 'overdue');
    expect($response->json('props.serverNow'))->toBeString();
});

it('shows a redacted attempt timeline and exposes AI-disabled policy', function () {
    $admin = examOperationsAdmin();
    $version = ExamVersion::factory()->create(['status' => 'published']);
    $attempt = ExamAttempt::factory()->for($version)->create();
    ExamEvent::factory()->for($attempt, 'examAttempt')->create(['event_type' => 'answer_saved', 'ip_address' => '192.0.2.10', 'user_agent' => 'private-agent', 'metadata' => ['question_id' => 4, 'secret' => 'redact']]);
    $headers = ['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest', 'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json'))];

    $response = $this->actingAs($admin)->withHeaders($headers)->get(route('admin.exams.attempts.show', $attempt));

    $response->assertOk()->assertJsonPath('component', 'Admin/Exams/Show')->assertJsonPath('props.attempt.id', $attempt->id)->assertJsonPath('props.aiPolicy.state', 'disabled')->assertJsonPath('props.events.0.metadata.question_id', 4);
    expect($response->json('props.events.0.metadata'))->not->toHaveKey('secret')->and($response->json('props.events.0'))->not->toHaveKey('ip_address')->and($response->json('props.events.0'))->not->toHaveKey('user_agent');
});

it('expires an overdue attempt idempotently without mutating answers', function () {
    $admin = examOperationsAdmin();
    $version = ExamVersion::factory()->create(['status' => 'published']);
    $attempt = ExamAttempt::factory()->for($version)->create(['status' => 'in_progress', 'deadline_at' => now()->subMinute(), 'save_version' => 7]);
    $payload = ['action' => 'expire', 'reason' => 'Deadline exceeded; runbook reviewed.', 'idempotency_key' => 'exam-recovery-'.$attempt->id];

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->post(route('admin.exams.attempts.recover', $attempt), $payload)->assertRedirect(route('admin.exams.attempts.show', $attempt));
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->post(route('admin.exams.attempts.recover', $attempt), $payload)->assertRedirect();

    expect($attempt->fresh()->status)->toBe('expired')->and($attempt->fresh()->save_version)->toBe(7)->and(AuditLog::query()->where('event', 'admin.exam_recovery')->count())->toBe(1);
});

it('rejects unsafe recovery and non-admin access', function () {
    $admin = examOperationsAdmin();
    $version = ExamVersion::factory()->create(['status' => 'published']);
    $attempt = ExamAttempt::factory()->for($version)->create(['deadline_at' => now()->addHour()]);

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->post(route('admin.exams.attempts.recover', $attempt), ['action' => 'expire', 'reason' => 'Too early', 'idempotency_key' => 'future-'.$attempt->id])->assertSessionHasErrors('attempt');
    $this->actingAs(User::factory()->create())->get(route('admin.exams'))->assertForbidden();
    $this->app['auth']->forgetGuards();
    $this->get(route('admin.exams'))->assertRedirect(route('login'));
});
