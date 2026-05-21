<div>
    @include('components.reseller-handover-table-styles')

    <div class="search-wrapper" style="display: flex; gap: 1rem; align-items: center;">
        <div style="position: relative; flex: 1;">
            <div class="search-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input type="text" wire:model.live="search" class="search-input" placeholder="Search by company name">
        </div>

        <div class="filter-container" x-data="{ open: false }" @click.away="open = false">
            <button @click="open = !open" class="filter-button" :class="{ 'active': {{ (!empty($statusFilter) || $activeFilter !== 'all') ? 'true' : 'false' }} }">
                <svg class="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                <span>Filter</span>
            </button>
            <div class="filter-dropdown" :class="{ 'show': open }" @click.stop>
                <div class="filter-section">
                    <div class="filter-section-title">Status</div>
                    <div class="filter-select-wrapper">
                        <select wire:model.live="statusFilter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="new">New</option>
                            <option value="pending_quotation_confirmation">Pending Quotation Confirmation</option>
                            <option value="pending_invoice_confirmation">Pending Invoice Confirmation</option>
                            <option value="pending_reseller_payment">Pending Reseller Payment</option>
                            <option value="pending_timetec_license">Pending TimeTec License</option>
                            <option value="pending_timetec_invoice">Pending TimeTec Invoice</option>
                            <option value="pending_timetec_finance">Pending TimeTec Finance</option>
                            <option value="pending_finance_payment">Pending Finance Payment</option>
                            <option value="completed">Completed</option>
                            <option value="inactive">InActive</option>
                        </select>
                    </div>
                </div>
                <div class="filter-section">
                    <div class="filter-section-title">Subscriber Status</div>
                    <div class="filter-select-wrapper">
                        <select wire:model.live="activeFilter" class="filter-select">
                            <option value="all">All</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">InActive Only</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>
                        <button wire:click="sortBy('id')">ID
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'id')
                                    @if($sortDirection === 'desc') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path> @endif
                                @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path> @endif
                            </svg>
                        </button>
                    </th>
                    <th>
                        <button wire:click="sortBy('subscriber_name')">Subscriber Name
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'subscriber_name')
                                    @if($sortDirection === 'desc') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path> @endif
                                @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path> @endif
                            </svg>
                        </button>
                    </th>
                    <th>
                        <button wire:click="sortBy('updated_at')">Last Modified
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'updated_at')
                                    @if($sortDirection === 'desc') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path> @endif
                                @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path> @endif
                            </svg>
                        </button>
                    </th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($handovers as $handover)
                    <tr>
                        <td class="fb-id">
                            <a wire:click="openFilesModal({{ $handover->id }})" style="color: #3b82f6; font-weight: 600; cursor: pointer; text-decoration: none;"
                               onmouseover="this.style.textDecoration='underline'"
                               onmouseout="this.style.textDecoration='none'">
                                {{ $handover->fe_id }}
                            </a>
                        </td>
                        <td class="subscriber-name">{{ $handover->subscriber_name }}</td>
                        <td class="date-cell">{{ $handover->updated_at->format('d M Y, H:i') }}</td>
                        <td>
                            @php
                                $statusClass = 'status-' . str_replace('_', '-', $handover->status);
                                $statusLabel = str_replace('Timetec', 'TimeTec', ucwords(str_replace('_', ' ', $handover->status)));
                                if ($handover->status === 'inactive') { $statusLabel = 'InActive'; }
                            @endphp
                            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty-state">No handovers found</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('components.handover-fe-files-modal')
</div>
