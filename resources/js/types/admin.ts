import type { SharedPageProps } from './index';

export type WidgetStatus = 'loading' | 'ready' | 'empty' | 'stale' | 'partial' | 'error';

export interface AdminFilterOption {
    value: string;
    label: string;
}

export interface AdminCourseOption {
    id: number;
    title: string;
}

export interface AdminFilters {
    range: '7d' | '30d' | '90d';
    compare: 'previous' | 'none';
    course: number | null;
    timezone: string;
}

export interface AdminKpi {
    key: string;
    label: string;
    value: number;
    unit: 'count' | 'percent';
    numerator: number;
    denominator: number;
    comparison: {
        value: number;
        delta: number;
        percent: number | null;
    } | null;
    window: { from: string; to: string };
    timezone: string;
    updated_at: string;
    freshness_seconds: number;
    status: WidgetStatus;
    drill_down_url: string;
}

export interface AdminAuditSummary {
    id: number;
    event: string;
    actor: string;
    occurredAt: string | null;
}

export interface AdminOverviewProps extends SharedPageProps {
    filters: AdminFilters;
    filterOptions: {
        ranges: AdminFilterOption[];
        comparisons: AdminFilterOption[];
        timezones: string[];
        courses: AdminCourseOption[];
    };
    kpis: AdminKpi[];
    trend: {
        status: WidgetStatus;
        series: Array<{ label: string; activeLearners: number; returningLearners: number }>;
    };
    courseHealth: Array<{
        courseId: number;
        title: string;
        enrollments: number;
        completion: number | null;
        learnersAtRisk: number;
        freshness: string | null;
    }>;
    freshness: string;
    alerts: Array<{
        id: string;
        title: string;
        severity: 'info' | 'warning' | 'critical';
        href: string;
    }>;
    recentAudits: AdminAuditSummary[];
}

export interface AdminActionAlert {
    id: string;
    source: string;
    sourceId: string;
    severity: 'info' | 'warning' | 'critical';
    title: string;
    summary: string;
    occurredAt: string | null;
    owner: string;
    action: string;
    href: string;
    status: 'open' | 'acknowledged' | 'resolved';
    updatedAt: string | null;
}

export interface AdminActionCenterProps extends SharedPageProps {
    filters: { severity?: string; source?: string; status?: string };
    alerts: AdminActionAlert[];
    counts: Record<'open' | 'acknowledged' | 'resolved', number>;
    filterOptions: { severities: string[]; sources: string[]; statuses: string[] };
}

export interface AdminExamAttempt {
    id: number;
    publicId: string;
    status: string;
    issue: 'overdue' | 'stuck' | null;
    exam: { id: number | null; title: string };
    learner: { id: number | null; name: string };
    attemptNumber: number;
    startedAt: string | null;
    deadlineAt: string | null;
    submittedAt: string | null;
    saveVersion: number;
    lastEventAt: string | null;
    href: string;
}

export interface AdminExamOperationsProps extends SharedPageProps {
    serverNow: string;
    examStatuses: { upcoming: number; live: number; ended: number };
    attemptStatuses: Record<string, number>;
    issues: { overdue: number; stuck: number; save_conflicts: number; reconnects: number; submit_failures: number };
    attempts: { data: AdminExamAttempt[]; meta: { currentPage: number; lastPage: number; perPage: number; total: number } };
    exportJobs: Array<{ id: number; publicId: string; examTitle: string; mode: string; status: string; requestedAt: string | null; completedAt: string | null; failedAt: string | null; expiresAt: string | null; failureHref: string | null }>;
    aiPolicy: { state: string; label: string; reason: string };
    filters: { status?: string; issue?: string };
    filterOptions: { statuses: string[]; issues: string[] };
}

export interface AdminExamAttemptDetailProps extends SharedPageProps {
    serverNow: string;
    attempt: AdminExamAttempt & { questionsCount: number; examStatus: string; examWindow: { startsAt: string | null; endsAt: string | null } };
    events: Array<{ id: number; type: string; occurredAt: string | null; clientOccurredAt: string | null; metadata: Record<string, string | number | null> }>;
    recovery: { allowed: boolean; action: string; reasonRequired: boolean; idempotencyRequired: boolean };
    aiPolicy: { state: string; label: string; reason: string };
}

export interface AdminAiOperationsProps extends SharedPageProps {
    serverNow: string;
    filters: {
        range: '7d' | '30d' | '90d';
        compare: 'previous' | 'none';
        course: number | null;
        provider: string;
        model: string;
        operation: string;
        timezone: string;
        message_id: number | null;
    };
    filterOptions: {
        courses: AdminCourseOption[];
        providers: string[];
        models: string[];
        operations: string[];
    };
    summary: {
        requests: number;
        tokens: number;
        cost: number;
        successRate: number;
        latencyP50: number | null;
        latencyP95: number | null;
        succeeded: number;
        failed: number;
        currency: string;
        window: { from: string; to: string };
        comparison: Record<string, { value: number; delta: number }> | null;
    };
    comparison: Record<string, number> | null;
    trend: Array<{ label: string; requests: number; succeeded: number; cost: number; tokens: number }>;
    breakdown: Array<{ provider: string; model: string; operation: string; requests: number; successRate: number; tokens: number; cost: number; latencyAvg: number | null; helpfulness: { averageRating: number | null; sampleSize: number; positiveRate: number | null } }>;
    failures: Array<{ id: number; provider: string; model: string; operation: string; errorCode: string; message: string; occurredAt: string; href: string; actionHref: string }>;
    quality: { sampleSize: number; averageRating: number | null; positiveRate: number | null; citationCoverage: number | null; citationSampleSize: number; href: string };
    budget: { enabled: boolean; daily: number; currency: string };
    providerHealth: { provider: string; status: 'healthy' | 'degraded' | 'open'; failures: number; threshold: number; actionHref: string };
    dataState: 'ready' | 'degraded' | 'no_traffic' | 'outage';
}

export interface AdminAuditRecord {
    id: number;
    event: string;
    severity: 'critical' | 'warning' | 'info';
    actor: string;
    actorId: number | null;
    targetType: string | null;
    targetId: number | null;
    occurredAt: string | null;
    retentionExpiresAt: string | null;
    oldValues: Record<string, unknown> | null;
    newValues: Record<string, unknown> | null;
    detailHref: string;
}

export interface AdminOperationsProps extends SharedPageProps {
    filters: { range: '7d' | '30d' | '90d'; event: string; actor_id: number | null; target_type: string; target_id: number | null; severity: string; timezone: string; per_page: number };
    filterOptions: { actors: AdminFilterOption[]; targetTypes: AdminFilterOption[]; severities: AdminFilterOption[] };
    audits: { data: AdminAuditRecord[]; meta: { currentPage: number; lastPage: number; perPage: number; total: number }; links: { first: string; previous: string | null; next: string | null; last: string } };
    health: {
        generatedAt: string;
        status: 'healthy' | 'attention';
        queue: { depth: number; failed: number; href: string };
        webhooks: { failedLast7d: number; href: string };
        scheduler: { lastHealthCheck: string | null; status: string; command: string; runbookHref: string };
        retention: { lastRun: string | null; status: string; command: string; runbookHref: string; expired: { audit: number; examAttempts: number; examAnswers: number } };
        retentionPolicy: { auditMonths: number; examYears: number; earlyDeletion: string };
        provider: { provider: string; status: string; failures: number; href: string };
        links: { actionCenter: string; audit: string; runbook: string };
    };
}

export interface AdminAuditDetailProps extends SharedPageProps {
    audit: AdminAuditRecord;
}
