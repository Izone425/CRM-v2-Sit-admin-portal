<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/finance.blade.php -->
<style>
    /* Container styling */
    .finance-container {
        grid-column: 1 / -1;
        width: 100%;
    }

    /* Main layout with grid setup */
    .dashboard-layout {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 15px;
    }

    /* Group column styling */
    .group-column {
        padding-right: 10px;
        width: 230px;
    }

    .group-box {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px 15px;
        cursor: pointer;
        transition: all 0.2s;
        border-top: 4px solid transparent;
        display: flex;
        flex-direction: column;
        justify-content: center;
        margin-bottom: 15px;
        width: 100%;
        text-align: center;
        max-height: 82px;
        max-width: 220px;
    }

    .group-box:hover {
        background-color: #f9fafb;
        transform: translateX(3px);
    }

    .group-box.selected {
        background-color: #f0f7ff;
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-top-width: 5px;
        outline: 2px solid currentColor;
        outline-offset: -2px;
    }

    .group-box.selected.group-einvoice { outline-color: #7c3aed; background-color: rgba(124, 58, 237, 0.08); }
    .group-box.selected.group-reseller-portal { outline-color: #2563eb; background-color: rgba(37, 99, 235, 0.08); }
    .group-box.selected.group-generate-invoice { outline-color: #f59e0b; background-color: rgba(245, 158, 11, 0.08); }
    .group-box.selected.group-admin-portal { outline-color: #10b981; background-color: rgba(16, 185, 129, 0.08); }
    .group-box.selected.group-finance-handover { outline-color: #e11d48; background-color: rgba(225, 29, 72, 0.08); }
    .group-box.selected.group-fe { outline-color: #8b5cf6; background-color: rgba(139, 92, 246, 0.08); }
    .group-box.selected.group-fg { outline-color: #f97316; background-color: rgba(249, 115, 22, 0.08); }
    .group-box.selected.group-reseller-commission { outline-color: #0891b2; background-color: rgba(8, 145, 178, 0.08); }
    .group-box.selected.group-offset-payment { outline-color: #6366f1; background-color: rgba(99, 102, 241, 0.08); }

    .group-info {
        display: flex;
        flex-direction: column;
    }

    .group-title {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 8px;
        text-align: left;
    }

    .group-desc {
        font-size: 12px;
        color: #6b7280;
    }

    .group-count {
        font-size: 24px;
        font-weight: bold;
    }

    /* GROUP COLORS */
    .group-einvoice { border-top-color: #7c3aed; }
    .group-einvoice .group-count { color: #7c3aed; }

    .group-reseller-portal { border-top-color: #2563eb; }
    .group-reseller-portal .group-count { color: #2563eb; }

    .group-generate-invoice { border-top-color: #f59e0b; }
    .group-generate-invoice .group-count { color: #f59e0b; }

    .group-admin-portal { border-top-color: #10b981; }
    .group-admin-portal .group-count { color: #10b981; }

    .group-finance-handover { border-top-color: #e11d48; }
    .group-finance-handover .group-count { color: #e11d48; }

    .group-fe { border-top-color: #8b5cf6; }
    .group-fe .group-count { color: #8b5cf6; }

    .group-fg { border-top-color: #f97316; }
    .group-fg .group-count { color: #f97316; }

    .group-reseller-commission { border-top-color: #0891b2; }
    .group-reseller-commission .group-count { color: #0891b2; }

    .group-offset-payment { border-top-color: #6366f1; }
    .group-offset-payment .group-count { color: #6366f1; }

    /* Group container layout */
    .group-container {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        border-right: none;
        padding-right: 0;
        padding-bottom: 20px;
        margin-bottom: 20px;
        text-align: center;
    }

    /* Category column styling */
    .category-column {
        padding-right: 10px;
    }

    /* Category container */
    .category-container {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        border-right: 1px solid #e5e7eb;
        padding-right: 10px;
        max-height: 80vh;
        overflow-y: auto;
    }

    /* Stat box styling */
    .stat-box {
        background-color: white;
        width: 100%;
        min-height: 65px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        margin-bottom: 8px;
    }

    .stat-box:hover {
        background-color: #f9fafb;
        transform: translateX(3px);
    }

    .stat-box.selected {
        transform: translateX(5px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.18);
    }

    .stat-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
    }

    .stat-count {
        font-size: 20px;
        font-weight: bold;
        margin: 0;
        line-height: 1.2;
    }

    .stat-label {
        color: #6b7280;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.2;
    }

    /* Content area */
    .content-column {
        min-height: 600px;
    }

    .content-area {
        min-height: 600px;
    }

    .content-area .fi-ta {
        margin-top: 0;
    }

    .content-area .fi-ta-content {
        padding: 0.75rem !important;
    }

    /* Hint message */
    .hint-message {
        text-align: center;
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px dashed #d1d5db;
        height: 530px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .hint-message h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .hint-message p {
        color: #6b7280;
    }

    /* COLOR CODING FOR STAT BOXES */
    .new-task { border-left: 4px solid #2563eb; }
    .new-task .stat-count { color: #2563eb; }

    .rejected { border-left: 4px solid #ef4444; }
    .rejected .stat-count { color: #ef4444; }

    .completed { border-left: 4px solid #10b981; }
    .completed .stat-count { color: #10b981; }

    .reseller-all { border-left: 4px solid #6b7280; }
    .reseller-all .stat-count { color: #6b7280; }

    .reseller-completed { border-left: 4px solid #06b6d4; }
    .reseller-completed .stat-count { color: #06b6d4; }

    .pending-payment { border-left: 4px solid #f59e0b; }
    .pending-payment .stat-count { color: #f59e0b; }

    /* Selected states for categories */
    .stat-box.selected.new-task { background-color: rgba(37, 99, 235, 0.1); border-left-width: 6px; outline: 2px solid #2563eb; outline-offset: -2px; }
    .stat-box.selected.reseller-all { background-color: rgba(107, 114, 128, 0.1); border-left-width: 6px; outline: 2px solid #6b7280; outline-offset: -2px; }
    .stat-box.selected.rejected { background-color: rgba(239, 68, 68, 0.1); border-left-width: 6px; outline: 2px solid #ef4444; outline-offset: -2px; }
    .stat-box.selected.completed { background-color: rgba(16, 185, 129, 0.1); border-left-width: 6px; outline: 2px solid #10b981; outline-offset: -2px; }
    .stat-box.selected.reseller-completed { background-color: rgba(6, 182, 212, 0.1); border-left-width: 6px; outline: 2px solid #06b6d4; outline-offset: -2px; }
    .stat-box.selected.pending-payment { background-color: rgba(245, 158, 11, 0.1); border-left-width: 6px; outline: 2px solid #f59e0b; outline-offset: -2px; }

    /* Animation for tab switching */
    [x-transition] {
        transition: all 0.2s ease-out;
    }

    /* Custom table styling for finance tables */
    .finance-table {
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .finance-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .finance-table th {
        background-color: #f8fafc;
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border-bottom: 1px solid #e5e7eb;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.05em;
    }

    .finance-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f3f4f6;
        color: #6b7280;
    }

    .finance-table tr:hover {
        background-color: #f9fafb;
    }

    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 500;
        text-transform: uppercase;
    }

    .status-new {
        background-color: #dbeafe;
        color: #1e40af;
    }

    .status-rejected {
        background-color: #fee2e2;
        color: #dc2626;
    }

    .status-completed {
        background-color: #d1fae5;
        color: #065f46;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .dashboard-layout {
            grid-template-columns: 100%;
            grid-template-rows: auto auto;
        }

        .group-container {
            display: grid;
            grid-template-columns: repeat(1, 1fr);
            gap: 10px;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .category-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
            max-height: none;
        }

        .stat-box:hover, .stat-box.selected {
            transform: translateY(-3px);
        }
    }

    @media (max-width: 768px) {
        .category-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .category-container {
            grid-template-columns: 1fr;
        }
    }
</style>

@php
    // Calculate E-Invoice counts
    use App\Models\EInvoiceHandover;
    use App\Models\ResellerHandover;
    use App\Models\FinanceInvoice;
    use App\Models\AdminPortalInvoice;
    use App\Models\FinanceHandover;
    use App\Models\ResellerHandoverFe;
    use App\Models\ResellerHandoverFg;

    // Get counts for different statuses
    $newCount = EInvoiceHandover::where('status', 'New')->count();
    $rejectedCount = EInvoiceHandover::where('status', 'Rejected')->count();
    $completedCount = EInvoiceHandover::where('status', 'Completed')->count();

    // Total count
    $totalEInvoiceCount = EInvoiceHandover::count();

    // Reseller Handover counts
    $resellerPendingPaymentCount = 0;
    $resellerPendingFinanceCount = ResellerHandover::where('status', 'pending_timetec_finance')->count();
    $resellerCompletedCount = ResellerHandover::where('status', 'completed')->count();
    $resellerAllCount = ResellerHandover::count();
    $resellerPendingInvoiceConfirmationCount = 0;
    $resellerMainBoxCount = $resellerPendingFinanceCount;

    // Finance Invoice counts
    $resellerPortalCount = FinanceInvoice::where('portal_type', 'reseller')->count();
    $adminPortalCount = FinanceInvoice::where('portal_type', 'admin')->count();
    $handoverPortalCount = FinanceInvoice::whereIn('portal_type', ['software', 'hardware', 'reseller_handover'])->count();
    $totalInvoiceCount = FinanceInvoice::where('status', 'new')->count();

    // Admin Portal Finance Invoice counts
    $adminPortalCompletedCount = AdminPortalInvoice::count();

    // Finance Handover counts
    $financeHandoverPendingPaymentCount = FinanceHandover::where('status', 'Pending Payment')->count();
    $financeHandoverCompletedCount = FinanceHandover::where('status', 'Completed')->count();
    $financeHandoverMainBoxCount = $financeHandoverPendingPaymentCount;

    // FE Handover counts
    $fePendingPaymentCount = ResellerHandoverFe::where('status', 'pending_reseller_payment')
        ->where(function ($query) {
            $query->whereNull('reseller_payment_completed')
                  ->orWhere('reseller_payment_completed', false);
        })->count();
    $fePendingFinancePaymentCount = ResellerHandoverFe::where('status', 'pending_finance_payment')->count();
    $feCompletedCount = ResellerHandoverFe::where('status', 'completed')->count();
    $feMainBoxCount = $fePendingFinancePaymentCount;

    // FG Renewal Quotation counts
    $fgPendingFinanceCount = ResellerHandoverFg::where('status', 'pending_timetec_finance')->count();
    $fgCompletedCount = ResellerHandoverFg::where('status', 'completed')->count();
    $fgMainBoxCount = $fgPendingFinanceCount;

    // Reseller Commission counts
    $rcPendingFinanceCount = \App\Models\ResellerCommissionHandover::where('status', 'pending_finance')->count();
    $rcCompletedCount = \App\Models\ResellerCommissionHandover::where('status', 'completed')->count();
    $rcMainBoxCount = $rcPendingFinanceCount;

    // Offset Payment counts
    $offsetPaymentNewCount = \App\Models\OffsetPaymentHandover::where('status', 'new')->count();
    $offsetPaymentCompletedCount = \App\Models\OffsetPaymentHandover::where('status', 'completed')->count();
    $offsetPaymentRejectedCount = \App\Models\OffsetPaymentHandover::where('status', 'rejected')->count();
    $offsetPaymentMainBoxCount = $offsetPaymentNewCount;
@endphp

<div id="finance-container" class="finance-container"
    x-data="{
        selectedGroup: null,
        selectedStat: null,
        totalInvoiceCount: {{ $totalInvoiceCount }},
        resellerPortalCount: {{ $resellerPortalCount }},
        adminPortalCount: {{ $adminPortalCount }},
        handoverPortalCount: {{ $handoverPortalCount }},
        adminPortalCompletedCount: {{ $adminPortalCompletedCount }},
        resellerPendingInvoiceConfirmationCount: {{ $resellerPendingInvoiceConfirmationCount }},
        resellerPendingPaymentCount: {{ $resellerPendingPaymentCount }},
        resellerPendingFinanceCount: {{ $resellerPendingFinanceCount }},
        resellerCompletedCount: {{ $resellerCompletedCount }},
        resellerAllCount: {{ $resellerAllCount }},
        resellerMainBoxCount: {{ $resellerMainBoxCount }},
        financeHandoverPendingPaymentCount: {{ $financeHandoverPendingPaymentCount }},
        financeHandoverCompletedCount: {{ $financeHandoverCompletedCount }},
        financeHandoverMainBoxCount: {{ $financeHandoverMainBoxCount }},
        fePendingPaymentCount: {{ $fePendingPaymentCount }},
        fePendingFinancePaymentCount: {{ $fePendingFinancePaymentCount }},
        feCompletedCount: {{ $feCompletedCount }},
        feMainBoxCount: {{ $feMainBoxCount }},
        fgPendingFinanceCount: {{ $fgPendingFinanceCount }},
        fgCompletedCount: {{ $fgCompletedCount }},
        fgMainBoxCount: {{ $fgMainBoxCount }},
        rcPendingFinanceCount: {{ $rcPendingFinanceCount }},
        rcCompletedCount: {{ $rcCompletedCount }},
        rcMainBoxCount: {{ $rcMainBoxCount }},
        offsetPaymentNewCount: {{ $offsetPaymentNewCount }},
        offsetPaymentCompletedCount: {{ $offsetPaymentCompletedCount }},
        offsetPaymentRejectedCount: {{ $offsetPaymentRejectedCount }},
        offsetPaymentMainBoxCount: {{ $offsetPaymentMainBoxCount }},
        setSelectedGroup(value) {
            if (this.selectedGroup === value) {
                this.selectedGroup = null;
                this.selectedStat = null;
            } else {
                this.selectedGroup = value;
                // Set default stat for E-Invoice group
                if (value === 'einvoice') {
                    this.selectedStat = 'new-task';
                }
                // Set default stat for Reseller Handover group
                if (value === 'reseller') {
                    this.selectedStat = 'reseller-pending-finance';
                }
                // Set default stat for Generate Invoice group
                if (value === 'generate-invoice') {
                    this.selectedStat = 'invoice-reseller-portal';
                }
                // Set default stat for Admin Portal group
                if (value === 'admin-portal') {
                    this.selectedStat = 'admin-portal-completed';
                }
                // Set default stat for Self Billed Invoice group
                if (value === 'self-billed-invoice') {
                    this.selectedStat = 'invoice-autocount';
                }
                // Set default stat for Finance Handover group
                if (value === 'finance-handover') {
                    this.selectedStat = 'fh-pending-payment';
                }
                // Set default stat for FE group
                if (value === 'fe') {
                    this.selectedStat = 'fe-pending-payment';
                }
                // Set default stat for FG Renewal Quotation group
                if (value === 'fg') {
                    this.selectedStat = 'fg-pending-finance';
                }
                // Set default stat for Offset Payment group
                if (value === 'offset-payment') {
                    this.selectedStat = 'offset-new';
                }
                // Set default stat for Reseller Commission group
                if (value === 'reseller-commission') {
                    this.selectedStat = 'rc-pending-finance';
                }
            }
        },

        setSelectedStat(value) {
            if (this.selectedStat === value) {
                this.selectedStat = null;
            } else {
                this.selectedStat = value;
            }
        },

        init() {
            console.log('Finance dashboard Alpine component initialized');
        }
    }"
    x-init="init()">

    <!-- Dashboard layout -->
    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with groups -->
        <div class="group-column">
            <div class="group-container">
                <!-- Group: E-Invoice -->
                <div class="group-box group-einvoice"
                     :class="{'selected': selectedGroup === 'einvoice'}"
                     @click="setSelectedGroup('einvoice')">
                    <div class="group-title">E-Invoice Registration</div>
                    @if($newCount > 0)
                        <div class="group-count">{{ $newCount }}</div>
                    @endif
                </div>

                <!-- Group: Offset Payment -->
                <div class="group-box group-offset-payment"
                     :class="{'selected': selectedGroup === 'offset-payment'}"
                     @click="setSelectedGroup('offset-payment')">
                    <div class="group-title">Offset Payment</div>
                    @if($offsetPaymentMainBoxCount > 0)
                        <div class="group-count">{{ $offsetPaymentMainBoxCount }}</div>
                    @endif
                </div>

                <!-- Group: Reseller Handover -->
                <div class="group-box group-reseller-portal"
                     :class="{'selected': selectedGroup === 'reseller'}"
                     @click="setSelectedGroup('reseller')">
                    <div class="group-title">Reseller Portal</div>
                    <div class="group-count" x-show="resellerMainBoxCount > 0" x-text="resellerMainBoxCount"></div>
                </div>

                <!-- Group: Self Billed Invoice -->
                <div class="group-box group-generate-invoice"
                     :class="{'selected': selectedGroup === 'self-billed-invoice'}"
                     @click="setSelectedGroup('self-billed-invoice')">
                    <div class="group-title">Self Billed E-Invoice</div>
                    @if($totalInvoiceCount > 0)
                        <div class="group-count">{{ $totalInvoiceCount }}</div>
                    @endif
                </div>

                <!-- Group: Finance Handover -->
                <div class="group-box group-finance-handover"
                     :class="{'selected': selectedGroup === 'finance-handover'}"
                     @click="setSelectedGroup('finance-handover')">
                    <div class="group-title">Installation Payment</div>
                    <div class="group-count" x-show="financeHandoverMainBoxCount > 0" x-text="financeHandoverMainBoxCount"></div>
                </div>

                <!-- Group: FE -->
                <div class="group-box group-fe"
                     :class="{'selected': selectedGroup === 'fe'}"
                     @click="setSelectedGroup('fe')">
                    <div class="group-title">Bill as End User</div>
                    <div class="group-count" x-show="feMainBoxCount > 0" x-text="feMainBoxCount"></div>
                </div>

                <!-- Group: FG Renewal Quotation -->
                <div class="group-box group-fg"
                     :class="{'selected': selectedGroup === 'fg'}"
                     @click="setSelectedGroup('fg')">
                    <div class="group-title">USD With Invoice</div>
                    <div class="group-count" x-show="fgMainBoxCount > 0" x-text="fgMainBoxCount"></div>
                </div>

                <!-- Group: Reseller Commission -->
                <div class="group-box group-reseller-commission"
                     :class="{'selected': selectedGroup === 'reseller-commission'}"
                     @click="setSelectedGroup('reseller-commission')">
                    <div class="group-title">Reseller Commission</div>
                    <div class="group-count" x-show="rcMainBoxCount > 0" x-text="rcMainBoxCount"></div>
                </div>

                {{-- <!-- Group: Admin Portal -->
                <div class="group-box group-admin-portal"
                     :class="{'selected': selectedGroup === 'admin-portal'}"
                     @click="setSelectedGroup('admin-portal')">
                    <div class="group-title">Admin Portal</div>
                    <div class="group-count" x-text="adminPortalCompletedCount"></div>
                </div> --}}
            </div>
        </div>

        <!-- Right content area -->
        <div class="content-column">
            <!-- E-Invoice Categories -->
            <div class="category-container" x-show="selectedGroup === 'einvoice'">
                <div class="stat-box new-task"
                     :class="{'selected': selectedStat === 'new-task'}"
                     @click="setSelectedStat('new-task')">
                    <div class="stat-info">
                        <div class="stat-label">New Task</div>
                    </div>
                    <div class="stat-count">{{ $newCount }}</div>
                </div>

                <div class="stat-box rejected"
                     :class="{'selected': selectedStat === 'rejected'}"
                     @click="setSelectedStat('rejected')">
                    <div class="stat-info">
                        <div class="stat-label">Rejected</div>
                    </div>
                    <div class="stat-count">{{ $rejectedCount }}</div>
                </div>

                <div class="stat-box completed"
                     :class="{'selected': selectedStat === 'completed'}"
                     @click="setSelectedStat('completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count">{{ $completedCount }}</div>
                </div>
            </div>

            <!-- Reseller Handover Categories -->
            <div class="category-container" x-show="selectedGroup === 'reseller'">
                {{-- <div class="stat-box pending-payment"
                     :class="{'selected': selectedStat === 'reseller-pending-invoice-confirmation'}"
                     @click="setSelectedStat('reseller-pending-invoice-confirmation')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Invoice Confirmation</div>
                    </div>
                    <div class="stat-count" x-text="resellerPendingInvoiceConfirmationCount"></div>
                </div>

                <div class="stat-box new-task"
                     :class="{'selected': selectedStat === 'reseller-pending-payment'}"
                     @click="setSelectedStat('reseller-pending-payment')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Reseller Payment</div>
                    </div>
                    <div class="stat-count" x-text="resellerPendingPaymentCount"></div>
                </div> --}}

                <div class="stat-box rejected"
                     :class="{'selected': selectedStat === 'reseller-pending-finance'}"
                     @click="setSelectedStat('reseller-pending-finance')">
                    <div class="stat-info">
                        <div class="stat-label">Pending TimeTec Finance</div>
                    </div>
                    <div class="stat-count" x-text="resellerPendingFinanceCount"></div>
                </div>

                <div class="stat-box reseller-all"
                     :class="{'selected': selectedStat === 'reseller-all'}"
                     @click="setSelectedStat('reseller-all')">
                    <div class="stat-info">
                        <div class="stat-label">All Handover</div>
                    </div>
                    <div class="stat-count" x-text="resellerAllCount"></div>
                </div>
            </div>

            <!-- Generate Invoice Categories -->
            <div class="category-container" x-show="selectedGroup === 'generate-invoice'">
                <div class="stat-box new-task"
                     :class="{'selected': selectedStat === 'invoice-reseller-portal'}"
                     @click="setSelectedStat('invoice-reseller-portal')">
                    <div class="stat-info">
                        <div class="stat-label">Reseller Portal</div>
                    </div>
                    <div class="stat-count" x-text="resellerPortalCount"></div>
                </div>

                <div class="stat-box rejected"
                     :class="{'selected': selectedStat === 'invoice-admin-portal'}"
                     @click="setSelectedStat('invoice-admin-portal')">
                    <div class="stat-info">
                        <div class="stat-label">Admin Portal</div>
                    </div>
                    <div class="stat-count" x-text="adminPortalCount"></div>
                </div>
                <div class="stat-box reseller-completed"
                        :class="{'selected': selectedStat === 'invoice-admin-portal'}">
                    <div class="stat-info">
                        <div class="stat-label">Software Handover</div>
                    </div>
                    <div class="stat-count">0</div>
                </div>

                <div class="stat-box reseller-all"
                        :class="{'selected': selectedStat === 'invoice-admin-portal'}">
                    <div class="stat-info">
                        <div class="stat-label">Reseller Handover</div>
                    </div>
                    <div class="stat-count">0</div>
                </div>
            </div>

            <!-- Admin Portal Categories -->
            <div class="category-container" x-show="selectedGroup === 'admin-portal'">
                <div class="stat-box completed"
                     :class="{'selected': selectedStat === 'admin-portal-completed'}"
                     @click="setSelectedStat('admin-portal-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count" x-text="adminPortalCompletedCount"></div>
                </div>
            </div>

            <!-- Self Billed Invoice Categories -->
            <div class="category-container" x-show="selectedGroup === 'self-billed-invoice'">
                <div class="stat-box reseller-all"
                     :class="{'selected': selectedStat === 'invoice-autocount'}"
                     @click="setSelectedStat('invoice-autocount')">
                    <div class="stat-info">
                        <div class="stat-label">AutoCount Invoice</div>
                    </div>
                    <div class="stat-count" x-text="totalInvoiceCount"></div>
                </div>
            </div>

            <!-- Finance Handover Categories -->
            <div class="category-container" x-show="selectedGroup === 'finance-handover'">
                <div class="stat-box pending-payment"
                     :class="{'selected': selectedStat === 'fh-pending-payment'}"
                     @click="setSelectedStat('fh-pending-payment')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Payment</div>
                    </div>
                    <div class="stat-count" x-text="financeHandoverPendingPaymentCount"></div>
                </div>

                <div class="stat-box completed"
                     :class="{'selected': selectedStat === 'fh-completed'}"
                     @click="setSelectedStat('fh-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count" x-text="financeHandoverCompletedCount"></div>
                </div>
            </div>

            <!-- FE Categories -->
            <div class="category-container" x-show="selectedGroup === 'fe'">
                <div class="stat-box new-task"
                     :class="{'selected': selectedStat === 'fe-pending-payment'}"
                     @click="setSelectedStat('fe-pending-payment')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Reseller Payment</div>
                    </div>
                    <div class="stat-count" x-text="fePendingPaymentCount"></div>
                </div>

                <div class="stat-box pending-payment"
                     :class="{'selected': selectedStat === 'fe-pending-finance-payment'}"
                     @click="setSelectedStat('fe-pending-finance-payment')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Finance Payment</div>
                    </div>
                    <div class="stat-count" x-text="fePendingFinancePaymentCount"></div>
                </div>

                <div class="stat-box completed"
                     :class="{'selected': selectedStat === 'fe-completed'}"
                     @click="setSelectedStat('fe-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count" x-text="feCompletedCount"></div>
                </div>
            </div>

            <!-- FG Renewal Quotation Categories -->
            <div class="category-container" x-show="selectedGroup === 'fg'">
                <div class="stat-box pending-payment"
                     :class="{'selected': selectedStat === 'fg-pending-finance'}"
                     @click="setSelectedStat('fg-pending-finance')">
                    <div class="stat-info">
                        <div class="stat-label">Pending TimeTec Finance</div>
                    </div>
                    <div class="stat-count" x-text="fgPendingFinanceCount"></div>
                </div>

                <div class="stat-box completed"
                     :class="{'selected': selectedStat === 'fg-completed'}"
                     @click="setSelectedStat('fg-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count" x-text="fgCompletedCount"></div>
                </div>
            </div>

            <!-- Reseller Commission Categories -->
            <div class="category-container" x-show="selectedGroup === 'reseller-commission'">
                <div class="stat-box pending-payment"
                     :class="{'selected': selectedStat === 'rc-pending-finance'}"
                     @click="setSelectedStat('rc-pending-finance')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Finance</div>
                    </div>
                    <div class="stat-count" x-text="rcPendingFinanceCount"></div>
                </div>

                <div class="stat-box completed"
                     :class="{'selected': selectedStat === 'rc-completed'}"
                     @click="setSelectedStat('rc-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count" x-text="rcCompletedCount"></div>
                </div>
            </div>

            <!-- Offset Payment Categories -->
            <div class="category-container" x-show="selectedGroup === 'offset-payment'">
                <div class="stat-box new-task"
                     :class="{'selected': selectedStat === 'offset-new'}"
                     @click="setSelectedStat('offset-new')">
                    <div class="stat-info">
                        <div class="stat-label">New</div>
                    </div>
                    <div class="stat-count" x-text="offsetPaymentNewCount"></div>
                </div>

                <div class="stat-box rejected"
                     :class="{'selected': selectedStat === 'offset-rejected'}"
                     @click="setSelectedStat('offset-rejected')">
                    <div class="stat-info">
                        <div class="stat-label">Rejected</div>
                    </div>
                    <div class="stat-count" x-text="offsetPaymentRejectedCount"></div>
                </div>

                <div class="stat-box completed"
                     :class="{'selected': selectedStat === 'offset-completed'}"
                     @click="setSelectedStat('offset-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count" x-text="offsetPaymentCompletedCount"></div>
                </div>
            </div>

            <br>
            <!-- Content Area for Tables -->
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a group to continue' : 'Select a category to view data'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on E-Invoice or Reseller Handover to see categories' : 'Click on any category box to display the corresponding information'"></p>
                </div>

                <!-- E-Invoice Tables -->
                <!-- New Task Table -->
                <div x-show="selectedStat === 'new-task'" x-transition>
                    <livewire:finance-dashboard.e-invoice-handover-new />
                </div>

                <!-- Rejected Table -->
                <div x-show="selectedStat === 'rejected'" x-transition>
                    <livewire:finance-dashboard.e-invoice-handover-rejected />
                </div>

                <!-- Completed Table -->
                <div x-show="selectedStat === 'completed'" x-transition>
                    <livewire:finance-dashboard.e-invoice-handover-completed />
                </div>

                <!-- Reseller Handover Tables -->
                <!-- Reseller Pending Invoice Confirmation Table -->
                <div x-show="selectedStat === 'reseller-pending-invoice-confirmation'" x-transition>
                    <livewire:finance-pending-invoice-confirmation />
                </div>

                <!-- Reseller Pending Payment Table -->
                <div x-show="selectedStat === 'reseller-pending-payment'" x-transition>
                    <livewire:admin-reseller-handover-pending-payment />
                </div>

                <!-- Reseller Pending Finance Table -->
                <div x-show="selectedStat === 'reseller-pending-finance'" x-transition>
                    <livewire:admin-reseller-handover-pending-timetec-finance />
                </div>

                <!-- Reseller Completed Table -->
                <div x-show="selectedStat === 'reseller-completed'" x-transition>
                    <livewire:admin-reseller-handover-completed />
                </div>

                <!-- Reseller All Handover Table -->
                <div x-show="selectedStat === 'reseller-all'" x-transition>
                    <livewire:admin-reseller-handover-all />
                </div>

                <!-- Generate Invoice - Reseller Portal -->
                <div x-show="selectedStat === 'invoice-reseller-portal'" x-transition>
                    <livewire:finance-invoice.generate-invoice-reseller-portal-finance />
                </div>

                <!-- Generate Invoice - Admin Portal -->
                <div x-show="selectedStat === 'invoice-admin-portal'" x-transition>
                    <livewire:finance-invoice.generate-invoice-admin-portal-finance />
                </div>

                <!-- Admin Portal - Completed -->
                <div x-show="selectedStat === 'admin-portal-completed'" x-transition>
                    <livewire:admin-portal-finance-invoice-completed />
                </div>

                <!-- Self Billed Invoice - AutoCount Invoice -->
                <div x-show="selectedStat === 'invoice-autocount'" x-transition>
                    <livewire:finance-invoice.auto-count-invoice-table />
                </div>

                <!-- Finance Handover - Pending Payment -->
                <div x-show="selectedStat === 'fh-pending-payment'" x-transition>
                    <livewire:admin-finance-dashboard.finance-handover-pending-payment-table />
                </div>

                <!-- Finance Handover - Completed -->
                <div x-show="selectedStat === 'fh-completed'" x-transition>
                    <livewire:admin-finance-dashboard.finance-handover-completed-table />
                </div>

                <!-- FE - Pending Reseller Payment -->
                <div x-show="selectedStat === 'fe-pending-payment'" x-transition>
                    <livewire:admin-finance-dashboard.fe-handover-pending-payment-table />
                </div>

                <!-- FE - Pending Finance Payment -->
                <div x-show="selectedStat === 'fe-pending-finance-payment'" x-transition>
                    <livewire:admin-finance-dashboard.fe-handover-pending-finance-payment-table source="finance" />
                </div>

                <!-- FE - Completed -->
                <div x-show="selectedStat === 'fe-completed'" x-transition>
                    <livewire:admin-finance-dashboard.fe-handover-completed-table />
                </div>

                <!-- FG - Pending TimeTec Finance -->
                <div x-show="selectedStat === 'fg-pending-finance'" x-transition>
                    <livewire:admin-finance-dashboard.fg-handover-pending-finance-table />
                </div>

                <!-- FG - Completed -->
                <div x-show="selectedStat === 'fg-completed'" x-transition>
                    <livewire:admin-finance-dashboard.fg-handover-completed-table />
                </div>

                <!-- Reseller Commission - Pending Finance -->
                <div x-show="selectedStat === 'rc-pending-finance'" x-transition>
                    <livewire:admin-finance-dashboard.rc-pending-finance-table />
                </div>

                <!-- Reseller Commission - Completed -->
                <div x-show="selectedStat === 'rc-completed'" x-transition>
                    <livewire:admin-finance-dashboard.rc-completed-table />
                </div>

                <!-- Offset Payment - New -->
                <div x-show="selectedStat === 'offset-new'" x-transition>
                    <livewire:admin-finance-dashboard.offset-payment-new-table />
                </div>

                <!-- Offset Payment - Rejected -->
                <div x-show="selectedStat === 'offset-rejected'" x-transition>
                    <livewire:admin-finance-dashboard.offset-payment-rejected-table />
                </div>

                <!-- Offset Payment - Completed -->
                <div x-show="selectedStat === 'offset-completed'" x-transition>
                    <livewire:admin-finance-dashboard.offset-payment-completed-table />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Finance dashboard component setup
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the finance component
        window.resetFinanceDashboard = function() {
            const container = document.getElementById('finance-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
                console.log('Finance dashboard reset via global function');
            }
        };

        // Listen for custom reset event
        window.addEventListener('reset-finance-dashboard', function() {
            window.resetFinanceDashboard();
        });
    });
</script>
