<div>
    @include('components.reseller-handover-table-styles')

    <!-- Search Input -->
    <div class="search-wrapper">
        <div class="search-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input type="text" wire:model.live="search" class="search-input" placeholder="Search by PI No, subscriber name...">
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>FH ID</th>
                    <th>
                        <button wire:click="sortBy('ap_invoice_no')">
                            AP NUMBER
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'ap_invoice_no')
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
                    <th>TT NUMBER</th>
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
                </tr>
            </thead>
            <tbody>
                @forelse($handovers as $handover)
                    <tr>
                        <td class="fb-id">
                            <a wire:click="openDetailsModal({{ $handover->id }})"
                               style="color: #3b82f6; font-weight: 600; cursor: pointer; text-decoration: none;"
                               onmouseover="this.style.textDecoration='underline'"
                               onmouseout="this.style.textDecoration='none'">
                                {{ $handover->fh_id }}
                            </a>
                        </td>
                        <td class="fb-id">
                            @if($handover->ap_invoice_url)
                                <a href="{{ $handover->ap_invoice_url }}" target="_blank" style="color: #059669; font-weight: 600; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                    {{ $handover->ap_invoice_no }} <i class="fas fa-external-link-alt" style="font-size: 0.65rem;"></i>
                                </a>
                            @else
                                {{ $handover->ap_invoice_no }}
                            @endif
                        </td>
                        <td>
                            @if($handover->tt_invoice_url)
                                <a href="{{ $handover->tt_invoice_url }}" target="_blank" style="color: #3b82f6; font-weight: 600; text-decoration: none;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                    {{ $handover->tt_invoice_no }} <i class="fas fa-external-link-alt" style="font-size: 0.65rem;"></i>
                                </a>
                            @else
                                {{ $handover->tt_invoice_no ?? '-' }}
                            @endif
                        </td>
                        <td class="subscriber-name">{{ strtoupper($handover->subscriber_name ?? 'N/A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state"></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('components.commission-handover-modal')
</div>
