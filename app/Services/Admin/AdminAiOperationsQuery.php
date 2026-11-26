<?php

namespace App\Services\Admin;

use App\Data\Admin\AdminAiFilters;
use App\Models\Course;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class AdminAiOperationsQuery
{
    /**
     * @return array<string, mixed>
     */
    public function overview(AdminAiFilters $filters): array
    {
        return Cache::remember($filters->cacheKey(), now()->addSeconds(15), function () use ($filters): array {
            $usage = $this->usageMetrics($filters, $filters->start(), $filters->end());
            $comparison = $filters->comparisonStart()
                ? $this->usageMetrics($filters, $filters->comparisonStart(), $filters->comparisonEnd())
                : null;
            $failures = $this->failures($filters);

            return [
                'serverNow' => now()->toIso8601String(),
                'summary' => $this->summary($usage, $comparison, $filters),
                'comparison' => $comparison ? $this->summary($comparison, null, $filters) : null,
                'trend' => $this->trend($filters),
                'breakdown' => $this->breakdown($filters),
                'failures' => $failures,
                'quality' => $this->quality($filters),
                'budget' => $this->budget(),
                'providerHealth' => $this->providerHealth(),
                'dataState' => $this->dataState($usage['total'], $usage['failed'], $failures),
                'filterOptions' => $this->filterOptions($filters),
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @param  array<string, mixed>|null  $comparison
     * @return array<string, mixed>
     */
    private function summary(array $metrics, ?array $comparison, AdminAiFilters $filters): array
    {
        $items = [
            'requests' => $metrics['total'],
            'tokens' => $metrics['tokens'],
            'cost' => $metrics['cost'],
            'successRate' => $metrics['successRate'],
            'latencyP50' => $metrics['latencyP50'],
            'latencyP95' => $metrics['latencyP95'],
        ];

        return [
            ...$items,
            'succeeded' => $metrics['succeeded'],
            'failed' => $metrics['failed'],
            'currency' => (string) config('services.ai.currency', 'USD'),
            'window' => ['from' => $filters->start()->toIso8601String(), 'to' => $filters->end()->toIso8601String()],
            'comparison' => $comparison ? collect($items)->mapWithKeys(function (mixed $value, string $key) use ($comparison): array {
                if ($value === null) {
                    return [$key => ['value' => null, 'delta' => null]];
                }

                $previous = (float) ($comparison[$this->metricKey($key)] ?? 0);

                return [$key => ['value' => $previous, 'delta' => round($value - $previous, 6)]];
            })->all() : null,
        ];
    }

    private function metricKey(string $key): string
    {
        return match ($key) {
            'requests' => 'total',
            'tokens' => 'tokens',
            'cost' => 'cost',
            'successRate' => 'successRate',
            'latencyP50' => 'latencyP50',
            'latencyP95' => 'latencyP95',
            default => $key,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function usageMetrics(AdminAiFilters $filters, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $query = $this->usageQuery($filters, $from, $to);
        $aggregate = (clone $query)->selectRaw('COUNT(*) as total, SUM(CASE WHEN ai_usage_logs.status = ? THEN 1 ELSE 0 END) as succeeded, SUM(CASE WHEN ai_usage_logs.status != ? THEN 1 ELSE 0 END) as failed, COALESCE(SUM(ai_usage_logs.prompt_tokens), 0) as prompt_tokens, COALESCE(SUM(ai_usage_logs.completion_tokens), 0) as completion_tokens, COALESCE(SUM(ai_usage_logs.cost), 0) as cost', ['succeeded', 'succeeded'])->first();
        $latencies = (clone $query)->whereNotNull('ai_usage_logs.latency_ms')->orderBy('ai_usage_logs.latency_ms')->limit(10000)->pluck('ai_usage_logs.latency_ms')->map(fn (mixed $latency): float => (float) $latency)->values();

        return [
            'total' => (int) ($aggregate->total ?? 0),
            'succeeded' => (int) ($aggregate->succeeded ?? 0),
            'failed' => (int) ($aggregate->failed ?? 0),
            'promptTokens' => (int) ($aggregate->prompt_tokens ?? 0),
            'completionTokens' => (int) ($aggregate->completion_tokens ?? 0),
            'tokens' => (int) ($aggregate->prompt_tokens ?? 0) + (int) ($aggregate->completion_tokens ?? 0),
            'cost' => round((float) ($aggregate->cost ?? 0), 6),
            'successRate' => ($aggregate->total ?? 0) > 0 ? round(((int) $aggregate->succeeded / (int) $aggregate->total) * 100, 2) : 0.0,
            'latencyP50' => $this->percentile($latencies, 0.5),
            'latencyP95' => $this->percentile($latencies, 0.95),
        ];
    }

    private function usageQuery(AdminAiFilters $filters, CarbonImmutable $from, CarbonImmutable $to): Builder
    {
        return DB::table('ai_usage_logs')
            ->leftJoin('chat_messages', 'chat_messages.id', '=', 'ai_usage_logs.chat_message_id')
            ->leftJoin('chat_threads', 'chat_threads.id', '=', 'chat_messages.chat_thread_id')
            ->whereBetween('ai_usage_logs.occurred_at', [$from, $to])
            ->when($filters->messageId, fn (Builder $query, int $messageId) => $query->where('chat_messages.id', $messageId))
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('chat_threads.course_id', $courseId))
            ->when($filters->provider !== '', fn (Builder $query) => $query->where('ai_usage_logs.provider', $filters->provider))
            ->when($filters->model !== '', fn (Builder $query) => $query->where('ai_usage_logs.model', $filters->model))
            ->when($filters->operation !== '', fn (Builder $query) => $query->where('ai_usage_logs.operation', $filters->operation));
    }

    private function percentile(Collection $values, float $percentile): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }

        $index = (int) ceil($values->count() * $percentile) - 1;

        return round((float) $values->get(max(0, $index)), 2);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function trend(AdminAiFilters $filters): array
    {
        $rows = $this->usageQuery($filters, $filters->start(), $filters->end())
            ->select('ai_usage_logs.occurred_at', 'ai_usage_logs.status', 'ai_usage_logs.cost', 'ai_usage_logs.prompt_tokens', 'ai_usage_logs.completion_tokens')
            ->orderBy('ai_usage_logs.occurred_at')
            ->limit(10000)
            ->get();
        $grouped = $rows->groupBy(fn (object $row): string => CarbonImmutable::parse($row->occurred_at)->setTimezone($filters->timezone)->toDateString());
        $trend = [];
        for ($date = $filters->start()->setTimezone($filters->timezone)->startOfDay(); $date->lessThanOrEqualTo($filters->end()->setTimezone($filters->timezone)->startOfDay()); $date = $date->addDay()) {
            $bucket = $grouped->get($date->toDateString(), collect());
            $trend[] = [
                'label' => $date->toDateString(),
                'requests' => $bucket->count(),
                'succeeded' => $bucket->where('status', 'succeeded')->count(),
                'cost' => round((float) $bucket->sum(fn (object $row): float => (float) ($row->cost ?? 0)), 6),
                'tokens' => (int) $bucket->sum(fn (object $row): int => (int) ($row->prompt_tokens ?? 0) + (int) ($row->completion_tokens ?? 0)),
            ];
        }

        return $trend;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function breakdown(AdminAiFilters $filters): array
    {
        $rows = $this->usageQuery($filters, $filters->start(), $filters->end())
            ->selectRaw('ai_usage_logs.provider, ai_usage_logs.model, ai_usage_logs.operation, COUNT(*) as total, SUM(CASE WHEN ai_usage_logs.status = ? THEN 1 ELSE 0 END) as succeeded, COALESCE(SUM(ai_usage_logs.prompt_tokens), 0) as prompt_tokens, COALESCE(SUM(ai_usage_logs.completion_tokens), 0) as completion_tokens, COALESCE(SUM(ai_usage_logs.cost), 0) as cost, AVG(ai_usage_logs.latency_ms) as latency_avg', ['succeeded'])
            ->groupBy('ai_usage_logs.provider', 'ai_usage_logs.model', 'ai_usage_logs.operation')
            ->orderByDesc('total')
            ->limit(50)
            ->get();
        $helpfulness = $this->dimensionHelpfulness($filters)->keyBy(fn (object $row): string => $this->dimensionKey($row->provider, $row->model, $row->operation));

        return $rows->map(function (object $row) use ($helpfulness): array {
            $quality = $helpfulness->get($this->dimensionKey($row->provider, $row->model, $row->operation));

            return [
                'provider' => $row->provider,
                'model' => $row->model,
                'operation' => $row->operation,
                'requests' => (int) $row->total,
                'successRate' => $row->total > 0 ? round(((int) $row->succeeded / (int) $row->total) * 100, 2) : 0.0,
                'tokens' => (int) $row->prompt_tokens + (int) $row->completion_tokens,
                'cost' => round((float) $row->cost, 6),
                'latencyAvg' => $row->latency_avg === null ? null : round((float) $row->latency_avg, 2),
                'helpfulness' => [
                    'averageRating' => $quality?->average_rating === null ? null : round((float) $quality->average_rating, 2),
                    'sampleSize' => (int) ($quality->sample_size ?? 0),
                    'positiveRate' => ($quality?->sample_size ?? 0) > 0 ? round(((int) $quality->positive / (int) $quality->sample_size) * 100, 2) : null,
                ],
            ];
        })->values()->all();
    }

    /** @return Collection<int, object> */
    private function dimensionHelpfulness(AdminAiFilters $filters): Collection
    {
        return $this->usageQuery($filters, $filters->start(), $filters->end())
            ->leftJoin('ai_feedback', 'ai_feedback.chat_message_id', '=', 'ai_usage_logs.chat_message_id')
            ->selectRaw('ai_usage_logs.provider, ai_usage_logs.model, ai_usage_logs.operation, COUNT(DISTINCT ai_feedback.id) as sample_size, AVG(ai_feedback.rating) as average_rating, SUM(CASE WHEN ai_feedback.rating >= 4 THEN 1 ELSE 0 END) as positive')
            ->groupBy('ai_usage_logs.provider', 'ai_usage_logs.model', 'ai_usage_logs.operation')
            ->get();
    }

    private function dimensionKey(string $provider, string $model, string $operation): string
    {
        return implode("\x1F", [$provider, $model, $operation]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function failures(AdminAiFilters $filters): array
    {
        return $this->usageQuery($filters, $filters->start(), $filters->end())
            ->where('ai_usage_logs.status', '!=', 'succeeded')
            ->select('ai_usage_logs.id', 'ai_usage_logs.chat_message_id', 'ai_usage_logs.provider', 'ai_usage_logs.model', 'ai_usage_logs.operation', 'ai_usage_logs.error_code', 'ai_usage_logs.occurred_at')
            ->latest('ai_usage_logs.occurred_at')
            ->limit(50)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'provider' => $row->provider,
                'model' => $row->model,
                'operation' => $row->operation,
                'errorCode' => $row->error_code ?: 'provider_error',
                'message' => $this->redactedMessage((string) ($row->error_code ?? '')),
                'occurredAt' => CarbonImmutable::parse($row->occurred_at)->toIso8601String(),
                'href' => $row->chat_message_id ? route('admin.ai', ['message_id' => $row->chat_message_id]) : route('admin.ai'),
                'actionHref' => route('admin.action-center', ['source' => 'ai_provider']),
            ])->values()->all();
    }

    private function redactedMessage(string $errorCode): string
    {
        return match (true) {
            str_contains($errorCode, 'timeout') => 'Provider request timed out.',
            str_contains($errorCode, 'budget') => 'AI budget guard blocked the request.',
            str_contains($errorCode, 'circuit') => 'Provider circuit breaker is open.',
            str_contains($errorCode, 'rate') => 'Provider rate limit reached.',
            default => 'Provider request failed.',
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function quality(AdminAiFilters $filters): array
    {
        $feedback = DB::table('ai_feedback')
            ->join('chat_messages', 'chat_messages.id', '=', 'ai_feedback.chat_message_id')
            ->join('chat_threads', 'chat_threads.id', '=', 'chat_messages.chat_thread_id')
            ->whereBetween('ai_feedback.created_at', [$filters->start(), $filters->end()])
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('chat_threads.course_id', $courseId))
            ->selectRaw('COUNT(*) as total, AVG(ai_feedback.rating) as average_rating, SUM(CASE WHEN ai_feedback.rating >= 4 THEN 1 ELSE 0 END) as positive')
            ->first();
        $assistant = DB::table('chat_messages')
            ->join('chat_threads', 'chat_threads.id', '=', 'chat_messages.chat_thread_id')
            ->where('chat_messages.role', 'assistant')
            ->whereBetween('chat_messages.created_at', [$filters->start(), $filters->end()])
            ->when($filters->courseId, fn (Builder $query, int $courseId) => $query->where('chat_threads.course_id', $courseId));
        $assistantTotal = (clone $assistant)->count();
        $citationTotal = (clone $assistant)->whereExists(fn (Builder $query) => $query->selectRaw('1')->from('chat_message_sources')->whereColumn('chat_message_sources.chat_message_id', 'chat_messages.id'))->count();
        $firstFeedback = DB::table('ai_feedback')->whereBetween('created_at', [$filters->start(), $filters->end()])->orderBy('id')->value('chat_message_id');

        return [
            'sampleSize' => (int) ($feedback->total ?? 0),
            'averageRating' => $feedback?->average_rating === null ? null : round((float) $feedback->average_rating, 2),
            'positiveRate' => ($feedback->total ?? 0) > 0 ? round(((int) $feedback->positive / (int) $feedback->total) * 100, 2) : null,
            'citationCoverage' => $assistantTotal > 0 ? round(($citationTotal / $assistantTotal) * 100, 2) : null,
            'citationSampleSize' => $assistantTotal,
            'href' => $firstFeedback ? route('admin.ai', ['message_id' => $firstFeedback]) : route('admin.ai'),
        ];
    }

    /** @return array{enabled: bool, daily: int, currency: string} */
    private function budget(): array
    {
        $settings = DB::table('system_settings')->whereIn('key', ['ai.enabled', 'ai.daily_budget'])->pluck('value', 'key');

        return [
            'enabled' => filter_var($settings['ai.enabled'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'daily' => (int) ($settings['ai.daily_budget'] ?? 0),
            'currency' => (string) config('services.ai.currency', 'USD'),
        ];
    }

    /** @return array<string, mixed> */
    private function providerHealth(): array
    {
        $failures = (int) Cache::get('ai:circuit:failures', 0);
        $open = (bool) Cache::get('ai:circuit:open', false);
        $threshold = (int) config('services.ai.circuit_breaker_threshold', 3);

        return [
            'provider' => (string) config('services.ai.provider', 'local'),
            'status' => $open ? 'open' : ($failures > 0 ? 'degraded' : 'healthy'),
            'failures' => $failures,
            'threshold' => $threshold,
            'actionHref' => route('admin.action-center', ['source' => 'ai_provider']),
        ];
    }

    private function dataState(int $total, int $failed, array $failures): string
    {
        if ($total === 0) {
            return Cache::get('ai:circuit:open', false) || $failures !== [] ? 'outage' : 'no_traffic';
        }

        return $failed > 0 && $failed >= $total / 2 ? 'degraded' : 'ready';
    }

    /** @return array<string, mixed> */
    private function filterOptions(AdminAiFilters $filters): array
    {
        $query = $this->usageQuery($filters, $filters->start(), $filters->end());

        return [
            'courses' => Course::query()->orderBy('title')->get(['id', 'title'])->map(fn (Course $course): array => ['id' => $course->id, 'title' => $course->title])->all(),
            'providers' => (clone $query)->distinct()->orderBy('ai_usage_logs.provider')->pluck('ai_usage_logs.provider')->values()->all(),
            'models' => (clone $query)->distinct()->orderBy('ai_usage_logs.model')->pluck('ai_usage_logs.model')->values()->all(),
            'operations' => (clone $query)->distinct()->orderBy('ai_usage_logs.operation')->pluck('ai_usage_logs.operation')->values()->all(),
        ];
    }
}
