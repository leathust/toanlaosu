<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\AiOperationsController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\ExamOperationsController;
use App\Http\Controllers\Admin\SystemHealthController;
use App\Http\Controllers\AdminActionCenterController;
use App\Http\Controllers\AdminContentReviewController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLearningAnalyticsController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\InertiaPilotController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\SceneController;
use App\Http\Controllers\StudentLearningController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherExamController;
use App\Models\AuditLog;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'create'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware(['guest', 'throttle:login'])->name('login.store');
Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');
Route::get('/forgot-password', [PasswordController::class, 'createRequest'])->middleware('guest')->name('password.request');
Route::post('/forgot-password', [PasswordController::class, 'sendResetLink'])->middleware(['guest', 'throttle:password-reset'])->name('password.email');
Route::get('/reset-password/{token}', [PasswordController::class, 'createReset'])->middleware('guest')->name('password.reset');
Route::post('/reset-password', [PasswordController::class, 'reset'])->middleware(['guest', 'throttle:password-reset'])->name('password.update');
Route::get('/invite/{token}', [InvitationController::class, 'show'])->middleware('guest')->name('invite.accept');
Route::post('/invite/{token}', [InvitationController::class, 'accept'])->middleware(['guest', 'throttle:invite'])->name('invite.store');
Route::view('/verify-email', 'auth.verify-email')->middleware(['auth', 'active.account'])->name('verification.notice');
Route::get('/confirm-password', [AccountController::class, 'confirmPasswordForm'])
    ->middleware(['auth', 'active.account'])
    ->name('password.confirm');
