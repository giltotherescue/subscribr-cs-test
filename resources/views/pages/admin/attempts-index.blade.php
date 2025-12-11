<?php

use App\Models\Attempt;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

new #[Layout('components.layouts.app')] class extends Component {
    use WithPagination;

    private const ALLOWED_SORT_COLUMNS = ['candidate_name', 'status', 'started_at', 'completed_at'];

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
        if (! in_array($column, self::ALLOWED_SORT_COLUMNS, true)) {
            return;
        }

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
            $escapedSearch = str_replace(['%', '_'], ['\%', '\_'], $this->search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('candidate_name', 'like', "%{$escapedSearch}%")
                  ->orWhere('candidate_email', 'like', "%{$escapedSearch}%");
            });
        }

        if ($this->status && in_array($this->status, ['in_progress', 'submitted'], true)) {
            $query->where('status', $this->status);
        }

        // Validate sort parameters
        $sortColumn = in_array($this->sortBy, self::ALLOWED_SORT_COLUMNS, true) ? $this->sortBy : 'started_at';
        $sortDirection = in_array($this->sortDir, ['asc', 'desc'], true) ? $this->sortDir : 'desc';
        $query->orderBy($sortColumn, $sortDirection);

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
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Assessment Attempts</h1>
            <p class="mt-1 text-zinc-600 dark:text-zinc-300">
                <span class="font-medium text-zinc-900 dark:text-white">{{ $totalCount }}</span> total &middot;
                <span class="font-medium text-lime-600 dark:text-lime-400">{{ $submittedCount }}</span> submitted &middot;
                <span class="font-medium text-amber-600 dark:text-amber-400">{{ $inProgressCount }}</span> in progress
            </p>
        </div>
        <a href="{{ route('admin.export.submissions') }}" class="inline-flex items-center gap-2 bg-primary-500 hover:bg-primary-600 text-white font-semibold py-2.5 px-5 rounded-lg transition-colors shadow-md hover:shadow-lg">
            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
            </svg>
            Export CSV
        </a>
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
                <svg class="mx-auto size-12 text-zinc-300 dark:text-zinc-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859m-19.5.338V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H6.911a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661Z" />
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">No attempts yet</h3>
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">Attempts will appear here when candidates start the assessment.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr class="text-left text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            <th wire:click="sort('candidate_name')" class="px-4 py-3 cursor-pointer hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                Name
                                @if($sortBy === 'candidate_name')
                                    <span class="ml-1 text-primary-500">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3">Email</th>
                            <th wire:click="sort('status')" class="px-4 py-3 cursor-pointer hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                Status
                                @if($sortBy === 'status')
                                    <span class="ml-1 text-primary-500">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th wire:click="sort('started_at')" class="px-4 py-3 cursor-pointer hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                Started
                                @if($sortBy === 'started_at')
                                    <span class="ml-1 text-primary-500">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th wire:click="sort('completed_at')" class="px-4 py-3 cursor-pointer hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                                Completed
                                @if($sortBy === 'completed_at')
                                    <span class="ml-1 text-primary-500">{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </th>
                            <th class="px-4 py-3">Duration</th>
                            <th class="px-4 py-3">Reviewed</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($attempts as $attempt)
                            <tr wire:key="attempt-{{ $attempt->id }}" class="text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800/30 transition-colors">
                                <td class="px-4 py-3.5 font-semibold text-zinc-900 dark:text-zinc-100">{{ $attempt->candidate_name }}</td>
                                <td class="px-4 py-3.5 text-zinc-600 dark:text-zinc-400">{{ $attempt->candidate_email }}</td>
                                <td class="px-4 py-3.5">
                                    @if($attempt->status === 'submitted')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-lime-100 text-lime-800 dark:bg-lime-900/50 dark:text-lime-200">
                                            Submitted
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200">
                                            In Progress
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-zinc-600 dark:text-zinc-400">{{ $attempt->started_at?->format('M j, g:i A') }}</td>
                                <td class="px-4 py-3.5 text-zinc-600 dark:text-zinc-400">{{ $attempt->completed_at?->format('M j, g:i A') ?? '-' }}</td>
                                <td class="px-4 py-3.5 text-zinc-600 dark:text-zinc-400 font-mono text-xs">
                                    @if($attempt->duration_seconds)
                                        {{ floor($attempt->duration_seconds / 60) }}m {{ $attempt->duration_seconds % 60 }}s
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    @if($attempt->reviewed_at)
                                        <span class="inline-flex items-center justify-center size-6 rounded-full bg-lime-100 dark:bg-lime-900/50">
                                            <svg class="size-4 text-lime-600 dark:text-lime-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                            </svg>
                                        </span>
                                    @else
                                        <span class="inline-flex items-center justify-center size-6 rounded-full bg-zinc-100 dark:bg-zinc-800">
                                            <svg class="size-4 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" />
                                            </svg>
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5">
                                    <a href="{{ route('admin.attempts.show', $attempt) }}" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                                        View
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </a>
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
