<?php

namespace App\Http\Controllers\Admin;

use App\Data\Admin\AdminAuditFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\AdminAuditExportRequest;
use App\Jobs\Admin\GenerateAuditExport;
use App\Models\AdminAuditExport;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Admin\AdminOperationsQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuditController extends Controller
{
    public function index(Request $request, AdminOperationsQuery $query): Response
    {
        Gate::authorize('viewAny', User::class);
        $filters = AdminAuditFilters::fromRequest($request);
        $audits = $query->audits($filters);
        $audits->setCollection($audits->getCollection()->map(fn (AuditLog $audit): array => $query->auditPayload($audit)));

        return Inertia::render('Admin/System/Operations', ['filters' => $filters->toArray(), 'audits' => ['data' => $audits->items(), 'meta' => ['currentPage' => $audits->currentPage(), 'lastPage' => $audits->lastPage(), 'perPage' => $audits->perPage(), 'total' => $audits->total()], 'links' => ['first' => $audits->url(1), 'previous' => $audits->previousPageUrl(), 'next' => $audits->nextPageUrl(), 'last' => $audits->url($audits->lastPage())]], 'filterOptions' => $query->filterOptions(), 'health' => $query->health()]);
    }

    public function show(AuditLog $audit, AdminOperationsQuery $query): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('Admin/System/AuditDetail', ['audit' => $query->auditPayload($audit, true)]);
    }

    public function export(AdminAuditExportRequest $request): RedirectResponse
    {
        Gate::authorize('viewAny', User::class);
        $filters = AdminAuditFilters::fromRequest($request);
        $validated = $request->validated();
        $key = hash('sha256', (string) $validated['idempotency_key']);
        $export = AdminAuditExport::query()->where('requested_by', $request->user()->id)->where('idempotency_key', $key)->first();
        if ($export === null) {
            $export = AdminAuditExport::query()->create(['public_id' => (string) Str::ulid(), 'requested_by' => $request->user()->id, 'idempotency_key' => $key, 'status' => 'queued', 'filters' => $filters->toArray(), 'requested_at' => now()]);
            GenerateAuditExport::dispatch($export->id);
            AuditLog::query()->create(['actor_id' => $request->user()->id, 'event' => 'admin.audit_export_requested', 'auditable_type' => AdminAuditExport::class, 'auditable_id' => $export->id, 'new_values' => ['filters' => $filters->toArray()], 'retention_expires_at' => now()->addMonths(6)]);
        }

        return back()->with('status', 'Audit export queued.')->with('export_public_id', $export->public_id);
    }

    public function download(Request $request, string $publicId): BinaryFileResponse|SymfonyResponse
    {
        Gate::authorize('viewAny', User::class);
        $export = AdminAuditExport::query()->where('public_id', $publicId)->where('requested_by', $request->user()->id)->firstOrFail();
        abort_unless($export->status === 'completed' && $export->expires_at?->isFuture() && $export->disk && $export->path && Storage::disk($export->disk)->exists($export->path), 404, 'Export expired or unavailable.');

        return Storage::disk($export->disk)->download($export->path, 'audit-export.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
