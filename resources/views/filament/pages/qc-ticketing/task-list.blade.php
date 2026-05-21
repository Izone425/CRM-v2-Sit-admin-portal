<x-filament-panels::page>
    <style>
        .scope-toggle {
            display: inline-flex;
            background: #F3F4F6;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 4px;
            gap: 2px;
        }
        .scope-toggle button {
            padding: 8px 18px;
            border: 0;
            background: transparent;
            color: #6B7280;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .scope-toggle button:hover { color: #111827; }
        .scope-toggle button.active {
            background: #6366F1;
            color: #fff;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.25);
        }
    </style>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex gap-2">
                @foreach (['All', 'New', 'Reopen'] as $tab)
                    <button
                        type="button"
                        wire:click="setTab('{{ $tab }}')"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === $tab ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50' }}"
                    >
                        {{ $tab }} ({{ $this->tabCounts[$tab] ?? 0 }})
                    </button>
                @endforeach
            </div>

            <div class="scope-toggle">
                <button type="button" wire:click="setViewMode('all')" class="{{ $viewMode === 'all' ? 'active' : '' }}">All Tasks</button>
                <button type="button" wire:click="setViewMode('my')" class="{{ $viewMode === 'my' ? 'active' : '' }}">My Tasks</button>
            </div>
        </div>

        <div class="p-4 bg-white rounded-lg shadow">
            {{ $this->table }}
        </div>
    </div>

    <livewire:task-modal />
    <livewire:ticket-modal />
    <livewire:create-task-drawer />
    <livewire:create-bug-drawer />
    <livewire:create-ticket-drawer />
    <livewire:create-suggestion-drawer />
    <livewire:create-creative-request-drawer />
    <livewire:create-release-drawer />
</x-filament-panels::page>
