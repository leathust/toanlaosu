<?php

namespace App\Data\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

final readonly class AdminAiFilters
{
    public function __construct(
        public string $range = '30d',
        public string $compare = 'previous',
        public ?int $courseId = null,
        public string $provider = '',
        public string $model = '',
        public string $operation = '',
        public string $timezone = 'Asia/Ho_Chi_Minh',
        public ?int $messageId = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $timezone = $request->string('timezone')->toString();

        return new self(
            range: in_array($request->string('range')->toString(), ['7d', '30d', '90d'], true) ? $request->string('range')->toString() : '30d',
            compare: in_array($request->string('compare')->toString(), ['previous', 'none'], true) ? $request->string('compare')->toString() : 'previous',
            courseId: $request->integer('course') > 0 ? $request->integer('course') : null,
            provider: substr($request->string('provider')->toString(), 0, 80),
            model: substr($request->string('model')->toString(), 0, 100),
            operation: substr($request->string('operation')->toString(), 0, 80),
            timezone: in_array($timezone, timezone_identifiers_list(), true) ? $timezone : (string) config('app.timezone'),
            messageId: $request->integer('message_id') > 0 ? $request->integer('message_id') : null,
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
        return $this->compare === 'none' ? null : $this->start()->subDays($this->days());
    }

    public function comparisonEnd(): ?CarbonImmutable
    {
        return $this->comparisonStart()?->addDays($this->days())->subSecond();
    }

    public function cacheKey(): string
    {
        return implode(':', ['admin-ai', 'v1', $this->range, $this->compare, $this->courseId ?? 'all', $this->provider ?: 'all', $this->model ?: 'all', $this->operation ?: 'all', $this->messageId ?? 'all', str_replace('/', '_', $this->timezone)]);
    }
}
