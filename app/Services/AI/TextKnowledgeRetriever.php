<?php

namespace App\Services\AI;

use App\Models\AiKnowledgeChunk;
use Illuminate\Support\Collection;

class TextKnowledgeRetriever
{
    /**
     * @return Collection<int, AiKnowledgeChunk>
     */
    public function search(string $query, int $limit = 5): Collection
    {
        $terms = collect(preg_split('/\s+/u', mb_strtolower(trim($query)), -1, PREG_SPLIT_NO_EMPTY))
            ->filter(static fn (string $term): bool => mb_strlen($term) >= 3)
            ->take(8);

        if ($terms->isEmpty()) {
            return collect();
        }

        return AiKnowledgeChunk::query()
            ->with('document')
            ->whereHas('document', static fn ($query): object => $query->where('status', 'indexed'))
            ->where(function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query->orWhere('content', 'like', '%'.$term.'%');
                }
            })
            ->limit($limit)
            ->get();
    }
}
