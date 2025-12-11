<?php

use App\Models\Attempt;
use App\Models\Answer;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public Attempt $attempt;
    public array $answers = [];
    public string $sessionId = '';
    public ?string $lastSavedAt = null;
    public bool $showMultiTabWarning = false;
    public array $validationErrors = [];

    public function mount(string $token): void
    {
        $this->attempt = Attempt::where('token', $token)->firstOrFail();

        // Load existing answers
        $existingAnswers = $this->attempt->answers->pluck('answer_value', 'question_key')->toArray();

        // Initialize all question keys with existing or empty values
        foreach (config('assessment.sections') as $section) {
            foreach ($section['questions'] as $question) {
                $this->answers[$question['key']] = $existingAnswers[$question['key']] ?? '';
            }
        }

        $this->lastSavedAt = $this->attempt->last_activity_at?->toIso8601String();
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function autosave(): void
    {
        if ($this->attempt->isSubmitted()) {
            return;
        }

        $now = now();

        $rows = [];
        foreach ($this->answers as $key => $value) {
            $rows[] = [
                'attempt_id' => $this->attempt->id,
                'question_key' => $key,
                'answer_value' => is_string($value) ? $value : null,
                'updated_at' => $now,
                'created_at' => $now,
            ];
        }

        Answer::upsert(
            $rows,
            ['attempt_id', 'question_key'],
            ['answer_value', 'updated_at']
        );

        // Check for multi-tab warning
        $this->showMultiTabWarning = false;
        if ($this->sessionId &&
            $this->attempt->active_session_id &&
            $this->attempt->active_session_id !== $this->sessionId &&
            $this->attempt->active_session_updated_at?->isAfter(now()->subSeconds(60))) {
            $this->showMultiTabWarning = true;
        }

        $this->attempt->forceFill([
            'last_activity_at' => $now,
            'active_session_id' => $this->sessionId ?: null,
            'active_session_updated_at' => $now,
        ])->save();

        $this->lastSavedAt = $now->toIso8601String();
        $this->dispatch('autosaved', at: $this->lastSavedAt);
    }

    public function submit(): void
    {
        if ($this->attempt->isSubmitted()) {
            return;
        }

        // Validate required questions
        $this->validationErrors = [];
        foreach (config('assessment.sections') as $section) {
            foreach ($section['questions'] as $question) {
                if ($question['required'] && empty(trim($this->answers[$question['key']] ?? ''))) {
                    $this->validationErrors[$question['key']] = 'This question is required.';
                }
            }
        }

        if (! empty($this->validationErrors)) {
            $this->dispatch('validation-failed');
            return;
        }

        // Save answers one final time
        $this->autosave();

        // Mark as submitted
        $completedAt = now();
        $this->attempt->forceFill([
            'status' => 'submitted',
            'completed_at' => $completedAt,
            'duration_seconds' => $completedAt->diffInSeconds($this->attempt->started_at),
        ])->save();

        $this->redirect(route('attempt.done', $this->attempt->token), navigate: true);
    }

    public function getElapsedSecondsProperty(): int
    {
        return now()->diffInSeconds($this->attempt->started_at);
    }

    public function getAnsweredCountProperty(): int
    {
        $count = 0;
        foreach (config('assessment.sections') as $section) {
            foreach ($section['questions'] as $question) {
                if (! empty(trim($this->answers[$question['key']] ?? ''))) {
                    $count++;
                }
            }
        }
        return $count;
    }

    public function getTotalRequiredProperty(): int
    {
        $count = 0;
        foreach (config('assessment.sections') as $section) {
            foreach ($section['questions'] as $question) {
                if ($question['required']) {
                    $count++;
                }
            }
        }
        return $count;
    }
}; ?>

<div
    x-data="{
        dirty: false,
        lastSavedAt: @js($lastSavedAt),
        saveError: false,
        elapsed: @js($this->elapsedSeconds),
        sessionId: localStorage.getItem('assessment_session_id') || null,

        init() {
            if (!this.sessionId) {
                this.sessionId = crypto.randomUUID();
                localStorage.setItem('assessment_session_id', this.sessionId);
            }
            $wire.setSessionId(this.sessionId);

            setInterval(() => this.elapsed++, 1000);

            $wire.on('autosaved', (event) => {
                this.lastSavedAt = event.at;
                this.dirty = false;
                this.saveError = false;
            });
        },

        formatDuration(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = seconds % 60;
            if (h > 0) {
                return h + ':' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            }
            return m + ':' + String(s).padStart(2, '0');
        },

        formatSavedTime(iso) {
            if (!iso) return 'Never';
            const date = new Date(iso);
            return date.toLocaleTimeString();
        }
    }"
    x-on:input="dirty = true"
    wire:poll.15s="autosave"
