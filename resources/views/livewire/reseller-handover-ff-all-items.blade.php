<div>
    @include('components.reseller-handover-table-styles')

    <!-- Search Input -->
    <div class="search-wrapper">
        <div class="search-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input type="text" wire:model.live="search" class="search-input" placeholder="Search by company name">
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>
                        <button wire:click="sortBy('id')">
                            ID
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'id')
                                    @if($sortDirection === 'desc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @endif
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th>TT Invoice No</th>
                    <th>
                        <button wire:click="sortBy('subscriber_name')">
                            Subscriber Name
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'subscriber_name')
                                    @if($sortDirection === 'desc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @endif
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
                            </svg>
                        </button>
                    </th>
                    <th>
                        <button wire:click="sortBy('updated_at')">
                            Last Modified
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'updated_at')
                                    @if($sortDirection === 'desc')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                    @endif
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                @endif
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
                                {{ $handover->ff_id }}
                            </a>
                        </td>
                        <td>
                            @if($handover->timetec_proforma_invoice && $handover->invoice_url)
                                <a href="{{ $handover->invoice_url }}" target="_blank" style="color: #3b82f6; font-weight: 600; text-decoration: none; font-size: 0.85rem;"
                                   onmouseover="this.style.textDecoration='underline'"
                                   onmouseout="this.style.textDecoration='none'">
                                    {{ $handover->timetec_proforma_invoice }}
                                    <svg style="width: 12px; height: 12px; display: inline; vertical-align: middle; margin-left: 2px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            @elseif($handover->timetec_proforma_invoice)
                                <span style="color: #6b7280; font-size: 0.85rem;">{{ $handover->timetec_proforma_invoice }}</span>
                            @else
                                <span style="color: #9ca3af; font-size: 0.85rem;">-</span>
                            @endif
                        </td>
                        <td class="subscriber-name">{{ $handover->subscriber_name }}</td>
                        <td class="date-cell">{{ $handover->updated_at->format('d M Y, H:i') }}</td>
                        <td>
                            @php
                                $statusClass = 'status-' . str_replace('_', '-', $handover->status);
                                $statusLabel = ucwords(str_replace('_', ' ', $handover->status));
                                if ($handover->status === 'inactive') { $statusLabel = 'InActive'; }
                            @endphp
                            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state">No handovers found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('components.handover-ff-files-modal')
</div>
