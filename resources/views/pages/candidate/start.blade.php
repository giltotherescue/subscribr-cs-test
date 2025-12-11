<?php

use App\Models\Attempt;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;

new #[Layout('components.layouts.app')] class extends Component {
    #[Validate('required|string|max:200')]
    public string $name = '';

    #[Validate('required|email:rfc,dns|max:254')]
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

<div class="space-y-8">
    <div class="text-center">
        <flux:heading size="xl" level="1">Subscribr — Customer Support Lead Assessment</flux:heading>
    </div>

    <flux:card>
        <div class="prose prose-zinc dark:prose-invert max-w-none">
            <p>Thanks for taking the time to complete this assessment.</p>
            <ul>
                <li>Expected time: <strong>60–75 minutes</strong></li>
                <li>Please do this in <strong>one sitting if possible</strong></li>
                <li>You may reference the Subscribr Help Center: <a href="https://subscribr.ai/help" target="_blank" rel="noopener">https://subscribr.ai/help</a></li>
                <li>You may use AI tools (ChatGPT, Claude, etc.) and web search as you normally would</li>
                <li>Please do not paste AI output directly without editing — we want to see your thinking</li>
            </ul>
            <p>Enter your name and email to begin. You'll receive a private link you can use to resume later.</p>
        </div>
    </flux:card>

    <flux:card>
        <form wire:submit="start" class="space-y-6">
            <flux:input
                wire:model="name"
                label="Full name"
                placeholder="Your full name"
            />

            <flux:input
                wire:model="email"
                type="email"
                label="Email address"
                placeholder="you@example.com"
            />

            <flux:button type="submit" variant="primary" class="w-full">
                Start assessment
            </flux:button>
        </form>
    </flux:card>
</div>
