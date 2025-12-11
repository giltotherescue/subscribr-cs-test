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

    <flux:card>
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
                <flux:icon.inbox class="mx-auto size-12 text-zinc-400" />
                <flux:heading class="mt-4">No attempts yet</flux:heading>
                <flux:text class="mt-2">Attempts will appear here when candidates start the assessment.</flux:text>
            </div>
        @else
            <flux:table>
                <flux:table.columns>
                    <flux:table.column wire:click="sort('candidate_name')" class="cursor-pointer">
                        Name
                        @if($sortBy === 'candidate_name')
                            <flux:icon.chevron-{{ $sortDir === 'asc' ? 'up' : 'down' }} variant="micro" class="inline" />
                        @endif
                    </flux:table.column>
                    <flux:table.column>Email</flux:table.column>
                    <flux:table.column wire:click="sort('status')" class="cursor-pointer">
                        Status
                        @if($sortBy === 'status')
                            <flux:icon.chevron-{{ $sortDir === 'asc' ? 'up' : 'down' }} variant="micro" class="inline" />
                        @endif
                    </flux:table.column>
                    <flux:table.column wire:click="sort('started_at')" class="cursor-pointer">
                        Started
                        @if($sortBy === 'started_at')
                            <flux:icon.chevron-{{ $sortDir === 'asc' ? 'up' : 'down' }} variant="micro" class="inline" />
                        @endif
                    </flux:table.column>
                    <flux:table.column wire:click="sort('completed_at')" class="cursor-pointer">
                        Completed
                        @if($sortBy === 'completed_at')
                            <flux:icon.chevron-{{ $sortDir === 'asc' ? 'up' : 'down' }} variant="micro" class="inline" />
                        @endif
                    </flux:table.column>
                    <flux:table.column>Duration</flux:table.column>
                    <flux:table.column>Reviewed</flux:table.column>
                    <flux:table.column></flux:table.column>
                </flux:table.columns>
                <flux:table.rows>
                    @foreach($attempts as $attempt)
                        <flux:table.row wire:key="attempt-{{ $attempt->id }}">
                            <flux:table.cell class="font-medium">{{ $attempt->candidate_name }}</flux:table.cell>
                            <flux:table.cell>{{ $attempt->candidate_email }}</flux:table.cell>
                            <flux:table.cell>
                                @if($attempt->status === 'submitted')
                                    <flux:badge color="lime" size="sm">Submitted</flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm">In Progress</flux:badge>
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>{{ $attempt->started_at?->format('M j, g:i A') }}</flux:table.cell>
                            <flux:table.cell>{{ $attempt->completed_at?->format('M j, g:i A') ?? '-' }}</flux:table.cell>
                            <flux:table.cell>
                                @if($attempt->duration_seconds)
                                    {{ floor($attempt->duration_seconds / 60) }}m {{ $attempt->duration_seconds % 60 }}s
                                @else
                                    -
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                @if($attempt->reviewed_at)
                                    <flux:icon.check-circle class="size-5 text-lime-600" />
                                @else
                                    <flux:icon.minus-circle class="size-5 text-zinc-300" />
                                @endif
                            </flux:table.cell>
                            <flux:table.cell>
                                <flux:button href="{{ route('admin.attempts.show', $attempt) }}" size="sm" variant="ghost" icon="eye">
                                    View
                                </flux:button>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforeach
                </flux:table.rows>
            </flux:table>

            <div class="mt-6">
                {{ $attempts->links() }}
            </div>
        @endif
    </flux:card>
</div>
