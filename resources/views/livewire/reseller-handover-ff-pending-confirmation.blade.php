<div wire:poll.30s="checkPaymentStatus">
    <style>
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>
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
                        <td class="date-cell">{{ $handover->updated_at->format('d/m/Y h:i A') }}</td>
                        <td>
                            @if($handover->payment_clicked_at && $handover->payment_clicked_at->diffInMinutes(now()) < 1)
                                @php $remainingSeconds = 60 - $handover->payment_clicked_at->diffInSeconds(now()); @endphp
                                <div x-data="{ remaining: {{ $remainingSeconds }} }"
                                     x-init="let timer = setInterval(() => { remaining--; if (remaining <= 0) { clearInterval(timer); $wire.$refresh(); } }, 1000);"
                                     style="display: inline-flex; align-items: center; gap: 0.5rem; color: #f59e0b; font-size: 0.85rem; font-weight: 600;">
                                    <svg style="animation: spin 1s linear infinite; width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    <span>Checking Payment Received...
                                        (<span x-text="Math.floor(remaining / 60) + ':' + String(remaining % 60).padStart(2, '0')"></span>)
                                    </span>
                                </div>
                            @else
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    @if($handover->invoice_url)
                                        <a href="{{ $handover->invoice_url }}" target="_blank" wire:click="markCheckingPayment({{ $handover->id }})" class="confirm-button" style="text-decoration: none;">
                                            <svg class="confirm-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                            Make Payment
                                        </a>
                                    @else
                                        <span style="color: #9ca3af; font-size: 0.85rem;">No invoice available</span>
                                    @endif
                                    <button wire:click="openCancelModal({{ $handover->id }})" class="cancel-order-button">
                                        <svg class="confirm-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Cancel Order
                                    </button>
                                </div>
                            @endif
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

    <!-- Cancel Order Modal -->
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

    <!-- Payment Received Modal -->
    @if($showPaymentReceivedModal)
        <div class="modal-overlay">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="modal-icon" style="background: linear-gradient(135deg, #bbf7d0 0%, #86efac 100%);">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: #16a34a;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h3 class="modal-title">Payment Received</h3>
                </div>
                <div class="modal-body">
                    <p>Payment has been successfully received for:</p>
                    <p style="margin-top: 0.5rem; color: #16a34a; font-weight: 700; font-size: 1.05rem;">{{ $paymentReceivedName }}</p>
                </div>
                <div class="modal-actions">
                    <button wire:click="closePaymentReceivedModal" class="modal-button-cancel" style="background: #16a34a; color: white; border-color: #16a34a;">
                        OK
                    </button>
                </div>
            </div>
        </div>
    @endif

    @include('components.handover-ff-files-modal')

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" class="success-message">
            <i class="mr-2 fas fa-check-circle"></i>{{ session('message') }}
        </div>
    @endif
</div>
