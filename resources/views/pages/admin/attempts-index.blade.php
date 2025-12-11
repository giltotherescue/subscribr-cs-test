<?php

use App\Models\Attempt;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $sortBy = 'started_at';

    #[Url]
    public string $sortDir = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function sort(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDir = 'desc';
        }
    }

    public function with(): array
    {
        $query = Attempt::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('candidate_name', 'like', "%{$this->search}%")
                  ->orWhere('candidate_email', 'like', "%{$this->search}%");
            });
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $query->orderBy($this->sortBy, $this->sortDir);

        return [
            'attempts' => $query->paginate(20),
            'totalCount' => Attempt::count(),
            'submittedCount' => Attempt::where('status', 'submitted')->count(),
            'inProgressCount' => Attempt::where('status', 'in_progress')->count(),
        ];
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">Assessment Attempts</flux:heading>
            <flux:text class="mt-1">
                {{ $totalCount }} total &middot; {{ $submittedCount }} submitted &middot; {{ $inProgressCount }} in progress
            </flux:text>
        </div>
        <flux:button href="{{ route('admin.export.submissions') }}" icon="arrow-down-tray" variant="filled">
            Export CSV
        </flux:button>
    </div>

    <x-card>
        <div class="flex flex-wrap items-center gap-4 mb-6">
            <div class="flex-1 min-w-[200px]">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search by name or email..."
                    icon="magnifying-glass"
                />
            </div>
            <flux:select wire:model.live="status" placeholder="All statuses">
                <flux:select.option value="">All statuses</flux:select.option>
                <flux:select.option value="in_progress">In Progress</flux:select.option>
                <flux:select.option value="submitted">Submitted</flux:select.option>
            </flux:select>
        </div>

        @if($attempts->isEmpty())
            <div class="text-center py-12">
                <svg class="mx-auto size-12 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" />
                </svg>
                <flux:heading class="mt-4">No attempts yet</flux:heading>
                <flux:text class="mt-2">Attempts will appear here when candidates start the assessment.</flux:text>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead>
                        <tr class="text-left text-sm font-medium text-zinc-500 dark:text-zinc-400">
                            <th wire:click="sort('candidate_name')" class="px-3 py-3 cursor-pointer hover:text-zinc-700 dark:hover:text-zinc-200">
                                Name
                                @if($sortBy === 'candidate_name')
                                    <span class="ml-1">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-3 py-3">Email</th>
                            <th wire:click="sort('status')" class="px-3 py-3 cursor-pointer hover:text-zinc-700 dark:hover:text-zinc-200">
                                Status
                                @if($sortBy === 'status')
                                    <span class="ml-1">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th wire:click="sort('started_at')" class="px-3 py-3 cursor-pointer hover:text-zinc-700 dark:hover:text-zinc-200">
                                Started
                                @if($sortBy === 'started_at')
                                    <span class="ml-1">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th wire:click="sort('completed_at')" class="px-3 py-3 cursor-pointer hover:text-zinc-700 dark:hover:text-zinc-200">
                                Completed
                                @if($sortBy === 'completed_at')
                                    <span class="ml-1">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-3 py-3">Duration</th>
                            <th class="px-3 py-3">Reviewed</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($attempts as $attempt)
                            <tr wire:key="attempt-{{ $attempt->id }}" class="text-sm">
                                <td class="px-3 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $attempt->candidate_name }}</td>
                                <td class="px-3 py-3 text-zinc-600 dark:text-zinc-400">{{ $attempt->candidate_email }}</td>
                                <td class="px-3 py-3">
                                    @if($attempt->status === 'submitted')
                                        <flux:badge color="lime" size="sm">Submitted</flux:badge>
                                    @else
                                        <flux:badge color="amber" size="sm">In Progress</flux:badge>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-zinc-600 dark:text-zinc-400">{{ $attempt->started_at?->format('M j, g:i A') }}</td>
                                <td class="px-3 py-3 text-zinc-600 dark:text-zinc-400">{{ $attempt->completed_at?->format('M j, g:i A') ?? '-' }}</td>
                                <td class="px-3 py-3 text-zinc-600 dark:text-zinc-400">
                                    @if($attempt->duration_seconds)
                                        {{ floor($attempt->duration_seconds / 60) }}m {{ $attempt->duration_seconds % 60 }}s
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    @if($attempt->reviewed_at)
                                        <svg class="size-5 text-lime-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    @else
                                        <svg class="size-5 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <flux:button href="{{ route('admin.attempts.show', $attempt) }}" size="sm" variant="ghost">
                                        View
                                    </flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $attempts->links() }}
            </div>
        @endif
    </x-card>
</div>
