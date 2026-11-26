<?php

namespace App\Data\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final readonly class AdminAuditFilters
{
    public function __construct(
        public string $range = '30d',
        public string $event = '',
        public ?int $actorId = null,
        public string $targetType = '',
        public ?int $targetId = null,
        public string $severity = '',
        public string $timezone = 'UTC',
        public int $perPage = 30,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $range = $request->string('range')->toString();
        $timezone = $request->string('timezone')->toString();

        return new self(
            range: in_array($range, ['7d', '30d', '90d'], true) ? $range : '30d',
            event: substr($request->string('event')->toString(), 0, 120),
            actorId: $request->integer('actor_id') > 0 ? $request->integer('actor_id') : null,
            targetType: substr($request->string('target_type')->toString(), 0, 120),
            targetId: $request->integer('target_id') > 0 ? $request->integer('target_id') : null,
            severity: in_array($request->string('severity')->toString(), ['critical', 'warning', 'info'], true) ? $request->string('severity')->toString() : '',
            timezone: in_array($timezone, timezone_identifiers_list(), true) ? $timezone : (string) config('app.timezone'),
            perPage: min(max($request->integer('per_page', 30), 10), 100),
        );
    }

    public function days(): int
    {
        return (int) rtrim($this->range, 'd');
    }

    public function start(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone)->startOfDay()->subDays($this->days() - 1)->utc();
    }

    public function end(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone)->endOfDay()->utc();
    }

    /** @return array<string, scalar|null> */
    public function toArray(): array
    {
        return ['range' => $this->range, 'event' => $this->event, 'actor_id' => $this->actorId, 'target_type' => $this->targetType, 'target_id' => $this->targetId, 'severity' => $this->severity, 'timezone' => $this->timezone, 'per_page' => $this->perPage];
    }

    public function cacheKey(): string
    {
        return 'admin-operations:health:v1:'.$this->timezone;
    }
}
