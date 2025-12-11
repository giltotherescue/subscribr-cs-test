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

        // Get valid question keys from config
        $validKeys = collect(config('assessment.sections'))
            ->flatMap(fn ($section) => collect($section['questions'])->pluck('key'))
            ->toArray();

        $rows = [];
        foreach ($this->answers as $key => $value) {
            // Only save answers for valid question keys
            if (! in_array($key, $validKeys, true)) {
                continue;
            }
            $rows[] = [
                'attempt_id' => $this->attempt->id,
                'question_key' => $key,
                'answer_value' => is_string($value) ? $value : null,
                'updated_at' => $now,
                'created_at' => $now,
            ];
        }

        if (! empty($rows)) {
            Answer::upsert(
                $rows,
                ['attempt_id', 'question_key'],
                ['answer_value', 'updated_at']
            );
        }

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
        sessionId: localStorage.getItem('assessment_session_id') || null,

        generateUUID() {
            if (typeof crypto !== 'undefined' && crypto.randomUUID) {
                return crypto.randomUUID();
            }
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                const r = Math.random() * 16 | 0;
                const v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },

        init() {
            if (!this.sessionId) {
                this.sessionId = this.generateUUID();
                localStorage.setItem('assessment_session_id', this.sessionId);
            }
            $wire.setSessionId(this.sessionId);

            $wire.on('autosaved', (event) => {
                this.lastSavedAt = event.at;
                this.dirty = false;
                this.saveError = false;
            });
        }
    }"
    x-on:input="dirty = true"
    wire:poll.15s="autosave"
>
    @if($attempt->isSubmitted())
        <div class="mb-8 assessment-card p-5 border-l-4 border-l-amber-400">
            <div class="flex items-center gap-3">
                <svg class="size-5 text-amber-600 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                <div class="flex-1">
                    <p class="font-medium text-sand-800">This assessment has been submitted.</p>
                </div>
                <a href="{{ route('attempt.done', $attempt->token) }}" class="text-sm font-medium text-primary-600 hover:text-primary-700 underline underline-offset-2">
                    View submission →
                </a>
            </div>
        </div>
    @endif

    {{-- Header --}}
    <header class="mb-10">
        <div class="assessment-card header-card p-6">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="section-title text-2xl">{{ config('assessment.title') }}</h1>
                    <p class="text-sand-600 mt-2">{{ $attempt->candidate_name }} · {{ $attempt->candidate_email }}</p>
                </div>
                <div>
                    <template x-if="dirty">
                        <span class="save-badge save-badge-saving">
                            <span class="size-1.5 rounded-full bg-amber-500 pulse-dot"></span>
                            Saving...
                        </span>
                    </template>
                    <template x-if="!dirty && !saveError">
                        <span class="save-badge save-badge-saved">
                            <svg class="size-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                            Saved
                        </span>
                    </template>
                    <template x-if="saveError">
                        <span class="save-badge save-badge-error">
                            Error saving
                        </span>
                    </template>
                </div>
            </div>

            @if($showMultiTabWarning)
                <div class="mt-4 p-3 rounded-lg bg-amber-50 border border-amber-200 text-sm text-amber-800">
                    <strong>Warning:</strong> This assessment is open in another tab.
                </div>
            @endif

            <p class="mt-4 text-sm text-sand-500">
                Your work saves automatically. Bookmark this page if you need to take a break.
            </p>
        </div>
    </header>

    {{-- Questions --}}
    <form wire:submit="submit" class="space-y-10">
        @foreach(config('assessment.sections') as $sectionIndex => $section)
            <section class="assessment-card p-8">
                {{-- Section header --}}
                <div class="section-header mb-8">
                    <div class="section-number">{{ $sectionIndex + 1 }}</div>
                    <h2 class="section-title">{{ preg_replace('/^\d+\.\s*/', '', $section['title']) }}</h2>
                    @if($section['description'])
                        <p class="text-sand-600 mt-2 text-base">{{ $section['description'] }}</p>
                    @endif
                </div>

                {{-- Questions in this section --}}
                <div class="space-y-0">
                    @foreach($section['questions'] as $questionIndex => $question)
                        <div wire:key="q-{{ $question['key'] }}">
                            @if($questionIndex > 0)
                                <div class="question-divider"></div>
                            @endif

                            {{-- Question label --}}
                            <label for="q-{{ $question['key'] }}" class="question-label block mb-1">
                                {{ $question['title'] }}
                            </label>

                            {{-- Question prompt --}}
                            <div class="question-prompt whitespace-pre-wrap">{{ $question['prompt'] }}</div>

                            {{-- Answer textarea --}}
                            <textarea
                                id="q-{{ $question['key'] }}"
                                wire:model="answers.{{ $question['key'] }}"
                                rows="{{ $question['rows'] ?? 8 }}"
                                placeholder="Write your answer here..."
                                @if($attempt->isSubmitted()) disabled @endif
                                class="answer-textarea @if(isset($validationErrors[$question['key']])) !border-red-400 @endif"
                            ></textarea>

                            @if(isset($validationErrors[$question['key']]))
                                <p class="mt-2 text-sm text-red-600 font-medium">{{ $validationErrors[$question['key']] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        @if(!$attempt->isSubmitted())
            <div class="assessment-card p-6 flex items-center justify-between gap-4">
                <p class="text-sand-600">
                    Ready to submit your assessment?
                </p>
                <button type="submit" class="submit-button">
                    Submit Assessment
                </button>
            </div>
        @endif
    </form>

    {{-- Floating help center callout --}}
    <a
        href="https://subscribr.ai/help"
        target="_blank"
        rel="noopener"
        class="fixed top-4 right-4 flex items-center gap-2 rounded-lg bg-primary-500 px-4 py-2 text-sm font-medium text-white shadow-lg shadow-primary-500/25 transition hover:bg-primary-600 hover:shadow-xl hover:shadow-primary-500/30"
    >
        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
        </svg>
        Help Center
        <svg class="size-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
        </svg>
    </a>
</div>
