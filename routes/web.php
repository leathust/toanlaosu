<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\StudentLearningController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherExamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:6,1')->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
Route::view('/verify-email', 'auth.verify-email')->name('verification.notice');
Route::view('/workspace', 'auth.workspace')->name('workspace');
Route::get('/student', [StudentLearningController::class, 'dashboard'])->name('student.dashboard');
Route::view('/student/lessons/radar-may-bay', 'student.lesson')->name('student.lesson');
Route::view('/student/practice/radar-may-bay', 'student.question')->name('student.practice');
Route::post('/student/lessons/blocks/{lessonBlock}/complete', [StudentLearningController::class, 'completeBlock'])
    ->middleware('auth')
    ->name('student.lesson.progress');
Route::post('/student/practice/attempts', [StudentLearningController::class, 'submitAttempt'])
    ->middleware('auth')
    ->name('student.practice.attempts.store');
Route::get('/student/exams/{exam}/attempt', [ExamController::class, 'show'])
    ->middleware('auth')
    ->name('student.exam.show');
Route::post('/student/exams/attempts/{attempt}/answers', [ExamController::class, 'saveAnswer'])
    ->middleware('auth')
    ->name('student.exam.answers.save');
Route::post('/student/exams/attempts/{attempt}/submit', [ExamController::class, 'submit'])
    ->middleware('auth')
    ->name('student.exam.submit');
Route::get('/student/exams/attempts/{attempt}/result', [ExamController::class, 'result'])
    ->middleware('auth')
    ->name('student.exam.result');
Route::get('/student/exams/{exam}/history', [ExamController::class, 'history'])
    ->middleware('auth')
    ->name('student.exam.history');
Route::get('/student/chat/{thread?}', [ChatController::class, 'show'])
    ->middleware('auth')
    ->name('student.chat');
Route::post('/student/chat/messages', [ChatController::class, 'send'])
    ->middleware('auth')
    ->name('student.chat.send');
Route::get('/student/chat/messages/{message}/stream', [ChatController::class, 'stream'])
    ->middleware('auth')
    ->name('student.chat.stream');
Route::post('/student/chat/messages/{message}/feedback', [ChatController::class, 'feedback'])
    ->middleware('auth')
    ->name('student.chat.feedback');
Route::post('/student/chat/{thread}/command', [ChatController::class, 'command'])
    ->middleware('auth')
    ->name('student.chat.command');
Route::get('/teacher', [TeacherController::class, 'dashboard'])
    ->middleware('auth')
    ->name('teacher.dashboard');
Route::get('/teacher/courses/{course}', [TeacherController::class, 'course'])
    ->middleware('auth')
    ->name('teacher.course');
Route::post('/teacher/courses/{course}/lessons', [TeacherController::class, 'storeLesson'])
    ->middleware('auth')
    ->name('teacher.lessons.store');
Route::post('/teacher/questions', [TeacherController::class, 'storeQuestion'])
    ->middleware('auth')
    ->name('teacher.questions.store');
Route::post('/teacher/scenes', [TeacherController::class, 'storeScene'])
    ->middleware('auth')
    ->name('teacher.scenes.store');
Route::get('/teacher/exams', [TeacherExamController::class, 'index'])
    ->middleware('auth')
    ->name('teacher.exams');
Route::get('/teacher/exams/{exam}', [TeacherExamController::class, 'show'])
    ->middleware('auth')
    ->name('teacher.exam');
Route::get('/teacher/exams/{exam}/export-latex', [TeacherExamController::class, 'exportLatex'])
    ->middleware('auth')
    ->name('teacher.exam.export-latex');
Route::get('/teacher/exams/{exam}/report', [TeacherExamController::class, 'report'])
    ->middleware('auth')
    ->name('teacher.exam.report');
Route::get('/admin', [AdminController::class, 'dashboard'])
    ->middleware('auth')
    ->name('admin.dashboard');
Route::get('/admin/users', [AdminController::class, 'users'])
    ->middleware('auth')
    ->name('admin.users');
Route::get('/admin/content', [AdminController::class, 'content'])
    ->middleware('auth')
    ->name('admin.content');
Route::get('/admin/audit', [AdminController::class, 'audit'])
    ->middleware('auth')
    ->name('admin.audit');
Route::get('/admin/ai-usage', [AdminController::class, 'aiUsage'])
    ->middleware('auth')
    ->name('admin.ai-usage');

Route::view('/ui/prototype', 'ui.prototype')->name('ui.prototype');
Route::view('/ui/components', 'ui.components')->name('ui.components');
