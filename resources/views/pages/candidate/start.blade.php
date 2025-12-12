<?php

use App\Models\Attempt;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component {
    #[Validate('required|string|max:200')]
    public string $name = '';

    #[Validate('required|email|max:254')]
    public string $email = '';

    public function start(): void
    {
        $this->validate();

        $attempt = Attempt::create([
            'token' => Attempt::generateToken(),
            'assessment_version' => config('assessment.version'),
            'candidate_name' => $this->name,
            'candidate_email' => $this->email,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $this->redirect(route('attempt', $attempt->token), navigate: true);
    }
}; ?>

<div class="max-w-xl mx-auto">
    {{-- Header --}}
    <header class="text-center mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full border border-primary-200 bg-primary-50 text-primary-700 text-xs font-medium tracking-wide uppercase mb-4">
            Subscribr Assessment
        </div>
        <h1 class="font-display font-bold text-3xl sm:text-4xl text-sand-900 leading-tight">
            Customer Support Lead<br>
            <span class="text-primary-500">Assessment</span>
        </h1>
    </header>

    {{-- Instructions --}}
    <div class="assessment-card p-8 mb-8">
        <p class="text-sand-700 text-lg leading-relaxed mb-6">
            Thanks for taking the time to complete this assessment.
        </p>

        <ul class="space-y-4">
            <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-medium mt-0.5">1</span>
                <span class="text-sand-700">You will be evaluated on both <strong class="text-sand-900">quality and completion time</strong></span>
            </li>
            <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-medium mt-0.5">2</span>
                <span class="text-sand-700">Please complete in <strong class="text-sand-900">one sitting</strong> (expected 30-60 minutes)</span>
            </li>
            <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-medium mt-0.5">3</span>
                <span class="text-sand-700">You may reference the <a href="https://subscribr.ai/help" target="_blank" rel="noopener" class="text-primary-600 hover:text-primary-700 underline underline-offset-2 font-medium">Subscribr Help Center</a></span>
            </li>
            <li class="flex items-start gap-3">
                <span class="flex-shrink-0 w-6 h-6 rounded-full bg-primary-100 text-primary-600 flex items-center justify-center text-sm font-medium mt-0.5">4</span>
                <span class="text-sand-700">AI tools are fine for research, but all <strong class="text-sand-900">writing should be your own</strong></span>
            </li>
        </ul>

        <p class="text-sand-500 text-sm mt-6 pt-6 border-t border-sand-200">
            Your work saves automatically. You can bookmark your link to resume later.
        </p>
    </div>

    {{-- Form --}}
    <div class="assessment-card p-8">
        <form wire:submit="start" class="space-y-5">
            <div>
                <label for="name" class="question-label block mb-2">Full name</label>
                <input
                    type="text"
                    id="name"
                    wire:model="name"
                    placeholder="Your full name"
                    class="answer-textarea !min-h-0 !py-3"
                    required
                />
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="question-label block mb-2">Email address</label>
                <input
                    type="email"
                    id="email"
                    wire:model="email"
                    placeholder="you@example.com"
                    class="answer-textarea !min-h-0 !py-3"
                    required
                />
                @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="submit-button w-full mt-2">
                Start Assessment
            </button>
        </form>
    </div>
</div>
