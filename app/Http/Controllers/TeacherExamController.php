<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TeacherExamController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureTeacher($request->user());
        $exams = Exam::query()
            ->with('course')
            ->whereHas('course.teachers', function (Builder $query) use ($request): void {
                $query->whereKey($request->user()->id)->whereNull('course_teachers.revoked_at');
            })
            ->latest('id')
            ->get();

        return view('teacher.exams', compact('exams'));
    }

    public function show(Request $request, Exam $exam): View
    {
        $this->ensureExamAccess($request->user(), $exam);
        $exam->load('course', 'versions.questions.questionVersion');

        return view('teacher.exam', compact('exam'));
    }

    public function exportLatex(Request $request, Exam $exam): Response
    {
        $this->ensureExamAccess($request->user(), $exam);
        $exam->load('versions.questions.questionVersion');
        $version = $exam->versions->sortByDesc('version')->first();
        $lines = ['\\documentclass{article}', '\\usepackage{amsmath}', '\\begin{document}', '\\section*{'.self::escapeLatex($exam->title).'}'];
        foreach ($version?->questions ?? [] as $index => $question) {
            $lines[] = '\\textbf{Câu '.($index + 1).'.} '.self::escapeLatex($question->questionVersion->stem).'\\par';
        }
        $lines[] = '\\end{document}';

        return response(implode("\n", $lines), 200, ['Content-Type' => 'application/x-tex', 'Content-Disposition' => 'attachment; filename="'.str($exam->title)->slug().'.tex"']);
    }

    public function report(Request $request, Exam $exam): View
    {
        $this->ensureExamAccess($request->user(), $exam);
        $attempts = $exam->versions()->with('attempts.user')->get()->flatMap->attempts;
        $submitted = $attempts->where('status', 'submitted');
        $average = $submitted->avg(fn ($attempt): float => (float) $attempt->score);

        return view('teacher.exam-report', ['exam' => $exam, 'attempts' => $attempts, 'submittedCount' => $submitted->count(), 'averageScore' => $average]);
    }

    private function ensureTeacher(User $user): void
    {
        abort_unless($user->roles()->where('name', 'teacher')->exists(), 403);
    }

    private function ensureExamAccess(User $user, Exam $exam): void
    {
        $this->ensureTeacher($user);
        abort_unless($exam->course_id && $user->teachingCourses()->whereKey($exam->course_id)->wherePivotNull('revoked_at')->exists(), 403);
    }

    private static function escapeLatex(string $value): string
    {
        return str_replace(['\\', '&', '%', '$', '#', '_', '{', '}'], ['\\textbackslash{}', '\\&', '\\%', '\\$', '\\#', '\\_', '\\{', '\\}'], $value);
    }
}
