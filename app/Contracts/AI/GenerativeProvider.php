<?php

namespace App\Contracts\AI;

interface GenerativeProvider
{
    /**
     * @param  array<int, array{id:int,title:string,content:string}>  $sources
     * @return array{content:string,model:string,provider:string,prompt_tokens:int,completion_tokens:int,citations:array<int,int>}
     */
    public function complete(string $prompt, array $sources): array;
}