>
    @if($attempt->isSubmitted())
        <flux:callout variant="warning" icon="exclamation-triangle" class="mb-6">
            <flux:callout.heading>Already submitted</flux:callout.heading>
            <flux:callout.text>This assessment has already been submitted.</flux:callout.text>
            <x-slot name="actions">
                <flux:button href="{{ route('attempt.done', $attempt->token) }}" size="sm">View submission</flux:button>
            </x-slot>
        </flux:callout>
    @endif

    {{-- Header / Status bar --}}
    <div class="sticky top-0 z-10 mb-6">
        <flux:card class="!p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ config('assessment.title') }}</flux:heading>
                    <flux:text size="sm">{{ $attempt->candidate_name }} &middot; {{ $attempt->candidate_email }}</flux:text>
                </div>
                <div class="flex items-center gap-6 text-sm">
                    <div>
                        <flux:text size="sm"><span class="font-medium">Time:</span> <span x-text="formatDuration(elapsed)"></span></flux:text>
                    </div>
                    <div>
                        <flux:text size="sm"><span class="font-medium">Progress:</span> {{ $this->answeredCount }}/{{ $this->totalRequired }} required</flux:text>
                    </div>
                    <div>
                        <template x-if="dirty">
                            <flux:badge color="amber" size="sm">Saving soon...</flux:badge>
                        </template>
                        <template x-if="!dirty && !saveError">
                            <flux:badge color="lime" size="sm">Saved <span x-text="formatSavedTime(lastSavedAt)"></span></flux:badge>
                        </template>
                        <template x-if="saveError">
                            <flux:badge color="red" size="sm">Could not save</flux:badge>
                        </template>
                    </div>
                </div>
            </div>

            @if($showMultiTabWarning)
                <flux:callout variant="warning" icon="exclamation-triangle" class="mt-3" size="sm">
                    <flux:callout.text>This assessment is open in another tab. Changes may overwrite each other.</flux:callout.text>
                </flux:callout>
            @endif

            <flux:text size="xs" class="mt-3">
                <strong>Autosave is on.</strong> Your work saves automatically every ~15 seconds.
                Keep this link to resume: <code class="rounded bg-zinc-100 dark:bg-zinc-800 px-1">{{ url()->current() }}</code>
            </flux:text>
        </flux:card>
    </div>

    {{-- Questions --}}
    <form wire:submit="submit" class="space-y-8">
        @foreach(config('assessment.sections') as $sectionIndex => $section)
            <flux:card>
                <flux:heading size="lg">{{ $section['title'] }}</flux:heading>
                @if($section['description'])
                    <flux:text class="mt-1">{{ $section['description'] }}</flux:text>
                @endif

                <div class="mt-6 space-y-8">
                    @foreach($section['questions'] as $question)
                        <div wire:key="q-{{ $question['key'] }}">
                            <flux:field>
                                <flux:label>
                                    {{ $question['title'] }}
                                    @if($question['required'])
                                        <flux:badge color="red" size="sm" class="ml-2">Required</flux:badge>
                                    @endif
                                </flux:label>
                                <flux:text size="sm" class="mt-2 rounded-md bg-zinc-50 dark:bg-zinc-800 p-3 whitespace-pre-wrap">{{ $question['prompt'] }}</flux:text>
                                <flux:textarea
                                    wire:model.defer="answers.{{ $question['key'] }}"
                                    rows="{{ $question['rows'] ?? 8 }}"
                                    placeholder="Write your answer here..."
                                    :disabled="$attempt->isSubmitted()"
                                    :invalid="isset($validationErrors[$question['key']])"
                                />
                                @if(isset($validationErrors[$question['key']]))
                                    <flux:error>{{ $validationErrors[$question['key']] }}</flux:error>
                                @endif
                            </flux:field>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        @endforeach

        @if(!$attempt->isSubmitted())
            <flux:card>
                <div class="flex items-center justify-between">
                    <flux:text size="sm">
                        Make sure all required questions are answered before submitting.
                    </flux:text>
                    <flux:button type="submit" variant="primary">
                        Submit assessment
                    </flux:button>
                </div>
            </flux:card>
        @endif
    </form>
</div>
