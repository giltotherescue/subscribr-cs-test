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
            <flux:button href="{{ route('admin.attempts') }}" variant="ghost" icon="arrow-left" class="mb-2">
                Back to list
            </flux:button>
            <flux:heading size="xl" level="1">{{ $attempt->candidate_name }}</flux:heading>
            <flux:text>{{ $attempt->candidate_email }}</flux:text>
        </div>
        <div class="flex items-center gap-3">
            @if($attempt->reviewed_at)
                <flux:badge color="lime" size="lg">Reviewed {{ $attempt->reviewed_at->diffForHumans() }}</flux:badge>
                <flux:button wire:click="unmarkReviewed" variant="ghost" size="sm">Unmark</flux:button>
            @else
                <flux:button wire:click="markReviewed" variant="primary" icon="check">
                    Mark as Reviewed
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Metadata card --}}
    <x-card>
        <flux:heading>Submission Details</flux:heading>
        <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <flux:text size="sm" class="text-zinc-500">Status</flux:text>
                <div class="mt-1">
                    @if($attempt->status === 'submitted')
                        <flux:badge color="lime">Submitted</flux:badge>
                    @else
                        <flux:badge color="amber">In Progress</flux:badge>
                    @endif
                </div>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">Started</flux:text>
                <flux:text class="mt-1 font-medium">{{ $attempt->started_at?->format('M j, Y g:i A') }}</flux:text>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">Completed</flux:text>
                <flux:text class="mt-1 font-medium">{{ $attempt->completed_at?->format('M j, Y g:i A') ?? '-' }}</flux:text>
            </div>
            <div>
                <flux:text size="sm" class="text-zinc-500">Duration</flux:text>
                <flux:text class="mt-1 font-medium">{{ $this->durationFormatted }}</flux:text>
            </div>
        </div>
    </x-card>

    {{-- Admin notes --}}
    <x-card>
        <flux:heading>Admin Notes</flux:heading>
        <div class="mt-4">
            <flux:textarea
                wire:model="adminNotes"
                rows="4"
                placeholder="Add internal notes about this candidate..."
            />
            <div class="mt-3 flex items-center justify-between">
                <flux:text size="sm" class="text-zinc-500">These notes are only visible to admins.</flux:text>
                <flux:button wire:click="saveNotes" size="sm">
                    Save Notes
                </flux:button>
            </div>
        </div>
    </x-card>

    {{-- Answers by section --}}
    @foreach(config('assessment.sections') as $section)
        <x-card>
            <flux:heading size="lg">{{ $section['title'] }}</flux:heading>
            @if($section['description'])
                <flux:text class="mt-1">{{ $section['description'] }}</flux:text>
            @endif

            <div class="mt-6 space-y-8">
                @foreach($section['questions'] as $question)
                    <div>
                        <flux:heading class="text-base">{{ $question['title'] }}</flux:heading>
                        <flux:text size="sm" class="mt-1 text-zinc-500">{{ $question['prompt'] }}</flux:text>

                        <div class="mt-3 rounded-lg bg-zinc-50 dark:bg-zinc-800 p-4">
                            @if(!empty($this->answersMap[$question['key']] ?? ''))
                                <div class="whitespace-pre-wrap text-sm">{{ $this->answersMap[$question['key']] }}</div>
                            @else
                                <flux:text class="italic text-zinc-400">No answer provided</flux:text>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </x-card>
    @endforeach
</div>
