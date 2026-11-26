<?php

namespace App\Data\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final readonly class AdminDashboardFilters
{
    public function __construct(
        public string $range = '30d',
        public string $compare = 'previous',
        public ?int $courseId = null,
        public string $timezone = 'Asia/Ho_Chi_Minh',
    ) {}

    public static function fromRequest(Request $request): self
    {
        $range = $request->string('range')->toString();
        $compare = $request->string('compare')->toString();
        $timezone = $request->string('timezone')->toString();

        return new self(
            range: in_array($range, ['7d', '30d', '90d'], true) ? $range : '30d',
            compare: in_array($compare, ['previous', 'none'], true) ? $compare : 'previous',
            courseId: $request->integer('course') > 0 ? $request->integer('course') : null,
            timezone: in_array($timezone, timezone_identifiers_list(), true) ? $timezone : (string) config('app.timezone'),
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

    public function comparisonStart(): ?CarbonImmutable
    {
        if ($this->compare === 'none') {
            return null;
        }

        return $this->start()->subDays($this->days());
    }

    public function comparisonEnd(): ?CarbonImmutable
    {
        return $this->comparisonStart()?->addDays($this->days())->subSecond();
    }

    public function cacheKey(): string
    {
        return implode(':', [
            'admin-dashboard',
            'v1',
            $this->range,
            $this->compare,
            $this->courseId ?? 'all',
            str_replace('/', '_', $this->timezone),
        ]);
    }
}
