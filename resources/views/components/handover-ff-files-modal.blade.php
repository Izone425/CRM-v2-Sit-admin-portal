<!-- FF Files Modal -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/handover-files-modal.css') }}">

@if($showFilesModal && $selectedHandover)
    <div class="handover-modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="handover-modal-container">
            <!-- Background overlay -->
            <div class="handover-modal-background" wire:click="closeFilesModal" aria-hidden="true"></div>

            <!-- Modal panel -->
            <div class="handover-modal-panel">
                <!-- Header -->
                <div class="handover-modal-header">
                    <div class="handover-modal-header-content">
                        <div>
                            <h3 class="handover-modal-title" style="font-size: 0.875rem;">
                                {{ $selectedHandover->ff_id ?? '' }}
                            </h3>
                            <h3 class="handover-modal-title" style="font-size: 0.875rem;">{{ $selectedHandover->reseller_company_name ?? '' }}</h3>
                            <h3 class="handover-modal-title" style="font-size: 0.875rem;">{{ $selectedHandover->subscriber_name ?? '' }}</h3>
                        </div>
                        <button wire:click="closeFilesModal" class="handover-modal-close-btn">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="handover-modal-body">
                    <div class="handover-modal-grid">
                        <!-- Left Column -->
                        <div class="handover-modal-column">
                            <div class="handover-info-box">
                                <h4 class="handover-info-title">
                                    Reseller Remark:
                                    @if(isset($selectedHandover->reseller_remark) && $selectedHandover->reseller_remark)
                                        <span
                                            wire:click="$set('showRemarkModal', true)"
                                            style="color: #3b82f6; cursor: pointer; text-decoration: underline; margin-left: 0.25rem;"
                                            onmouseover="this.style.color='#2563eb'"
                                            onmouseout="this.style.color='#3b82f6'">
                                            View
                                        </span>
                                    @else
                                        <span style="color: #6b7280; margin-left: 0.25rem;">
                                            No Remark
                                        </span>
                                    @endif
                                </h4>
                                <h4 class="handover-info-title">
                                    Category:
                                    <span style="font-weight: 600; margin-left: 0.25rem; color: #1f2937;">
                                        @if($selectedHandover->category === 'renewal_subscription')
                                            Renewal Sales
                                        @elseif($selectedHandover->category === 'addon_headcount')
                                            AddOn Headcount
                                        @else
                                            N/A
                                        @endif
                                    </span>
                                </h4>
                            </div>

                            <div class="handover-info-box">
                                <div style="margin-top: 0.75rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                    <div style="line-height: 1.8;">
                                        <p style="font-size: 0.875rem; color: #1f2937;">
                                            <span style="display: inline-block; width: 100px;">Attendance</span>: <span style="font-weight: 600;">{{ number_format((int) ($selectedHandover->attendance_qty ?? 0)) }}</span>
                                        </p>
                                        <p style="font-size: 0.875rem; color: #1f2937;">
                                            <span style="display: inline-block; width: 100px;">Leave</span>: <span style="font-weight: 600;">{{ number_format((int) ($selectedHandover->leave_qty ?? 0)) }}</span>
                                        </p>
                                        <p style="font-size: 0.875rem; color: #1f2937;">
                                            <span style="display: inline-block; width: 100px;">Claim</span>: <span style="font-weight: 600;">{{ number_format((int) ($selectedHandover->claim_qty ?? 0)) }}</span>
                                        </p>
                                        <p style="font-size: 0.875rem; color: #1f2937;">
                                            <span style="display: inline-block; width: 100px;">Payroll</span>: <span style="font-weight: 600;">{{ number_format((int) ($selectedHandover->payroll_qty ?? 0)) }}</span>
                                        </p>
                                        <p style="font-size: 0.875rem; color: #1f2937;">
                                            <span style="display: inline-block; width: 100px;">QF Master</span>: <span style="font-weight: 600;">{{ number_format((int) ($selectedHandover->qf_master_qty ?? 0)) }}</span>
                                        </p>
                                    </div>
                                    @if(trim((string) ($selectedHandover->reseller_id ?? '')) === '0000000934')
                                        <div style="line-height: 1.8;">
                                            <p style="font-size: 0.875rem; color: #1f2937;">
                                                <span style="display: inline-block; width: 100px;">VMS</span>: <span style="font-weight: 600;">{{ number_format((int) ($selectedHandover->vms_qty ?? 0)) }}</span>
                                            </p>
                                            <p style="font-size: 0.875rem; color: #1f2937;">
                                                <span style="display: inline-block; width: 100px;">Patrol</span>: <span style="font-weight: 600;">{{ number_format((int) ($selectedHandover->patrol_qty ?? 0)) }}</span>
                                            </p>
                                            <p style="font-size: 0.875rem; color: #1f2937;">
                                                <span style="display: inline-block; width: 100px;">Access</span>: <span style="font-weight: 600;">{{ number_format((int) ($selectedHandover->access_qty ?? 0)) }}</span>
                                            </p>
                                            <p style="font-size: 0.875rem; color: #1f2937;">
                                                <span style="display: inline-block; width: 100px;">FCC</span>: <span style="font-weight: 600;">{{ number_format((int) ($selectedHandover->fcc_qty ?? 0)) }}</span>
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="handover-modal-column">
                            <!-- TimeTec Proforma Invoice -->
                            <div class="handover-stage-section pending-timetec-invoice">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                    <h4 class="handover-stage-title pending-timetec-invoice" style="margin: 0;">
                                        TimeTec Proforma Invoice
                                    </h4>
                                    <p style="font-size: 0.875rem; color: #6b7280; font-weight: 400; margin: 0;">
                                        {{ $selectedHandover->ttpi_submitted_at ? $selectedHandover->ttpi_submitted_at->format('d M Y, h:i A') : '' }}
                                    </p>
                                </div>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    @if(isset($selectedHandover->timetec_proforma_invoice) && $selectedHandover->timetec_proforma_invoice && isset($selectedHandover->invoice_url) && $selectedHandover->invoice_url)
                                        <a href="{{ $selectedHandover->invoice_url }}" target="_blank" class="handover-invoice-link">
                                            {{ $selectedHandover->timetec_proforma_invoice }}
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                        <a href="{{ route('timetec-proforma-invoice.download', ['invoiceNo' => $selectedHandover->timetec_proforma_invoice]) }}" class="handover-invoice-link" title="Download PDF">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    @elseif(isset($selectedHandover->timetec_proforma_invoice) && $selectedHandover->timetec_proforma_invoice)
                                        <p class="handover-invoice-text" style="margin: 0;">
                                            {{ $selectedHandover->timetec_proforma_invoice }}
                                        </p>
                                    @else
                                        <p class="handover-info-na" style="margin: 0;">N/A</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Admin Reseller Remark -->
                            <div class="handover-info-box">
                                <h4 class="handover-info-title">
                                    Admin Reseller Remark:
                                    @if(isset($selectedHandover->admin_reseller_remark) && $selectedHandover->admin_reseller_remark)
                                        <span
                                            wire:click="$set('showAdminRemarkModal', true)"
                                            style="color: #3b82f6; cursor: pointer; text-decoration: underline; margin-left: 0.25rem;"
                                            onmouseover="this.style.color='#2563eb'"
                                            onmouseout="this.style.color='#3b82f6'">
                                            View
                                        </span>
                                    @else
                                        <span style="color: #6b7280; margin-left: 0.25rem;">
                                            No Remark
                                        </span>
                                    @endif
                                </h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Remark Modal -->
    @if(isset($showRemarkModal) && $showRemarkModal)
        <div class="handover-modal-overlay" style="z-index: 10000;">
            <div class="handover-modal-container">
                <div class="handover-modal-background" wire:click="$set('showRemarkModal', false)"></div>
                <div class="handover-modal-panel" style="max-width: 600px;">
                    <div class="handover-modal-body">
                        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; border-left: 4px solid #3b82f6;">
                            <p style="white-space: pre-wrap; word-wrap: break-word; color: #1f2937; line-height: 1.6;">{{ $selectedHandover->reseller_remark ?? 'No remarks' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Admin Remark Modal -->
    @if(isset($showAdminRemarkModal) && $showAdminRemarkModal)
        <div class="handover-modal-overlay" style="z-index: 10000;">
            <div class="handover-modal-container">
                <div class="handover-modal-background" wire:click="$set('showAdminRemarkModal', false)"></div>
                <div class="handover-modal-panel" style="max-width: 600px;">
                    <div class="handover-modal-body">
                        <div style="background: #f9fafb; padding: 1rem; border-radius: 8px; border-left: 4px solid #10b981;">
                            <p style="white-space: pre-wrap; word-wrap: break-word; color: #1f2937; line-height: 1.6;">{{ $selectedHandover->admin_reseller_remark ?? 'No remarks' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endif
