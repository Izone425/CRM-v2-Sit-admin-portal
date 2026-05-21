<div>
    @include('components.reseller-handover-table-styles')

    <div class="search-wrapper">
        <div class="search-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input type="text" wire:model.live="search" class="search-input" placeholder="Search by company name">
    </div>

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
                    <th>Last Modified</th>
                    <th>Actions</th>
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
                        <td class="date-cell">{{ $handover->updated_at->format('d/m/Y h:i A') }}</td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; align-items: center;">
                                <button wire:click="openConfirmModal({{ $handover->id }})" class="confirm-button">
                                    <svg class="confirm-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Proceed
                                </button>
                                <button wire:click="openCancelModal({{ $handover->id }})" class="cancel-order-button">
                                    <svg class="confirm-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Cancel Order
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state"></td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($showConfirmModal)
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="modal-title">Proceed with Confirmation?</h3>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to proceed with this confirmation?</p>
                    <p style="margin-top: 0.5rem;">This will send the request to TimeTec for <strong style="color: red;">Invoice</strong></p>
                </div>
                <div class="modal-actions">
                    <button wire:click="closeConfirmModal" class="modal-button-cancel">Cancel</button>
                    <button wire:click="proceedConfirmation" class="modal-button-confirm">Confirm & Proceed</button>
                </div>
            </div>
        </div>
    @endif

    @if($showCancelModal)
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-icon" style="background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #dc2626;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                    <h3 class="modal-title">Cancel Order?</h3>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this order?</p>
                    <p style="margin-top: 0.5rem; color: #dc2626; font-weight: 600;">This action cannot be undone.</p>
                </div>
                <div class="modal-actions">
                    <button wire:click="closeCancelModal" class="modal-button-cancel">No, Keep It</button>
                    <button wire:click="cancelOrder" class="modal-button-danger">Yes, Cancel Order</button>
                </div>
            </div>
        </div>
    @endif

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="success-message">
            <i class="mr-2 fas fa-check-circle"></i>{{ session('message') }}
        </div>
    @endif

    @include('components.handover-fe-files-modal')
</div>
