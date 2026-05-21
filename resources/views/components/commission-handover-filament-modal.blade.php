<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/handover-files-modal.css') }}?v={{ filemtime(public_path('css/handover-files-modal.css')) }}">

<div style="padding: 0.5rem;">
    <!-- Header -->
    <div style="margin-bottom: 1rem;">
        <h3 style="font-weight: 600; font-size: 0.875rem; color: #111827;">{{ $handover->fh_id ?? '' }}</h3>
        <h3 style="font-weight: 600; font-size: 0.875rem; color: #111827;">{{ strtoupper($handover->reseller_name ?? '') }}</h3>
        <h3 style="font-weight: 600; font-size: 0.875rem; color: #111827;">{{ strtoupper($handover->subscriber_name ?? '') }}</h3>
    </div>

    <!-- Body -->
    <div class="handover-modal-grid">
        <!-- Left Column: Invoice Details & Amount -->
        <div class="handover-modal-column">
            <div class="handover-info-box">
                <h4 class="handover-info-title" style="margin-bottom: 0.75rem;">Invoice Details</h4>
                <div style="line-height: 1.8;">
                    <p style="font-size: 0.875rem; color: #1f2937;">
                        <span style="display: inline-block; width: 140px; color: #6b7280;">AP Number</span>:
                        @if($handover->ap_invoice_url)
                            <a href="{{ $handover->ap_invoice_url }}" target="_blank" class="handover-invoice-link">
                                {{ $handover->ap_invoice_no }} <i class="fas fa-external-link-alt" style="font-size: 0.7rem;"></i>
                            </a>
                        @else
                            <span style="font-weight: 600;">{{ $handover->ap_invoice_no ?? 'N/A' }}</span>
                        @endif
                    </p>
                    <p style="font-size: 0.875rem; color: #1f2937;">
                        <span style="display: inline-block; width: 140px; color: #6b7280;">PI Number</span>:
                        @if($handover->tt_invoice_url)
                            <a href="{{ $handover->tt_invoice_url }}" target="_blank" class="handover-invoice-link">
                                {{ $handover->tt_invoice_no }} <i class="fas fa-external-link-alt" style="font-size: 0.7rem;"></i>
                            </a>
                        @else
                            <span style="font-weight: 600;">{{ $handover->tt_invoice_no ?? '-' }}</span>
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
                        <span style="display: inline-block; width: 140px; color: #6b7280;">Currency</span>: <span style="font-weight: 600;">{{ $handover->currency ?? 'MYR' }}</span>
                    </p>
                    <p style="font-size: 0.875rem; color: #1f2937;">
                        <span style="display: inline-block; width: 140px; color: #6b7280;">Amount</span>: <span style="font-weight: 600;">{{ $handover->currency ?? 'MYR' }} {{ number_format($handover->amount, 2) }}</span>
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
                        @if($handover->status === 'pending_reseller')
                            <span style="font-weight: 600; color: #d97706;">Pending Reseller</span>
                        @elseif($handover->status === 'pending_finance')
                            <span style="font-weight: 600; color: #2563eb;">Pending Finance</span>
                        @elseif($handover->status === 'completed')
                            <span style="font-weight: 600; color: #059669;">Completed</span>
                        @else
                            <span style="font-weight: 600;">{{ $handover->status }}</span>
                        @endif
                    </p>
                    <p style="font-size: 0.875rem; color: #1f2937;">
                        <span style="display: inline-block; width: 140px; color: #6b7280;">Created</span>: <span style="font-weight: 600;">{{ $handover->created_at ? $handover->created_at->format('d M Y, h:i A') : '-' }}</span>
                    </p>
                    @if($handover->reseller_proceeded_at)
                        <p style="font-size: 0.875rem; color: #1f2937;">
                            <span style="display: inline-block; width: 140px; color: #6b7280;">Proceeded</span>: <span style="font-weight: 600;">{{ $handover->reseller_proceeded_at->format('d M Y, h:i A') }}</span>
                        </p>
                    @endif
                    @if($handover->completed_at)
                        <p style="font-size: 0.875rem; color: #1f2937;">
                            <span style="display: inline-block; width: 140px; color: #6b7280;">Completed</span>: <span style="font-weight: 600;">{{ $handover->completed_at->format('d M Y, h:i A') }}</span>
                        </p>
                    @endif
                </div>
            </div>

            @if($handover->payment_slip || $handover->self_billed_einvoice)
                <div class="handover-stage-section finance-payment">
                    <h4 class="handover-stage-title finance-payment" style="margin-bottom: 0.5rem;">File From TimeTec Finance</h4>
                    <div class="handover-files-list">
                        @if($handover->payment_slip)
                            <div class="handover-file-item finance-payment">
                                <div class="handover-file-info">
                                    <div class="handover-file-icon finance-payment">
                                        <i class="fas fa-file-pdf"></i>
                                    </div>
                                    <div>
                                        <p class="handover-file-name">Finance Payment Slip</p>
                                    </div>
                                </div>
                                <a href="{{ asset('storage/' . $handover->payment_slip) }}" target="_blank" class="handover-file-link finance-payment">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        @endif
                        @if($handover->self_billed_einvoice)
                            <div class="handover-file-item finance-payment">
                                <div class="handover-file-info">
                                    <div class="handover-file-icon finance-payment">
                                        <i class="fas fa-file-invoice"></i>
                                    </div>
                                    <div>
                                        <p class="handover-file-name">Self-Billed e-Invoice</p>
                                    </div>
                                </div>
                                <a href="{{ asset('storage/' . $handover->self_billed_einvoice) }}" target="_blank" class="handover-file-link finance-payment">
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
