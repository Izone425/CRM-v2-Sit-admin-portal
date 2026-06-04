<x-filament-panels::page>
    <div class="space-y-4">
        <div class="flex gap-2 border-b border-gray-200 dark:border-gray-700">
            <button
                type="button"
                wire:click="setTab('all')"
                @class([
                    'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition',
                    'border-primary-600 text-primary-600' => $activeTab === 'all',
                    'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' => $activeTab !== 'all',
                ])
            >
                All List
            </button>
            <button
                type="button"
                wire:click="setTab('pending')"
                @class([
                    'px-4 py-2 text-sm font-medium border-b-2 -mb-px transition inline-flex items-center gap-2',
                    'border-primary-600 text-primary-600' => $activeTab === 'pending',
                    'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300' => $activeTab !== 'pending',
                ])
            >
                Pending Approval
                @if ($this->pendingCount > 0)
                    <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold">
                        {{ $this->pendingCount }}
                    </span>
                @endif
            </button>
        </div>

        @if ($activeTab === 'all')
            @livewire('hr-admin-dashboard.hr-reseller-table', ['showHero' => true], key('hr-resellers-all'))
        @else
            @livewire('hr-admin-dashboard.partner-applications-table', ['type' => 'reseller'], key('pending-reseller'))
        @endif
    </div>
</x-filament-panels::page>
