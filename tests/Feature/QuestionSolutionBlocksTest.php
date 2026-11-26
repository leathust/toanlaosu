<?php

use App\Models\QuestionVersion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('stores flexible multimodal solution blocks on an immutable question version', function () {
    $version = QuestionVersion::factory()->create([
        'solution' => 'Tóm tắt lời giải.',
        'solution_blocks' => [
            ['type' => 'text', 'content' => 'Xét khoảng cách đến tâm radar.'],
            ['type' => 'math', 'latex' => 'd = \\sqrt{x^2 + y^2}'],
            ['type' => 'image', 'media_id' => null],
            ['type' => 'video', 'video_id' => null],
            ['type' => 'scene', 'scene_version_id' => null],
        ],
    ]);

    expect($version->fresh()->solution_blocks)->toHaveCount(5)
        ->and($version->fresh()->solution_blocks[1]['type'])->toBe('math')
        ->and($version->fresh()->solution_blocks[1]['latex'])->toContain('sqrt');
});
