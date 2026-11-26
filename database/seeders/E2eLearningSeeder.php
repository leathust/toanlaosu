<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamAttemptAnswer;
use App\Models\ExamQuestion;
use App\Models\ExamVersion;
use App\Models\Lesson;
use App\Models\LessonBlock;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuestionVersion;
use App\Models\Role;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class E2eLearningSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User', 'status' => 'active', 'timezone' => 'Asia/Ho_Chi_Minh',
            'email_verified_at' => now(), 'password' => Hash::make('password'),
        ]);
        $studentRole = Role::query()->where('name', 'student')->first();
        if ($studentRole && ! $user->roles()->whereKey($studentRole->id)->exists()) {
            $user->roles()->attach($studentRole);
        }

        $course = Course::query()->firstOrCreate(['slug' => 'e2e-radar'], [
            'title' => 'E2E Radar Course', 'summary' => 'Course fixture for authenticated learning flow',
            'status' => 'published', 'difficulty' => 'basic',
        ]);
        $unit = Unit::query()->firstOrCreate(['course_id' => $course->id, 'slug' => 'e2e-unit'], [
            'title' => 'E2E Unit', 'status' => 'published', 'sort_order' => 1,
        ]);
        $lesson = Lesson::query()->firstOrCreate(['unit_id' => $unit->id, 'slug' => 'e2e-lesson'], [
            'title' => 'E2E Radar Lesson', 'summary' => 'A persisted lesson for browser verification',
            'status' => 'published', 'sort_order' => 1,
        ]);
        $question = Question::query()->firstOrCreate(['public_id' => '01J0E2EQUESTION00000000000'], [
            'owner_id' => $user->id, 'question_type' => 'single_choice', 'status' => 'published', 'difficulty' => 'basic',
        ]);
        $version = QuestionVersion::query()->firstOrCreate(['question_id' => $question->id, 'version' => 1], [
            'stem' => 'Radar phát hiện máy bay khi nào?', 'answer_config' => ['type' => 'single_choice'],
            'scoring_policy' => ['points' => 1], 'allow_negative' => false, 'status' => 'published', 'published_at' => now(),
        ]);
        $question->update(['published_version_id' => $version->id]);
        QuestionOption::query()->firstOrCreate(['question_version_id' => $version->id, 'option_key' => 'A'], ['content' => 'Khi máy bay nằm trong vùng kiểm soát', 'is_correct' => true, 'sort_order' => 1]);
        QuestionOption::query()->firstOrCreate(['question_version_id' => $version->id, 'option_key' => 'B'], ['content' => 'Khi máy bay ở rất xa radar', 'is_correct' => false, 'sort_order' => 2]);
        LessonBlock::query()->firstOrCreate(['lesson_id' => $lesson->id, 'sort_order' => 1], [
            'block_type' => 'note', 'is_blocking' => true, 'body' => '<p>Đọc mô hình radar và đánh dấu block đã hoàn thành.</p>',
        ]);
        LessonBlock::query()->firstOrCreate(['lesson_id' => $lesson->id, 'sort_order' => 2], [
            'block_type' => 'question', 'is_blocking' => true, 'question_id' => $question->id,
        ]);
        Enrollment::query()->firstOrCreate(['course_id' => $course->id, 'user_id' => $user->id, 'period_number' => 1], [
            'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addYear(), 'source' => 'e2e',
        ]);

        $exam = Exam::query()->updateOrCreate(['public_id' => '01J0E2EEXAM000000000000000'], [
            'course_id' => $course->id, 'title' => 'E2E Radar Exam', 'status' => 'published',
            'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'duration_minutes' => 30,
            'late_entry_minutes' => 0, 'grace_period_seconds' => 30, 'max_attempts' => 1,
        ]);
        $examVersion = ExamVersion::query()->firstOrCreate(['exam_id' => $exam->id, 'version' => 1], [
            'settings_snapshot' => ['duration_minutes' => 30], 'total_points' => 1, 'status' => 'published', 'published_at' => now(),
        ]);
        ExamQuestion::query()->firstOrCreate(['exam_version_id' => $examVersion->id, 'question_version_id' => $version->id], [
            'sort_order' => 1, 'points' => 1,
        ]);

        ExamAttempt::query()
            ->where('exam_version_id', $examVersion->id)
            ->where('user_id', $user->id)
            ->get()
            ->each(function (ExamAttempt $attempt): void {
                $questionIds = $attempt->questions()->pluck('id');
                ExamAttemptAnswer::query()->whereIn('exam_attempt_question_id', $questionIds)->delete();
                $attempt->events()->delete();
                $attempt->questions()->delete();
                $attempt->delete();
            });
    }
}
