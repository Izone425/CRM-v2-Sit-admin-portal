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
                    <th>Actions</th>
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
                        <td>
                            <button wire:click="openConfirmModal({{ $handover->id }})" class="confirm-button">
                                <svg class="confirm-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Proceed
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="empty-state"></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Confirmation Modal -->
    @if($showConfirmModal)
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="modal-title">Proceed to Finance?</h3>
                </div>
                <div class="modal-body">
                    <p style="font-weight: 600; color: #111827;">Please make sure you have checked your commission amount based on the AP document.</p>
                    <p style="margin-top: 0.75rem;">If you agree with the amount, kindly proceed.</p>
                </div>
                <div class="modal-actions">
                    <button wire:click="closeConfirmModal" class="modal-button-cancel">Cancel</button>
                    <button wire:click="proceedConfirmation" class="modal-button-confirm">
                        Yes, Proceed
                    </button>
                </div>
            </div>
        </div>
    @endif

    @include('components.commission-handover-modal')

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="success-message">
            <i class="mr-2 fas fa-check-circle"></i>{{ session('message') }}
        </div>
    @endif
</div>
