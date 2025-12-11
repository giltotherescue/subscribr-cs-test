<?php

use App\Models\Attempt;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public Attempt $attempt;
    public string $adminNotes = '';

    public function mount(Attempt $attempt): void
    {
        $this->attempt = $attempt->load('answers');
        $this->adminNotes = $attempt->admin_notes ?? '';
    }

    public function saveNotes(): void
    {
        $this->attempt->update([
            'admin_notes' => $this->adminNotes,
        ]);

        $this->dispatch('notes-saved');
    }

    public function markReviewed(): void
    {
        $this->attempt->update([
            'reviewed_at' => now(),
        ]);
    }

    public function unmarkReviewed(): void
    {
        $this->attempt->update([
            'reviewed_at' => null,
        ]);
    }

    public function getAnswersMapProperty(): array
    {
        return $this->attempt->answers->pluck('answer_value', 'question_key')->toArray();
    }

    public function getDurationFormattedProperty(): string
    {
        $seconds = $this->attempt->duration_seconds ?? 0;
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return sprintf('%dh %dm %ds', $h, $m, $s);
        }
        return sprintf('%dm %ds', $m, $s);
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.attempts') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors mb-3">
                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
                Back to list
            </a>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $attempt->candidate_name }}</h1>
            <p class="text-zinc-600 dark:text-zinc-300">{{ $attempt->candidate_email }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if($attempt->reviewed_at)
                <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-sm font-medium bg-lime-100 text-lime-800 dark:bg-lime-900/50 dark:text-lime-200">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    Reviewed {{ $attempt->reviewed_at->diffForHumans() }}
                </span>
                <button wire:click="unmarkReviewed" class="text-sm font-medium text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 transition-colors">
                    Unmark
                </button>
            @else
                <button wire:click="markReviewed" class="inline-flex items-center gap-2 bg-primary-500 hover:bg-primary-600 text-white font-semibold py-2.5 px-5 rounded-lg transition-colors shadow-md hover:shadow-lg">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                    Mark as Reviewed
                </button>
            @endif
        </div>
    </div>

    {{-- Metadata card --}}
    <x-card>
        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Submission Details</h2>
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Status</p>
                <div class="mt-1">
                    @if($attempt->status === 'submitted')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-lime-100 text-lime-800 dark:bg-lime-900/50 dark:text-lime-200">
                            Submitted
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200">
                            In Progress
                        </span>
                    @endif
                </div>
            </div>
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Started</p>
                <p class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $attempt->started_at?->format('M j, Y g:i A') }}</p>
            </div>
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Completed</p>
                <p class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $attempt->completed_at?->format('M j, Y g:i A') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Duration</p>
                <p class="mt-1 font-medium font-mono text-zinc-900 dark:text-white">{{ $this->durationFormatted }}</p>
            </div>
        </div>
    </x-card>

    {{-- Admin notes --}}
    <x-card>
        <h2 class="text-lg font-bold text-zinc-900 dark:text-white">Admin Notes</h2>
        <div class="mt-4">
            <textarea
                wire:model="adminNotes"
                rows="4"
                placeholder="Add internal notes about this candidate..."
                class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm placeholder:text-zinc-400"
            ></textarea>
            <div class="mt-3 flex items-center justify-between">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">These notes are only visible to admins.</p>
                <button wire:click="saveNotes" class="text-sm font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                    Save Notes
                </button>
            </div>
        </div>
    </x-card>

    {{-- Answers by section --}}
    @foreach(config('assessment.sections') as $sectionIndex => $section)
        <x-card>
            {{-- Section header with number --}}
            <div class="flex items-baseline gap-3 mb-4">
                <span class="flex-shrink-0 size-8 rounded-full bg-primary-500 text-white flex items-center justify-center text-sm font-bold">
                    {{ $sectionIndex + 1 }}
                </span>
                <div>
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $section['title'] }}</h2>
                    @if($section['description'])
                        <p class="text-zinc-600 dark:text-zinc-300 mt-1">{{ $section['description'] }}</p>
                    @endif
                </div>
            </div>

            <div class="space-y-8 mt-6">
                @foreach($section['questions'] as $question)
                    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-6 first:border-t-0 first:pt-0">
                        {{-- Question title --}}
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $question['title'] }}</h3>

                        {{-- Question prompt --}}
                        <div class="mt-2 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 p-3">
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">{{ $question['prompt'] }}</p>
                        </div>

                        {{-- Candidate's answer --}}
                        <div class="mt-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 mb-2">Answer</p>
                            <div class="rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-4 shadow-sm">
                                @if(!empty($this->answersMap[$question['key']] ?? ''))
                                    <div class="whitespace-pre-wrap text-base leading-relaxed text-zinc-800 dark:text-zinc-200">{{ $this->answersMap[$question['key']] }}</div>
                                @else
                                    <p class="italic text-zinc-400 dark:text-zinc-500">No answer provided</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endforeach
</div>
