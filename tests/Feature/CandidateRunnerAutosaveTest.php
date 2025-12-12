<?php

declare(strict_types=1);

use App\Models\Answer;
use App\Models\Attempt;
use Livewire\Volt\Volt;

it('autosaves runner answers to the database', function () {
    $attempt = Attempt::factory()->create();

    $key = 's1_q1a_angry_billing';
    $value = 'My saved answer';

    Volt::test('candidate.runner', ['token' => $attempt->token])
        ->set("answers.{$key}", $value)
        ->call('autosave');

    expect(
        Answer::query()
            ->where('attempt_id', $attempt->id)
            ->where('question_key', $key)
            ->value('answer_value')
    )->toBe($value);

    $attempt->refresh();
    expect($attempt->last_activity_at)->not->toBeNull();
});
