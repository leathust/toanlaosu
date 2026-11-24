<?php

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('sends a contextual tutor message and persists the assistant reply', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('student.chat.send'), ['content' => 'Vì sao khoảng cách đến radar giảm?']);

    $response->assertCreated()->assertJsonPath('assistant_message.role', 'assistant');
    expect(ChatThread::query()->where('user_id', $user->id)->count())->toBe(1);
    expect(ChatMessage::query()->where('role', 'assistant')->count())->toBe(1);
});

it('streams an owned assistant message and records feedback', function () {
    $user = User::factory()->create();
    $thread = ChatThread::factory()->create(['user_id' => $user->id]);
    $message = ChatMessage::factory()->create(['chat_thread_id' => $thread->id, 'role' => 'assistant', 'content' => 'Một câu trả lời có trích dẫn.']);

    $this->actingAs($user)->get(route('student.chat.stream', $message))->assertOk()->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');
    $this->actingAs($user)->postJson(route('student.chat.feedback', $message), ['rating' => 5, 'reason' => 'clear'])->assertOk()->assertJsonPath('data.rating', 5);
});

it('allows only safe scene commands for the thread owner', function () {
    $user = User::factory()->create();
    $thread = ChatThread::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->postJson(route('student.chat.command', $thread), ['command' => 'pause'])->assertOk()->assertJsonPath('accepted', true);
    $this->actingAs($user)->postJson(route('student.chat.command', $thread), ['command' => 'delete_all'])->assertUnprocessable();
    $this->actingAs(User::factory()->create())->postJson(route('student.chat.command', $thread), ['command' => 'pause'])->assertForbidden();
});
