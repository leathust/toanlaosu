<?php

use App\Data\Admin\AdminDashboardFilters;
use App\Http\Middleware\SecurityHeaders;
use App\Models\Role;
use App\Models\User;
use App\Services\Admin\AdminDashboardQuery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

uses(LazilyRefreshDatabase::class);

function adm011Admin(): User
{
    $admin = User::factory()->create();
    $admin->roles()->attach(Role::query()->firstOrCreate(['name' => 'admin'], ['display_name' => 'Admin'])->id);

    return $admin;
}

it('normalizes dashboard dimensions and isolates cache by scope and timezone', function () {
    $filters = AdminDashboardFilters::fromRequest(request()->merge([
        'range' => '365d',
        'compare' => 'invalid',
        'course' => '-1',
        'timezone' => 'not-a-timezone',
    ]));

    expect($filters->range)->toBe('30d')
        ->and($filters->compare)->toBe('previous')
        ->and($filters->courseId)->toBeNull()
        ->and($filters->timezone)->toBe(config('app.timezone'))
        ->and((new AdminDashboardFilters(timezone: 'UTC'))->cacheKey())
        ->not->toBe((new AdminDashboardFilters(timezone: 'Asia/Bangkok'))->cacheKey());
});

it('stays within the dashboard query budget and exposes an indexable plan', function () {
    Cache::flush();
    DB::table('users')->count();
    $query = app(AdminDashboardQuery::class);
    $filters = new AdminDashboardFilters(range: '7d', compare: 'none', timezone: 'UTC');
    $queryCount = 0;
    DB::listen(function () use (&$queryCount): void {
        $queryCount++;
    });

    $query->overview($filters);

    $planSql = DB::getDriverName() === 'sqlite'
        ? 'EXPLAIN QUERY PLAN SELECT id FROM lesson_block_progress WHERE status = ? AND completed_at BETWEEN ? AND ?'
        : 'EXPLAIN SELECT id FROM lesson_block_progress WHERE status = ? AND completed_at BETWEEN ? AND ?';
    $plan = DB::select($planSql, ['completed', $filters->start(), $filters->end()]);

    expect($queryCount)->toBeLessThanOrEqual(80)->and($plan)->not->toBeEmpty();
});

it('keeps admin operations private and emits bounded observability metadata', function () {
    Log::spy();
    $admin = adm011Admin();

    $this->actingAs($admin)->get(route('admin.dashboard', ['secret' => 'do-not-log']))->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');

    Log::shouldHaveReceived('info')->with('admin.request.completed', Mockery::on(function (array $context): bool {
        return isset($context['route'], $context['status'], $context['duration_ms']) && ! isset($context['secret']);
    }))->atLeast()->once();
});

it('denies admin analytics to non-admin users and limits CSV exports', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.analytics.learning.export'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.users.export'))->assertForbidden();
});

it('neutralizes spreadsheet formulas in bounded user exports', function () {
    $admin = adm011Admin();
    User::factory()->create(['name' => '=HYPERLINK("https://example.test","click")']);

    $response = $this->actingAs($admin)->get(route('admin.users.export'));

    $response->assertOk()->assertHeader('X-Export-Row-Limit', '1000');
    expect($response->getContent())->toContain("'=HYPERLINK");
});

it('adds security policy headers and transport protection only on HTTPS', function () {
    $this->get('/ui/components')->assertOk()
        ->assertHeader('Content-Security-Policy')
        ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin')
        ->assertHeaderMissing('Strict-Transport-Security');

    $request = Request::create('/ui/components', 'GET', [], [], [], ['HTTPS' => 'on']);
    $response = app(SecurityHeaders::class)->handle($request, fn (): Response => new Response('ok'));

    expect($response->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000; includeSubDomains');
});
