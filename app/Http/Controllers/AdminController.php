<?php

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Question;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\VisualScene;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function dashboard(Request $request): View
    {
        $this->ensureAdmin($request->user());
        $stats = ['users' => User::query()->count(), 'courses' => Course::query()->count(), 'draftQuestions' => Question::query()->where('status', 'draft')->count(), 'failedAi' => AiUsageLog::query()->where('status', 'failed')->where('occurred_at', '>=', now()->subDay())->count()];
        $audits = AuditLog::query()->with('actor')->latest('occurred_at')->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'audits'));
    }

    public function users(Request $request): View
    {
        $this->ensureAdmin($request->user());
        $users = User::query()->with('roles')->latest('id')->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function content(Request $request): View
    {
        $this->ensureAdmin($request->user());
        $draftQuestions = Question::query()->with('owner')->where('status', 'draft')->latest('id')->limit(20)->get();
        $draftScenes = VisualScene::query()->with('creator')->where('status', 'draft')->latest('id')->limit(20)->get();
        $draftCourses = Course::query()->where('status', 'draft')->latest('id')->limit(20)->get();

        return view('admin.content', compact('draftQuestions', 'draftScenes', 'draftCourses'));
    }

    public function audit(Request $request): View
    {
        $this->ensureAdmin($request->user());
        $audits = AuditLog::query()->with('actor')->latest('occurred_at')->paginate(30);

        return view('admin.audit', compact('audits'));
    }

    public function aiUsage(Request $request): View
    {
        $this->ensureAdmin($request->user());
        $logs = AiUsageLog::query()->with('user')->latest('occurred_at')->paginate(30);
        $settings = SystemSetting::query()->whereIn('key', ['ai.enabled', 'ai.daily_budget'])->get();

        return view('admin.ai-usage', compact('logs', 'settings'));
    }

    private function ensureAdmin(User $user): void
    {
        abort_unless($user->roles()->where('name', 'admin')->exists(), 403);
    }
}
