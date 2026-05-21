<!-- Commission Handover Modal -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/handover-files-modal.css') }}?v={{ filemtime(public_path('css/handover-files-modal.css')) }}">

@if($showDetailsModal && $selectedHandover)
    <div class="handover-modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="handover-modal-container">
            <!-- Background overlay -->
            <div class="handover-modal-background" wire:click="closeDetailsModal" aria-hidden="true"></div>

            <!-- Modal panel -->
            <div class="handover-modal-panel">
                <!-- Header -->
                <div class="handover-modal-header">
                    <div class="handover-modal-header-content">
                        <div>
                            <h3 class="handover-modal-title" style="font-size: 0.875rem;">
                                {{ $selectedHandover->fh_id ?? '' }}
                            </h3>
                            <h3 class="handover-modal-title" style="font-size: 0.875rem;">{{ strtoupper($selectedHandover->reseller_name ?? '') }}</h3>
                            <h3 class="handover-modal-title" style="font-size: 0.875rem;">{{ strtoupper($selectedHandover->subscriber_name ?? '') }}</h3>
                        </div>
                        <button wire:click="closeDetailsModal" class="handover-modal-close-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="handover-modal-body">
                    <div class="handover-modal-grid">
                        <!-- Left Column: Invoice Details & Amount -->
                        <div class="handover-modal-column">
                            <div class="handover-info-box">
                                <h4 class="handover-info-title" style="margin-bottom: 0.75rem;">Invoice Details</h4>
                                <div style="line-height: 1.8;">
                                    <p style="font-size: 0.875rem; color: #1f2937;">
                                        <span style="display: inline-block; width: 140px; color: #6b7280;">AP Number</span>:
                                        @if($selectedHandover->ap_invoice_url)
                                            <a href="{{ $selectedHandover->ap_invoice_url }}" target="_blank" class="handover-invoice-link">
                                                {{ $selectedHandover->ap_invoice_no }} <i class="fas fa-external-link-alt" style="font-size: 0.7rem;"></i>
                                            </a>
                                        @else
                                            <span style="font-weight: 600;">{{ $selectedHandover->ap_invoice_no ?? 'N/A' }}</span>
                                        @endif
                                    </p>
                                    <p style="font-size: 0.875rem; color: #1f2937;">
                                        <span style="display: inline-block; width: 140px; color: #6b7280;">PI Number</span>:
                                        @if($selectedHandover->tt_invoice_url)
                                            <a href="{{ $selectedHandover->tt_invoice_url }}" target="_blank" class="handover-invoice-link">
                                                {{ $selectedHandover->tt_invoice_no }} <i class="fas fa-external-link-alt" style="font-size: 0.7rem;"></i>
                                            </a>
                                        @else
                                            <span style="font-weight: 600;">{{ $selectedHandover->tt_invoice_no ?? '-' }}</span>
                                        @endif
                                    </p>
                                    <p style="font-size: 0.875rem; color: #1f2937;">
                                        <span style="display: inline-block; width: 140px; color: #6b7280;">Invoice Number</span>:
                                        <span style="font-size: 1.125rem; font-weight: bold;">{{ $selectedHandover->autocount_inv_no ?? '-' }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="handover-info-box">
                                <h4 class="handover-info-title" style="margin-bottom: 0.75rem;">Amount</h4>
                                <div style="line-height: 1.8;">
                                    <p style="font-size: 0.875rem; color: #1f2937;">
                                        <span style="display: inline-block; width: 140px; color: #6b7280;">Currency</span>: <span style="font-weight: 600;">{{ $selectedHandover->currency ?? 'MYR' }}</span>
                                    </p>
                                    <p style="font-size: 0.875rem; color: #1f2937;">
                                        <span style="display: inline-block; width: 140px; color: #6b7280;">Amount</span>: <span style="font-weight: 600;">{{ $selectedHandover->currency ?? 'MYR' }} {{ number_format($selectedHandover->amount, 2) }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Status & Timeline + Payment Slip -->
                        <div class="handover-modal-column">
                            <div class="handover-info-box">
                                <h4 class="handover-info-title" style="margin-bottom: 0.75rem;">Status & Timeline</h4>
                                <div style="line-height: 1.8;">
                                    <p style="font-size: 0.875rem; color: #1f2937;">
                                        <span style="display: inline-block; width: 140px; color: #6b7280;">Status</span>:
                                        @if($selectedHandover->status === 'pending_reseller')
                                            <span style="font-weight: 600; color: #d97706;">Pending Reseller</span>
                                        @elseif($selectedHandover->status === 'pending_finance')
                                            <span style="font-weight: 600; color: #2563eb;">Pending Finance</span>
                                        @elseif($selectedHandover->status === 'completed')
                                            <span style="font-weight: 600; color: #059669;">Completed</span>
                                        @else
                                            <span style="font-weight: 600;">{{ $selectedHandover->status }}</span>
                                        @endif
                                    </p>
                                    <p style="font-size: 0.875rem; color: #1f2937;">
                                        <span style="display: inline-block; width: 140px; color: #6b7280;">Created</span>: <span style="font-weight: 600;">{{ $selectedHandover->created_at ? $selectedHandover->created_at->format('d M Y, h:i A') : '-' }}</span>
                                    </p>
                                    @if($selectedHandover->reseller_proceeded_at)
                                        <p style="font-size: 0.875rem; color: #1f2937;">
                                            <span style="display: inline-block; width: 140px; color: #6b7280;">Proceeded</span>: <span style="font-weight: 600;">{{ $selectedHandover->reseller_proceeded_at->format('d M Y, h:i A') }}</span>
                                        </p>
                                    @endif
                                    @if($selectedHandover->completed_at)
                                        <p style="font-size: 0.875rem; color: #1f2937;">
                                            <span style="display: inline-block; width: 140px; color: #6b7280;">Completed</span>: <span style="font-weight: 600;">{{ $selectedHandover->completed_at->format('d M Y, h:i A') }}</span>
                                        </p>
                                    @endif
                                </div>
                            </div>

                            @if($selectedHandover->payment_slip || $selectedHandover->self_billed_einvoice)
                                <div class="handover-stage-section finance-payment">
                                    <h4 class="handover-stage-title finance-payment" style="margin-bottom: 0.5rem;">File From TimeTec Finance</h4>
                                    <div class="handover-files-list">
                                        @if($selectedHandover->payment_slip)
                                            <div class="handover-file-item finance-payment">
                                                <div class="handover-file-info">
                                                    <div class="handover-file-icon finance-payment">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </div>
                                                    <div>
                                                        <p class="handover-file-name">Finance Payment Slip</p>
                                                    </div>
                                                </div>
                                                <a href="{{ asset('storage/' . $selectedHandover->payment_slip) }}" target="_blank" class="handover-file-link finance-payment">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        @endif
                                        @if($selectedHandover->self_billed_einvoice)
                                            <div class="handover-file-item finance-payment">
                                                <div class="handover-file-info">
                                                    <div class="handover-file-icon finance-payment">
                                                        <i class="fas fa-file-invoice"></i>
                                                    </div>
                                                    <div>
                                                        <p class="handover-file-name">Self-Billed e-Invoice</p>
                                                    </div>
                                                </div>
                                                <a href="{{ asset('storage/' . $selectedHandover->self_billed_einvoice) }}" target="_blank" class="handover-file-link finance-payment">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
