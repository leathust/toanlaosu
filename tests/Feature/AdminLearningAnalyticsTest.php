<?php

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonBlock;
use App\Models\LessonBlockProgress;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserMastery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function learningAnalyticsAdmin(): User
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin'])->id);

    return $admin;
}

it('renders course funnel, question diagnostics and mastery analytics', function () {
    $admin = learningAnalyticsAdmin();
    $learner = User::factory()->create();
    $course = Course::factory()->create(['title' => 'Radar course']);
    Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $learner->id]);
    $block = LessonBlock::factory()->for($course->units()->create(['slug' => 'unit-a', 'title' => 'Unit A', 'status' => 'published', 'sort_order' => 1])->lessons()->create(['slug' => 'lesson-a', 'title' => 'Lesson A', 'status' => 'published', 'sort_order' => 1]), 'lesson')->create(['sort_order' => 1]);
    LessonBlockProgress::factory()->create(['user_id' => $learner->id, 'lesson_block_id' => $block->id, 'status' => 'completed', 'started_at' => now()->subDay(), 'completed_at' => now()->subHour(), 'last_interaction_at' => now()->subHour()]);
    $skill = Skill::factory()->create(['name' => 'Distance']);
    UserMastery::factory()->create(['user_id' => $learner->id, 'skill_id' => $skill->id, 'mastery_score' => 80]);

    $this->actingAs($admin)->get(route('admin.analytics.learning', ['course' => $course->id]))
        ->assertOk()->assertSee('Radar course')->assertSee('Learning funnel')->assertSee('Distance');
});

it('denies learning analytics to non-admin users', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.analytics.learning'))->assertForbidden();
});

it('preserves filter context in drill-down and exports bounded metadata', function () {
    $admin = learningAnalyticsAdmin();
    $course = Course::factory()->create(['title' => 'Context course']);

    $this->actingAs($admin)->get(route('admin.analytics.learning', ['course' => $course->id, 'range' => '7d', 'timezone' => 'UTC']))
        ->assertOk()->assertSee('course='.$course->id)->assertSee('range=7d')->assertSee('timezone=UTC');
    $this->actingAs($admin)->get(route('admin.analytics.learning.export', ['course' => $course->id, 'range' => '7d', 'timezone' => 'UTC']))
        ->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')->assertSee('generated_at,from,to,timezone');
    $this->actingAs($admin)->get(route('admin.analytics.learning.drilldown', ['type' => 'course', 'id' => $course->id, 'range' => '7d', 'timezone' => 'UTC']))
        ->assertOk();
});
