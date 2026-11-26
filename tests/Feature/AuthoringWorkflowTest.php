<?php

use App\Models\AuditLog;
use App\Models\ContentRevision;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

function authoringFixture(): array
{
    $teacher = User::factory()->create();
    $teacher->roles()->attach(Role::factory()->create(['name' => 'teacher'])->id);
    $course = Course::factory()->create();
    $course->teachers()->attach($teacher->id, ['scope' => 'editor']);
    $unit = Unit::factory()->create(['course_id' => $course->id]);
    $lesson = Lesson::factory()->create(['unit_id' => $unit->id, 'created_by' => $teacher->id, 'updated_by' => $teacher->id]);

    return compact('teacher', 'course', 'unit', 'lesson');
}

it('updates a lesson draft with optimistic locking and records its revision', function () {
    $fixture = authoringFixture();

    $response = $this->actingAs($fixture['teacher'])->patchJson(route('teacher.lessons.update', $fixture['lesson']), [
        'lock_version' => 1,
        'title' => 'Radar cập nhật',
        'summary' => 'Bản nháp mới',
    ]);

    $response->assertOk()->assertJsonPath('data.lock_version', 2);
    expect($fixture['lesson']->fresh()->title)->toBe('Radar cập nhật')
        ->and(ContentRevision::query()->where('content_type', 'lesson')->where('content_id', $fixture['lesson']->id)->value('version'))->toBe(1)
        ->and(AuditLog::query()->where('event', 'lesson.draft_updated')->count())->toBe(1);
});

it('rejects stale lesson draft updates without overwriting newer content', function () {
    $fixture = authoringFixture();
    $fixture['lesson']->update(['title' => 'Phiên bản hiện tại', 'lock_version' => 2]);

    $this->actingAs($fixture['teacher'])
        ->patchJson(route('teacher.lessons.update', $fixture['lesson']), ['lock_version' => 1, 'title' => 'Ghi đè', 'summary' => null])
        ->assertStatus(409)
        ->assertJsonPath('data.lock_version', 2);

    expect($fixture['lesson']->fresh()->title)->toBe('Phiên bản hiện tại');
});

it('publishes a lesson and records an immutable published revision', function () {
    $fixture = authoringFixture();

    $this->actingAs($fixture['teacher'])
        ->postJson(route('teacher.lessons.publish', $fixture['lesson']))
        ->assertOk()
        ->assertJsonPath('data.status', 'published');

    expect($fixture['lesson']->fresh()->published_at)->not->toBeNull()
        ->and(ContentRevision::query()->where('content_type', 'lesson')->where('content_id', $fixture['lesson']->id)->value('version'))->toBe(1)
        ->and(AuditLog::query()->where('event', 'lesson.published')->count())->toBe(1);

    $this->actingAs($fixture['teacher'])
        ->getJson(route('teacher.lessons.revisions', $fixture['lesson']))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('duplicates a lesson draft for edits after a lesson is published', function () {
    $fixture = authoringFixture();
    $fixture['lesson']->update(['status' => 'published', 'published_at' => now()]);

    $this->actingAs($fixture['teacher'])
        ->patchJson(route('teacher.lessons.update', $fixture['lesson']), ['lock_version' => 1, 'title' => 'Không ghi đè', 'summary' => null])
        ->assertStatus(409);

    $response = $this->actingAs($fixture['teacher'])
        ->postJson(route('teacher.lessons.duplicate', $fixture['lesson']))
        ->assertCreated()
        ->assertJsonPath('data.status', 'draft');

    expect($response->json('data.id'))->not->toBe($fixture['lesson']->id)
        ->and(ContentRevision::query()->where('content_type', 'lesson')->where('content_id', $response->json('data.id'))->count())->toBe(1)
        ->and(AuditLog::query()->where('event', 'lesson.draft_duplicated')->count())->toBe(1);
});

it('blocks a teacher without course editor scope from authoring a lesson', function () {
    $fixture = authoringFixture();
    $other = User::factory()->create();
    $other->roles()->attach(Role::query()->firstOrCreate(['name' => 'teacher'], ['display_name' => 'Teacher'])->id);
    $fixture['course']->teachers()->attach($other->id, ['scope' => 'reviewer']);

    $this->actingAs($other)
        ->patchJson(route('teacher.lessons.update', $fixture['lesson']), ['lock_version' => 1, 'title' => 'Không được phép', 'summary' => null])
        ->assertForbidden();
});

it('renders the typed teacher lesson editor with the current draft and revisions', function () {
    $fixture = authoringFixture();

    $this->actingAs($fixture['teacher'])
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Inertia-Version' => hash_file('xxh128', public_path('build/manifest.json')),
        ])
        ->get(route('teacher.lessons.edit', $fixture['lesson']))
        ->assertOk()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'Teacher/LessonEditor')
        ->assertJsonPath('props.lesson.lock_version', 1)
        ->assertJsonPath('props.course.id', $fixture['course']->id);
});
