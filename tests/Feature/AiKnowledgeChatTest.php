<?php

use App\Models\AiFeedback;
use App\Models\AiKnowledgeChunk;
use App\Models\AiKnowledgeDocument;
use App\Models\AiUsageLog;
use App\Models\ChatMessage;
use App\Models\ChatMessageSource;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('links knowledge chunks to citations without requiring vector storage', function () {
    $document = AiKnowledgeDocument::factory()->create([
        'source_type' => 'lesson',
        'source_id' => 10,
        'source_version' => 2,
    ]);
    $chunk = AiKnowledgeChunk::factory()->for($document, 'document')->create([
        'external_vector_id' => null,
        'content' => 'Radar detection depends on distance.',
    ]);
    $thread = ChatThread::factory()->create();
    $message = ChatMessage::factory()->for($thread, 'thread')->create(['role' => 'assistant']);
    $source = ChatMessageSource::factory()->for($message, 'message')->for($chunk, 'chunk')->create([
        'citation_order' => 1,
        'label' => 'Lesson 1',
    ]);

    expect($document->fresh()->chunks->first()->is($chunk))->toBeTrue()
        ->and($message->fresh()->sources->first()->is($source))->toBeTrue()
        ->and($source->fresh()->chunk->is($chunk))->toBeTrue()
        ->and($chunk->fresh()->external_vector_id)->toBeNull();
});

it('stores threaded chat messages, usage and feedback', function () {
    $user = User::factory()->create();
    $thread = ChatThread::factory()->for($user)->create();
    $parent = ChatMessage::factory()->for($thread, 'thread')->create(['role' => 'user']);
    $reply = ChatMessage::factory()->for($thread, 'thread')->for($parent, 'parent')->create(['role' => 'assistant']);
    $usage = AiUsageLog::factory()->for($user)->for($reply, 'chatMessage')->create();
    $feedback = AiFeedback::factory()->for($reply, 'chatMessage')->for($user)->create(['rating' => 5]);

    expect($reply->fresh()->parent->is($parent))->toBeTrue()
        ->and($thread->fresh()->messages)->toHaveCount(2)
        ->and($usage->fresh()->chatMessage->is($reply))->toBeTrue()
        ->and($feedback->fresh()->chatMessage->is($reply))->toBeTrue();
});

it('prevents duplicate feedback from one user on a chat message', function () {
    $user = User::factory()->create();
    $message = ChatMessage::factory()->create();

    AiFeedback::factory()->for($message, 'chatMessage')->for($user)->create();

    expect(fn () => AiFeedback::factory()->for($message, 'chatMessage')->for($user)->create())
        ->toThrow(QueryException::class);
});
