<x-filament-panels::page>
    <style>
        .bug-tab-button {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            border: 2px solid;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            background: none;
            outline: none;
            position: relative;
            overflow: hidden;
        }
        .bug-tab-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        }
        .bug-tab-button.active-v1,
        .bug-tab-button.active-v2 {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-color: #059669;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(16,185,129,0.4), 0 4px 6px -2px rgba(16,185,129,0.05);
        }
        .bug-tab-button.active-v1:hover,
        .bug-tab-button.active-v2:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 20px 25px -5px rgba(16,185,129,0.5), 0 10px 10px -5px rgba(16,185,129,0.1);
        }
.bug-tab-button.inactive {
            background: white;
            border-color: #d1d5db;
            color: #374151;
        }
        .bug-tab-button.inactive:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #111827;
        }
        .bug-tab-button::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.1) 50%, transparent 100%);
            pointer-events: none;
        }
        .bug-tab-button:active { transform: scale(0.98); }
        .bug-tab-icon {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            flex-shrink: 0;
        }
        .bug-tab-container {
            display: flex;
            justify-content: flex-start;
            gap: 16px;
        }
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
            box-shadow: 0 2px 4px rgba(99,102,241,0.25);
        }
        @media (max-width: 768px) {
            .bug-tab-container { flex-direction: column; gap: 12px; }
            .bug-tab-button { padding: 10px 20px; font-size: 13px; justify-content: center; }
        }
    </style>

    <div class="space-y-4">
        {{-- Version tabs + scope toggle --}}
        <div style="display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;">
            <div class="scope-toggle">
                <button type="button" wire:click="setVersion('all')" class="{{ $version === 'all' ? 'active' : '' }}">All</button>
                <button type="button" wire:click="setVersion('v1')" class="{{ $version === 'v1' ? 'active' : '' }}">Version 1 Bugs</button>
                <button type="button" wire:click="setVersion('v2')" class="{{ $version === 'v2' ? 'active' : '' }}">Version 2 Bugs</button>
            </div>

            <div class="scope-toggle">
                <button type="button" wire:click="setViewMode('all')" class="{{ $viewMode === 'all' ? 'active' : '' }}">All Bugs</button>
                <button type="button" wire:click="setViewMode('my')" class="{{ $viewMode === 'my' ? 'active' : '' }}">My Bugs</button>
            </div>
        </div>

        {{-- Status tabs --}}
        <div class="flex flex-wrap gap-2">
            @foreach (['All', 'New', 'Reopen', 'Ready For Testing', 'QC-In Progress', 'Ready For Live', 'Live', 'Closed', 'Rejected'] as $tab)
                <button
                    type="button"
                    wire:click="setTab('{{ $tab }}')"
                    class="px-4 py-2 text-sm font-medium rounded-md transition-colors {{ $activeTab === $tab ? 'bg-primary-600 text-white' : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50' }}"
                >
                    {{ $tab }} ({{ $this->tabCounts[$tab] ?? 0 }})
                </button>
            @endforeach

        </div>

        <div class="p-4 bg-white rounded-lg shadow">
            {{ $this->table }}
        </div>
    </div>

    <livewire:bug-modal />
    <livewire:create-task-drawer />
    <livewire:create-bug-drawer />
    <livewire:create-ticket-drawer />
    <livewire:create-suggestion-drawer />
    <livewire:create-creative-request-drawer />
    <livewire:create-release-drawer />
</x-filament-panels::page>
