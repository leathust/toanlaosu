<?php

use App\Data\Admin\AdminDashboardFilters;
use App\Models\AiUsageLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonBlock;
use App\Models\LessonBlockProgress;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAttempt;
use App\Services\Admin\AdminDashboardQuery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

function dashboardAdmin(): User
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::factory()->create(['name' => 'admin'])->id);

    return $admin;
}

it('calculates dashboard KPI formulas and course health from real records', function () {
    $this->travelTo('2026-01-15 12:00:00');
    Cache::flush();
    $admin = dashboardAdmin();
    $course = Course::factory()->create(['title' => 'Hàm số']);
    $unit = $course->units()->create(['slug' => 'unit-1', 'title' => 'Unit 1', 'status' => 'published', 'sort_order' => 1]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id, 'status' => 'published']);
    $block = LessonBlock::factory()->create(['lesson_id' => $lesson->id, 'is_blocking' => true]);
    $firstLearner = User::factory()->create();
    $secondLearner = User::factory()->create();
    Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $firstLearner->id, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
    Enrollment::factory()->create(['course_id' => $course->id, 'user_id' => $secondLearner->id, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(), 'period_number' => 2]);
    LessonBlockProgress::factory()->create(['user_id' => $firstLearner->id, 'lesson_block_id' => $block->id, 'status' => 'completed', 'completed_at' => now()->subHour(), 'last_interaction_at' => now()->subHour()]);
    LessonBlockProgress::factory()->create(['user_id' => $secondLearner->id, 'lesson_block_id' => $block->id, 'last_interaction_at' => now()->subHour()]);
    $version = QuestionVersion::factory()->create();
    UserAttempt::factory()->create(['user_id' => $firstLearner->id, 'question_version_id' => $version->id, 'lesson_block_id' => $block->id, 'is_correct' => true, 'submitted_at' => now()->subHour()]);
    UserAttempt::factory()->create(['user_id' => $secondLearner->id, 'question_version_id' => $version->id, 'lesson_block_id' => $block->id, 'is_correct' => false, 'submitted_at' => now()->subHour()]);
    AiUsageLog::factory()->create(['status' => 'succeeded', 'occurred_at' => now()->subHour()]);
    AiUsageLog::factory()->create(['status' => 'failed', 'occurred_at' => now()->subHour()]);

    $snapshot = app(AdminDashboardQuery::class)->overview(new AdminDashboardFilters(range: '7d', courseId: $course->id));
    $kpis = collect($snapshot['kpis'])->keyBy('key');

    expect($kpis['active_learners']['value'])->toBe(2)
        ->and($kpis['active_enrollments']['value'])->toBe(2)
        ->and($kpis['completion']['value'])->toBe(100.0)
        ->and($kpis['question_accuracy']['value'])->toBe(50.0)
        ->and($kpis['ai_success_rate']['value'])->toBe(0.0)
        ->and($kpis['ai_success_rate']['status'])->toBe('empty')
        ->and($snapshot['courseHealth'][0]['title'])->toBe('Hàm số')
        ->and($snapshot['courseHealth'][0]['learnersAtRisk'])->toBe(0);
});

it('returns empty status instead of a misleading percentage when a denominator is zero', function () {
    $this->travelTo('2026-01-15 12:00:00');
    Cache::flush();

    $snapshot = app(AdminDashboardQuery::class)->overview(new AdminDashboardFilters(range: '7d', compare: 'none'));
    $kpis = collect($snapshot['kpis'])->keyBy('key');

    expect($kpis['completion']['status'])->toBe('empty')
        ->and($kpis['completion']['value'])->toBe(0.0)
        ->and($kpis['question_accuracy']['status'])->toBe('empty')
        ->and($kpis['return_rate']['status'])->toBe('empty')
        ->and($kpis['ai_success_rate']['status'])->toBe('empty');
});

it('isolates dashboard cache entries by course and preserves normalized URL filters', function () {
    $this->travelTo('2026-01-15 12:00:00');
    Cache::flush();
    $admin = dashboardAdmin();
    $firstCourse = Course::factory()->create(['title' => 'First']);
    $secondCourse = Course::factory()->create(['title' => 'Second']);
    Enrollment::factory()->create(['course_id' => $firstCourse->id, 'user_id' => User::factory()->create()->id]);
    Enrollment::factory()->create(['course_id' => $secondCourse->id, 'user_id' => User::factory()->create()->id]);

    $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
    ])->actingAs($admin)
        ->get(route('admin.dashboard', ['range' => '7d', 'course' => $firstCourse->id, 'timezone' => 'UTC']))
        ->assertOk()
        ->assertJsonPath('props.filters.range', '7d')
        ->assertJsonPath('props.filters.course', $firstCourse->id)
        ->assertJsonPath('props.filters.timezone', 'UTC')
        ->assertJsonPath('props.kpis.1.value', 1)
        ->assertJsonPath('props.courseHealth.0.title', 'First');

    $all = app(AdminDashboardQuery::class)->overview(new AdminDashboardFilters(range: '7d'));
    expect(collect($all['kpis'])->firstWhere('key', 'active_enrollments')['value'])->toBe(2);
});

it('serves a repeated overview from the scoped cache without database queries', function () {
    Cache::flush();
    $filters = new AdminDashboardFilters(range: '7d', timezone: 'UTC');
    $query = app(AdminDashboardQuery::class);
    $query->overview($filters);
    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });

    $query->overview($filters);

    expect($queries)->toBe(0);
});
