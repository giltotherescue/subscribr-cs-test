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

<div class="max-w-xl mx-auto">
    {{-- Success header --}}
    <div class="text-center mb-10">
        <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-green-100 mb-6">
            <svg class="size-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>
        <h1 class="section-title text-3xl">You're done!</h1>
        <p class="mt-3 text-sand-600 text-lg">Your assessment has been submitted successfully.</p>
    </div>

    {{-- Submission details --}}
    <div class="assessment-card p-8">
        <h2 class="question-label text-lg mb-4">Submission Details</h2>
        <dl class="space-y-0 divide-y divide-sand-200">
            <div class="flex justify-between py-3">
                <dt class="text-sand-500">Candidate</dt>
                <dd class="font-medium text-sand-900">{{ $attempt->candidate_name }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sand-500">Email</dt>
                <dd class="font-medium text-sand-900">{{ $attempt->candidate_email }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sand-500">Started</dt>
                <dd class="font-medium text-sand-900">{{ $attempt->started_at?->format('M j, Y g:i A') ?? '-' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sand-500">Completed</dt>
                <dd class="font-medium text-sand-900">{{ $attempt->completed_at?->format('M j, Y g:i A') ?? '-' }}</dd>
            </div>
            <div class="flex justify-between py-3">
                <dt class="text-sand-500">Duration</dt>
                <dd class="font-semibold text-primary-600 font-mono">{{ $this->durationFormatted }}</dd>
            </div>
        </dl>
    </div>

    <p class="text-center text-sm text-sand-500 mt-8">
        You may close this tab. We'll be in touch soon.
    </p>
</div>
