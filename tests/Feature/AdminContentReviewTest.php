<?php

use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\User;
use App\Models\VisualScene;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function contentReviewAdmin(): User
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin'])->id);

    return $admin;
}

it('aggregates a unified queue and opens a version preview', function () {
    $admin = contentReviewAdmin();
    $question = Question::factory()->create(['status' => 'draft']);
    QuestionVersion::factory()->create(['question_id' => $question->id, 'version' => 1, 'stem' => 'Radar review stem']);
    VisualScene::factory()->create(['status' => 'review']);

    $this->actingAs($admin)->get(route('admin.content.review'))->assertOk()->assertSee($question->public_id)->assertSee('Content review queue');
    $this->actingAs($admin)->get(route('admin.content.review.show', ['type' => 'question', 'id' => $question->id]))->assertOk()->assertSee('Radar review stem')->assertSee('Approve / publish');
});

it('filters the review queue by an assigned active admin reviewer', function () {
    $admin = contentReviewAdmin();
    $reviewer = User::factory()->create(['name' => 'Assigned Reviewer']);
    $reviewer->roles()->attach($admin->roles()->first()->id);
    Course::factory()->create(['title' => 'Assigned Course', 'assigned_reviewer_id' => $reviewer->id]);
    $question = Question::factory()->create(['assigned_reviewer_id' => $reviewer->id]);
    VisualScene::factory()->create(['name' => 'Assigned Scene', 'assigned_reviewer_id' => $reviewer->id]);
    Course::factory()->create(['title' => 'Unassigned Course']);

    $response = $this->actingAs($admin)->get(route('admin.content.review', ['reviewer' => $reviewer->id]));

    $response->assertOk()->assertSee('Assigned Course')->assertSee($question->public_id)->assertSee('Assigned Scene')->assertDontSee('Unassigned Course')->assertSee('value="'.$reviewer->id.'" selected', false);
});

it('approves a draft version without mutating the published version contract', function () {
    $admin = contentReviewAdmin();
    $question = Question::factory()->create(['status' => 'draft']);
    $version = QuestionVersion::factory()->create(['question_id' => $question->id, 'version' => 1, 'status' => 'draft']);

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->patch(route('admin.content.review.decision', ['type' => 'question', 'id' => $question->id]), ['decision' => 'approve', 'reason' => 'Reviewed', 'expected_version' => 1])->assertRedirect();

    expect($question->fresh()->status)->toBe('published')->and($question->fresh()->published_version_id)->toBe($version->id)->and($version->fresh()->status)->toBe('published')->and(AuditLog::query()->where('event', 'content.reviewed')->count())->toBe(1);
});

it('rejects stale review submissions and blocks non-admin access', function () {
    $admin = contentReviewAdmin();
    $question = Question::factory()->create();
    QuestionVersion::factory()->create(['question_id' => $question->id, 'version' => 2]);

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->patch(route('admin.content.review.decision', ['type' => 'question', 'id' => $question->id]), ['decision' => 'approve', 'reason' => 'Old review', 'expected_version' => 1])->assertSessionHasErrors('expected_version');
    $this->actingAs(User::factory()->create())->get(route('admin.content.review'))->assertForbidden();
});

it('requires explicit policy confirmation for batch review and limits it to courses', function () {
    $admin = contentReviewAdmin();
    $course = Course::factory()->create(['status' => 'draft']);

    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->post(route('admin.content.review.batch'), ['type' => 'course', 'content_ids' => [$course->id], 'decision' => 'approve', 'reason' => 'Batch course review'])->assertSessionHasErrors('confirm_batch');
    $this->actingAs($admin)->withSession(['auth.password_confirmed_at' => now()->unix()])->post(route('admin.content.review.batch'), ['type' => 'course', 'content_ids' => [$course->id], 'decision' => 'approve', 'reason' => 'Batch course review', 'confirm_batch' => '1'])->assertRedirect();

    expect($course->fresh()->status)->toBe('published');
});
