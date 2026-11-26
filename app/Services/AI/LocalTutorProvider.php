<?php

namespace App\Services\AI;

use App\Contracts\AI\GenerativeProvider;
use Illuminate\Support\Str;

class LocalTutorProvider implements GenerativeProvider
{
    public function complete(string $prompt, array $sources): array
    {
        $citation = $sources === [] ? '' : ' [1]';

        return [
            'content' => 'Hãy phân tích câu hỏi theo từng bước và đối chiếu với kiến thức đã chọn: '.Str::limit($prompt, 180).$citation,
            'model' => 'local-tutor-v1',
            'provider' => 'local',
            'prompt_tokens' => max(1, (int) ceil(mb_strlen($prompt) / 4)),
            'completion_tokens' => max(1, (int) ceil(mb_strlen($prompt) / 8)),
            'citations' => array_map(static fn (array $source): int => $source['id'], $sources),
        ];
    }
}