Route::post('/confirm-password', [AccountController::class, 'confirmPassword'])
    ->middleware(['auth', 'active.account'])
    ->name('password.confirm.store');
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request): RedirectResponse {
    $request->fulfill();
    AuditLog::query()->create([
        'actor_id' => $request->user()->getKey(),
        'event' => 'auth.email_verified',
        'auditable_type' => get_class($request->user()),
        'auditable_id' => $request->user()->getKey(),
        'new_values' => ['result' => 'success'],
        'ip_address' => $request->ip(),
        'user_agent' => $request->userAgent(),
        'occurred_at' => now(),
        'retention_expires_at' => now()->addMonths(6),
    ]);

    return redirect()->route('account.settings')->with('status', 'Email đã được xác minh thành công.');
})->middleware(['auth', 'active.account', 'signed'])->name('verification.verify');
Route::post('/email/verification-notification', function (Request $request): RedirectResponse {
    if (! $request->user()->hasVerifiedEmail()) {
        $request->user()->sendEmailVerificationNotification();
    }

    return back()->with('status', 'verification-link-sent');
})->middleware(['auth', 'active.account', 'throttle:verification'])->name('verification.send');
Route::middleware(['auth', 'active.account', 'auth.session'])->group(function (): void {
    Route::get('/workspace', [AuthController::class, 'workspace'])->name('workspace');
    Route::get('/account/settings', [AccountController::class, 'edit'])->name('account.settings');
    Route::put('/account/email', [AccountController::class, 'updateEmail'])
        ->middleware('password.intent')
        ->name('account.email.update');
    Route::put('/account/password', [AccountController::class, 'updatePassword'])
        ->middleware('password.intent')
        ->name('account.password.update');
    Route::delete('/account/sessions/{sessionKey}', [AccountController::class, 'revokeSession'])
        ->middleware('password.intent')
        ->name('account.sessions.revoke');
    Route::delete('/account/sessions', [AccountController::class, 'revokeOtherSessions'])
        ->middleware('password.intent')
        ->name('account.sessions.revoke-others');
    Route::get('/student', [StudentLearningController::class, 'dashboard'])->name('student.dashboard');
    Route::view('/student/lessons/radar-may-bay', 'student.lesson')->name('student.lesson');
    Route::view('/student/practice/radar-may-bay', 'student.question')->name('student.practice');
    Route::middleware('verified')->group(function (): void {
        Route::get('/student/learn', [StudentLearningController::class, 'inertiaDashboard'])->name('student.learning.dashboard');
        Route::get('/student/learn/courses/{course:slug}/lessons/{lesson:slug}', [StudentLearningController::class, 'inertiaLesson'])
            ->name('student.learning.lesson');
        Route::get('/student/scenes/{visualScene:public_id}', [SceneController::class, 'show'])->name('student.scene.show');
        Route::post('/student/scenes/{visualScene:public_id}/command', [SceneController::class, 'command'])->name('student.scene.command');
        Route::post('/student/lessons/blocks/{lessonBlock}/complete', [StudentLearningController::class, 'completeBlock'])
            ->name('student.lesson.progress');
        Route::post('/student/practice/attempts', [StudentLearningController::class, 'submitAttempt'])
            ->name('student.practice.attempts.store');
        Route::get('/student/exams/{exam}/attempt', [ExamController::class, 'show'])
            ->name('student.exam.show');
        Route::post('/student/exams/attempts/{attempt}/answers', [ExamController::class, 'saveAnswer'])
            ->name('student.exam.answers.save');
        Route::post('/student/exams/attempts/{attempt}/submit', [ExamController::class, 'submit'])
            ->name('student.exam.submit');
        Route::get('/student/exams/attempts/{attempt}/result', [ExamController::class, 'result'])
            ->name('student.exam.result');
        Route::get('/student/exams/{exam}/history', [ExamController::class, 'history'])
            ->name('student.exam.history');
        Route::get('/student/chat/{thread?}', [ChatController::class, 'show'])
            ->name('student.chat');
        Route::post('/student/chat/messages', [ChatController::class, 'send'])
            ->name('student.chat.send');
        Route::get('/student/chat/messages/{message}/stream', [ChatController::class, 'stream'])
            ->name('student.chat.stream');
        Route::post('/student/chat/messages/{message}/feedback', [ChatController::class, 'feedback'])
            ->name('student.chat.feedback');
        Route::post('/student/chat/{thread}/command', [ChatController::class, 'command'])
            ->name('student.chat.command');
    });
    Route::middleware('verified')->group(function (): void {
        Route::get('/teacher', [TeacherController::class, 'dashboard'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.dashboard');
        Route::get('/teacher/courses/{course}', [TeacherController::class, 'course'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.course');
        Route::post('/teacher/courses/{course}/lessons', [TeacherController::class, 'storeLesson'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.lessons.store');
        Route::get('/teacher/lessons/{lesson}/edit', [TeacherController::class, 'editLesson'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.lessons.edit');
        Route::patch('/teacher/lessons/{lesson}', [TeacherController::class, 'updateLesson'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.lessons.update');
        Route::post('/teacher/lessons/{lesson}/publish', [TeacherController::class, 'publishLesson'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.lessons.publish');
        Route::post('/teacher/lessons/{lesson}/duplicate', [TeacherController::class, 'duplicateLesson'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.lessons.duplicate');
        Route::get('/teacher/lessons/{lesson}/revisions', [TeacherController::class, 'lessonRevisions'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.lessons.revisions');
        Route::post('/teacher/questions', [TeacherController::class, 'storeQuestion'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.questions.store');
        Route::get('/teacher/questions/{question}/versions/{version}/edit', [TeacherController::class, 'editQuestion'])
            ->middleware('workspace.role:teacher,admin')
            ->name('teacher.questions.edit');
        Route::patch('/teacher/questions/{question}/versions/{version}', [TeacherController::class, 'updateQuestion'])
            ->middleware('workspace.role:teacher,admin')
            ->name('teacher.questions.update');
        Route::post('/teacher/scenes', [TeacherController::class, 'storeScene'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.scenes.store');
        Route::get('/teacher/scenes/{visualScene}/versions/{version}/edit', [TeacherController::class, 'editScene'])
            ->middleware('workspace.role:teacher,admin')
            ->name('teacher.scenes.edit');
        Route::patch('/teacher/scenes/{visualScene}/versions/{version}', [TeacherController::class, 'updateScene'])
            ->middleware('workspace.role:teacher,admin')
            ->name('teacher.scenes.update');
        Route::get('/teacher/exams', [TeacherExamController::class, 'index'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.exams');
        Route::get('/teacher/exams/{exam}', [TeacherExamController::class, 'show'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.exam');
        Route::get('/teacher/exams/{exam}/export-latex', [TeacherExamController::class, 'exportLatex'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.exam.export-latex');
        Route::get('/teacher/exams/{exam}/report', [TeacherExamController::class, 'report'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.exam.report');
        Route::post('/teacher/courses/{course}/enrollments', [EnrollmentController::class, 'store'])
            ->middleware('workspace.role:teacher')
            ->name('teacher.courses.enrollments.store');
        Route::get('/admin', [AdminController::class, 'dashboard'])
            ->middleware('workspace.role:admin')
            ->name('admin.dashboard');
        Route::get('/admin/users', [AdminController::class, 'users'])
            ->middleware('workspace.role:admin')
            ->name('admin.users');
        Route::get('/admin/users/export', [AdminUserController::class, 'export'])
            ->middleware('workspace.role:admin')
            ->name('admin.users.export');
        Route::get('/admin/users/{user}', [AdminUserController::class, 'show'])
            ->middleware('workspace.role:admin')
            ->name('admin.users.show');
        Route::patch('/admin/users/{user}/roles', [AdminUserController::class, 'updateRoles'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.users.roles');
        Route::delete('/admin/users/{user}/sessions', [AdminUserController::class, 'revokeSessions'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.users.sessions.revoke');
        Route::get('/admin/content', [AdminController::class, 'content'])
            ->middleware('workspace.role:admin')
            ->name('admin.content');
        Route::get('/admin/content/review', [AdminContentReviewController::class, 'index'])
            ->middleware('workspace.role:admin')
            ->name('admin.content.review');
        Route::get('/admin/content/review/{type}/{id}', [AdminContentReviewController::class, 'show'])
            ->middleware('workspace.role:admin')
            ->name('admin.content.review.show');
        Route::patch('/admin/content/review/{type}/{id}', [AdminContentReviewController::class, 'review'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.content.review.decision');
        Route::post('/admin/content/review/batch', [AdminContentReviewController::class, 'batchReview'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.content.review.batch');
        Route::get('/admin/audit', [AdminController::class, 'audit'])
            ->middleware('workspace.role:admin')
            ->name('admin.audit');
        Route::get('/admin/operations', [AuditController::class, 'index'])
            ->middleware('workspace.role:admin')
            ->name('admin.operations');
        Route::get('/admin/operations/audit/{audit}', [AuditController::class, 'show'])
            ->middleware('workspace.role:admin')
            ->name('admin.operations.audit.show');
        Route::post('/admin/operations/audit/export', [AuditController::class, 'export'])
            ->middleware(['workspace.role:admin', 'password.intent', 'throttle:admin-operations'])
            ->name('admin.operations.audit.export');
        Route::get('/admin/operations/audit/exports/{publicId}/download', [AuditController::class, 'download'])
            ->middleware('workspace.role:admin')
            ->name('admin.operations.audit.export.download');
        Route::post('/admin/operations/health/{fingerprint}', [SystemHealthController::class, 'action'])
            ->middleware(['workspace.role:admin', 'password.intent', 'throttle:admin-operations'])
            ->name('admin.operations.health.action');
        Route::get('/admin/ai-usage', [AdminController::class, 'aiUsage'])
            ->middleware('workspace.role:admin')
            ->name('admin.ai-usage');
        Route::get('/admin/ai-operations', [AiOperationsController::class, 'index'])
            ->middleware('workspace.role:admin')
            ->name('admin.ai');
        Route::get('/admin/action-center', [AdminActionCenterController::class, 'index'])
            ->middleware('workspace.role:admin')
            ->name('admin.action-center');
        Route::get('/admin/exams', [ExamOperationsController::class, 'index'])
            ->middleware('workspace.role:admin')
            ->name('admin.exams');
        Route::get('/admin/exams/attempts/{attempt}', [ExamOperationsController::class, 'show'])
            ->middleware('workspace.role:admin')
            ->name('admin.exams.attempts.show');
        Route::post('/admin/exams/attempts/{attempt}/recover', [ExamOperationsController::class, 'recover'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.exams.attempts.recover');
        Route::get('/admin/analytics/learning', AdminLearningAnalyticsController::class)
            ->middleware('workspace.role:admin')
            ->name('admin.analytics.learning');
        Route::get('/admin/analytics/learning/export', [AdminLearningAnalyticsController::class, 'export'])
            ->middleware('workspace.role:admin')
            ->name('admin.analytics.learning.export');
        Route::get('/admin/analytics/learning/drilldown/{type}/{id}', [AdminLearningAnalyticsController::class, 'drilldown'])
            ->middleware('workspace.role:admin')
            ->name('admin.analytics.learning.drilldown');
        Route::patch('/admin/action-center/{fingerprint}/state', [AdminActionCenterController::class, 'updateState'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.action-center.state');
        Route::patch('/admin/users/{user}/status', [AdminController::class, 'updateUserStatus'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.users.status');
        Route::post('/admin/users/bulk-status', [AdminController::class, 'bulkUserStatus'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.users.bulk-status');
        Route::post('/admin/questions/{question}/approve', [AdminController::class, 'approveQuestion'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.questions.approve');
        Route::post('/admin/scenes/{scene}/approve', [AdminController::class, 'approveScene'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.scenes.approve');
        Route::put('/admin/settings/{key}', [AdminController::class, 'updateSetting'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.settings.update');
        Route::post('/admin/invites', [InvitationController::class, 'store'])
            ->middleware('workspace.role:admin')
            ->name('admin.invites.store');
        Route::post('/admin/invites/{invitation}/resend', [InvitationController::class, 'resend'])
            ->middleware('workspace.role:admin')
            ->name('admin.invites.resend');
        Route::delete('/admin/invites/{invitation}', [InvitationController::class, 'revoke'])
            ->middleware(['workspace.role:admin', 'password.intent'])
            ->name('admin.invites.revoke');
    });
});

Route::view('/ui/prototype', 'ui.prototype')->name('ui.prototype');
Route::view('/ui/components', 'ui.components')->name('ui.components');
Route::get('/ui/inertia-pilot', InertiaPilotController::class)->name('ui.inertia-pilot');
