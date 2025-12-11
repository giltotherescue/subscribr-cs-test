<?php

use App\Models\Attempt;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public Attempt $attempt;

    public function mount(string $token): void
    {
        $this->attempt = Attempt::where('token', $token)->firstOrFail();

        // Redirect back to runner if not submitted
        if ($this->attempt->isInProgress()) {
            $this->redirect(route('attempt', $token), navigate: true);
        }
    }

    public function getDurationFormattedProperty(): string
    {
        $seconds = $this->attempt->duration_seconds ?? 0;
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        if ($h > 0) {
            return sprintf('%d:%02d:%02d', $h, $m, $s);
        }
        return sprintf('%d:%02d', $m, $s);
    }
}; ?>

<div class="space-y-8">
    <div class="text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-lime-100 dark:bg-lime-900">
            <flux:icon.check class="size-8 text-lime-600 dark:text-lime-400" />
        </div>
        <flux:heading size="xl" level="1" class="mt-4">You're done!</flux:heading>
        <flux:text class="mt-2 text-lg">Thanks — your assessment has been submitted successfully.</flux:text>
    </div>

    <x-card>
        <flux:heading>Submission Details</flux:heading>
        <dl class="mt-4 space-y-3">
            <div class="flex justify-between">
                <flux:text class="text-zinc-500 dark:text-zinc-400">Candidate</flux:text>
                <flux:text class="font-medium">{{ $attempt->candidate_name }}</flux:text>
            </div>
            <flux:separator />
            <div class="flex justify-between">
                <flux:text class="text-zinc-500 dark:text-zinc-400">Email</flux:text>
                <flux:text class="font-medium">{{ $attempt->candidate_email }}</flux:text>
            </div>
            <flux:separator />
            <div class="flex justify-between">
                <flux:text class="text-zinc-500 dark:text-zinc-400">Started</flux:text>
                <flux:text class="font-medium">{{ $attempt->started_at->format('M j, Y g:i A') }}</flux:text>
            </div>
            <flux:separator />
            <div class="flex justify-between">
                <flux:text class="text-zinc-500 dark:text-zinc-400">Completed</flux:text>
                <flux:text class="font-medium">{{ $attempt->completed_at->format('M j, Y g:i A') }}</flux:text>
            </div>
            <flux:separator />
            <div class="flex justify-between">
                <flux:text class="text-zinc-500 dark:text-zinc-400">Duration</flux:text>
                <flux:text class="font-medium">{{ $this->durationFormatted }}</flux:text>
            </div>
        </dl>
    </x-card>

    <div class="text-center">
        <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">
            You may close this tab.
        </flux:text>
    </div>
</div>
