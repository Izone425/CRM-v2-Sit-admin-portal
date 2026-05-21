<x-filament-panels::page>
<style>
    /* Container styling */
    .hardware-handover-container {
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
        text-align: center; /* Changed from center to left */
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

    .group-box.selected.group-reseller-portal { outline-color: #2563eb; background-color: rgba(37, 99, 235, 0.08); }
    .group-box.selected.group-generate-invoice { outline-color: #f59e0b; background-color: rgba(245, 158, 11, 0.08); }
    .group-box.selected.group-admin-portal { outline-color: #8b5cf6; background-color: rgba(139, 92, 246, 0.08); }
    .group-box.selected.group-reseller-inquiry { outline-color: #06b6d4; background-color: rgba(6, 145, 178, 0.08); }
    .group-box.selected.group-database-creation { outline-color: #10b981; background-color: rgba(16, 185, 129, 0.08); }
    .group-box.selected.group-reseller-fd { outline-color: #e6c63b; background-color: rgba(230, 198, 59, 0.08); }
    .group-box.selected.group-reseller-fe { outline-color: #ec4899; background-color: rgba(236, 72, 153, 0.08); }
    .group-box.selected.group-reseller-ff { outline-color: #f97316; background-color: rgba(249, 115, 22, 0.08); }
    .group-box.selected.group-reseller-fg { outline-color: #06b6d4; background-color: rgba(6, 182, 212, 0.08); }
    .group-box.selected.group-reseller-handover { outline-color: #e6c63b; background-color: rgba(230, 198, 59, 0.08); }
    .group-box.selected.group-installation-payment { outline-color: #ec4899; background-color: rgba(236, 72, 153, 0.08); }

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
    .group-reseller-portal { border-top-color: #2563eb; }
    .group-reseller-portal .group-count { color: #2563eb; }

    .group-generate-invoice { border-top-color: #f59e0b; }
    .group-generate-invoice .group-count { color: #f59e0b; }

    .group-admin-portal { border-top-color: #8b5cf6; }
    .group-admin-portal .group-count { color: #8b5cf6; }

    .group-reseller-inquiry { border-top-color: #06b6d4; }
    .group-reseller-inquiry .group-count { color: #06b6d4; }

    .group-database-creation { border-top-color: #10b981; }
    .group-database-creation .group-count { color: #10b981; }

    .group-reseller-fd { border-top-color: #e6c63b; }
    .group-reseller-fd .group-count { color: #e6c63b; }

    .group-reseller-fe { border-top-color: #ec4899; }
    .group-reseller-fe .group-count { color: #ec4899; }

    .group-reseller-ff { border-top-color: #f97316; }
    .group-reseller-ff .group-count { color: #f97316; }

    .group-reseller-fg { border-top-color: #06b6d4; }
    .group-reseller-fg .group-count { color: #06b6d4; }

    .group-reseller-handover { border-top-color: #e6c63b; }
    .group-reseller-handover .group-count { color: #e6c63b; }

    .group-installation-payment { border-top-color: #ec4899; }
    .group-installation-payment .group-count { color: #ec4899; }

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
        background-color: #f9fafb;
        transform: translateX(5px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
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

    /* NEW COLOR CODING FOR STAT BOXES */
    .all-items { border-left: 4px solid #6b7280; }
    .all-items .stat-count { color: #6b7280; }

    .reseller-all { border-left: 4px solid #3b82f6; }
    .reseller-all .stat-count { color: #3b82f6; }

    .reseller-new { border-left: 4px solid #10b981; }
    .reseller-new .stat-count { color: #10b981; }

    .reseller-pending-invoice { border-left: 4px solid #f59e0b; }
    .reseller-pending-invoice .stat-count { color: #f59e0b; }

    .reseller-pending-license { border-left: 4px solid #8b5cf6; }
    .reseller-pending-license .stat-count { color: #8b5cf6; }

    .reseller-pending-payment { border-left: 4px solid #ef4444; }
    .reseller-pending-payment .stat-count { color: #ef4444; }

    .reseller-completed { border-left: 4px solid #06b6d4; }
    .reseller-completed .stat-count { color: #06b6d4; }

    /* Selected states for categories */
    .stat-box.selected.all-items { background-color: rgba(107, 114, 128, 0.1); border-left-width: 6px; outline: 2px solid #6b7280; outline-offset: -2px; }
    .stat-box.selected.reseller-all { background-color: rgba(59, 130, 246, 0.1); border-left-width: 6px; outline: 2px solid #3b82f6; outline-offset: -2px; }
    .stat-box.selected.reseller-new { background-color: rgba(16, 185, 129, 0.1); border-left-width: 6px; outline: 2px solid #10b981; outline-offset: -2px; }
    .stat-box.selected.reseller-pending-invoice { background-color: rgba(245, 158, 11, 0.1); border-left-width: 6px; outline: 2px solid #f59e0b; outline-offset: -2px; }
    .stat-box.selected.reseller-pending-license { background-color: rgba(139, 92, 246, 0.1); border-left-width: 6px; outline: 2px solid #8b5cf6; outline-offset: -2px; }
    .stat-box.selected.reseller-pending-payment { background-color: rgba(239, 68, 68, 0.1); border-left-width: 6px; outline: 2px solid #ef4444; outline-offset: -2px; }
    .stat-box.selected.reseller-completed { background-color: rgba(6, 182, 212, 0.1); border-left-width: 6px; outline: 2px solid #06b6d4; outline-offset: -2px; }

    /* Animation for tab switching */
    [x-transition] {
        transition: all 0.2s ease-out;
    }

    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .dashboard-layout {
            grid-template-columns: 100%;
            grid-template-rows: auto auto;
        }

        .group-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .category-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
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
        .group-container {
            grid-template-columns: repeat(2, 1fr);
        }
        .category-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .group-container,
        .category-container {
            grid-template-columns: 1fr;
        }
    }
</style>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    @php
        $newCount = \App\Models\ResellerHandover::where('status', 'new')->count();
        $pendingInvoiceCount = \App\Models\ResellerHandover::where('status', 'pending_timetec_invoice')->count();
        $pendingLicenseCount = \App\Models\ResellerHandover::where('status', 'pending_timetec_license')->count();
        $pendingResellerPaymentCount = \App\Models\ResellerHandover::where('status', 'pending_reseller_payment')->count();
        $pendingTimetecFinanceCount = \App\Models\ResellerHandover::where('status', 'pending_timetec_finance')->count();
        $completedCount = \App\Models\ResellerHandover::where('status', 'completed')->count();
        $allCount = \App\Models\ResellerHandover::count();
        $resellerPortalHandoverCount = $newCount + $pendingInvoiceCount + $pendingLicenseCount;
        $pendingTimetecAdminCount = $newCount + $pendingInvoiceCount + $pendingLicenseCount;

        // Finance Invoice counts
        $resellerPortalCount = \App\Models\FinanceInvoice::where('portal_type', 'reseller')->count();
        $adminPortalCount = \App\Models\FinanceInvoice::where('portal_type', 'admin')->count();
        $handoverPortalCount = \App\Models\FinanceInvoice::whereIn('portal_type', ['software', 'hardware', 'reseller_handover'])->count();
        $totalInvoiceCount = $resellerPortalCount + $adminPortalCount + $handoverPortalCount;

        // Admin Portal Finance Invoice counts
        $adminPortalNewCount = \App\Models\CrmInvoiceDetail::pendingInvoices()->get()->count();
        $adminPortalCompletedCount = \App\Models\AdminPortalInvoice::count();
        $adminPortalAllCount = $adminPortalNewCount;

        // Reseller Inquiry counts
        $inquiryAllCount = \App\Models\ResellerInquiry::count();
        $inquiryNewCount = \App\Models\ResellerInquiry::where('status', 'new')->count();
        $inquiryRejectedCount = \App\Models\ResellerInquiry::where('status', 'rejected')->count();
        $inquiryCompletedCount = \App\Models\ResellerInquiry::where('status', 'completed')->count();
        $inquiryDraftCount = \App\Models\ResellerInquiry::where('status', 'draft')->count();

        // Database Creation counts
        $databaseCreationAllCount = \App\Models\ResellerDatabaseCreation::count();
        $databaseCreationNewCount = \App\Models\ResellerDatabaseCreation::where('status', 'new')->count();
        $databaseCreationCompletedCount = \App\Models\ResellerDatabaseCreation::where('status', 'completed')->count();
        $databaseCreationRejectedCount = \App\Models\ResellerDatabaseCreation::where('status', 'rejected')->count();
        $databaseCreationDraftCount = \App\Models\ResellerDatabaseCreation::where('status', 'draft')->count();

        // FD (Bill as Reseller) counts
        $fdNewCount = \App\Models\ResellerHandoverFd::where('status', 'new')->count();
        $fdPendingInvoiceCount = \App\Models\ResellerHandoverFd::where('status', 'pending_timetec_invoice')->count();
        $fdPendingLicenseCount = \App\Models\ResellerHandoverFd::where('status', 'pending_timetec_license')->count();
        $fdPendingTimetecAdminCount = $fdNewCount + $fdPendingInvoiceCount + $fdPendingLicenseCount;
        $fdPendingResellerPaymentCount = \App\Models\ResellerHandoverFd::where('status', 'pending_reseller_payment')->count();
        $fdPendingFinancePaymentCount = \App\Models\ResellerHandoverFd::where('status', 'pending_finance_payment')->count();
        $fdAllCount = \App\Models\ResellerHandoverFd::count();

        // FE (Bill as End User) counts
        $feNewCount = \App\Models\ResellerHandoverFe::where('status', 'new')->count();
        $fePendingInvoiceCount = \App\Models\ResellerHandoverFe::where('status', 'pending_timetec_invoice')->count();
        $fePendingLicenseCount = \App\Models\ResellerHandoverFe::where('status', 'pending_timetec_license')->count();
        $fePendingTimetecAdminCount = $feNewCount + $fePendingInvoiceCount + $fePendingLicenseCount;
        $fePendingResellerPaymentCount = \App\Models\ResellerHandoverFe::where('status', 'pending_reseller_payment')->count();
        $fePendingFinancePaymentCount = \App\Models\ResellerHandoverFe::where('status', 'pending_finance_payment')->count();
        $feAllCount = \App\Models\ResellerHandoverFe::count();

        // FF (USD With Quotation) counts
        $ffPendingTimetecAdminCount = \App\Models\ResellerHandoverFf::where('status', 'new')->count();
        $ffAllCount = \App\Models\ResellerHandoverFf::count();

        // FG (USD With Invoice) counts
        $fgPendingTimetecAdminCount = \App\Models\ResellerHandoverFg::whereIn('status', ['new', 'pending_timetec_invoice', 'pending_timetec_license'])->count();
        $fgPendingResellerPaymentCount = \App\Models\ResellerHandoverFg::where('status', 'pending_reseller_payment')->count();
        $fgAllCount = \App\Models\ResellerHandoverFg::count();

        // Installation Payment counts (placeholder - update with actual logic)
        $installationPaymentCount = 0;
    @endphp

    <div id="admin-reseller-container" class="hardware-handover-container"
        x-data="{
            selectedGroup: null,
            selectedStat: null,
            allCount: {{ $allCount }},
            newCount: {{ $newCount }},
            pendingInvoiceCount: {{ $pendingInvoiceCount }},
            pendingLicenseCount: {{ $pendingLicenseCount }},
            pendingResellerPaymentCount: {{ $pendingResellerPaymentCount }},
            completedCount: {{ $completedCount }},
            resellerPortalHandoverCount: {{ $resellerPortalHandoverCount }},
            totalInvoiceCount: {{ $totalInvoiceCount }},
            resellerPortalCount: {{ $resellerPortalCount }},
            adminPortalCount: {{ $adminPortalCount }},
            handoverPortalCount: {{ $handoverPortalCount }},
            totalInvoiceCount: {{ $totalInvoiceCount }},
            adminPortalNewCount: {{ $adminPortalNewCount }},
            adminPortalCompletedCount: {{ $adminPortalCompletedCount }},
            adminPortalAllCount: {{ $adminPortalAllCount }},
            inquiryAllCount: {{ $inquiryAllCount }},
            inquiryNewCount: {{ $inquiryNewCount }},
            inquiryRejectedCount: {{ $inquiryRejectedCount }},
            inquiryCompletedCount: {{ $inquiryCompletedCount }},
            inquiryDraftCount: {{ $inquiryDraftCount }},
            databaseCreationAllCount: {{ $databaseCreationAllCount }},
            databaseCreationNewCount: {{ $databaseCreationNewCount }},
            databaseCreationCompletedCount: {{ $databaseCreationCompletedCount }},
            databaseCreationRejectedCount: {{ $databaseCreationRejectedCount }},
            databaseCreationDraftCount: {{ $databaseCreationDraftCount }},
            installationPaymentCount: {{ $installationPaymentCount }},
            pendingTimetecAdminCount: {{ $pendingTimetecAdminCount }},
            pendingTimetecFinanceCount: {{ $pendingTimetecFinanceCount }},
            fdPendingTimetecAdminCount: {{ $fdPendingTimetecAdminCount }},
            fdPendingResellerPaymentCount: {{ $fdPendingResellerPaymentCount }},
            fdPendingFinancePaymentCount: {{ $fdPendingFinancePaymentCount }},
            fdAllCount: {{ $fdAllCount }},
            fePendingTimetecAdminCount: {{ $fePendingTimetecAdminCount }},
            fePendingResellerPaymentCount: {{ $fePendingResellerPaymentCount }},
            fePendingFinancePaymentCount: {{ $fePendingFinancePaymentCount }},
            feAllCount: {{ $feAllCount }},
            ffPendingTimetecAdminCount: {{ $ffPendingTimetecAdminCount }},
            ffAllCount: {{ $ffAllCount }},
            fgPendingTimetecAdminCount: {{ $fgPendingTimetecAdminCount }},
            fgPendingResellerPaymentCount: {{ $fgPendingResellerPaymentCount }},
            fgAllCount: {{ $fgAllCount }},

            setSelectedGroup(value) {
                if (this.selectedGroup === value) {
                    this.selectedGroup = null;
                    this.selectedStat = null;
                } else {
                    this.selectedGroup = value;
                    if (value === 'reseller-handover') {
                        this.selectedStat = 'reseller-pending-timetec-admin';
                    } else if (value === 'generate-invoice') {
                        this.selectedStat = 'invoice-reseller-portal';
                    } else if (value === 'admin-portal') {
                        this.selectedStat = 'admin-portal-new';
                    } else if (value === 'reseller-inquiry') {
                        this.selectedStat = 'inquiry-new';
                    } else if (value === 'database-creation') {
                        this.selectedStat = 'database-creation-new';
                    } else if (value === 'reseller-fd') {
                        this.selectedStat = 'fd-pending-timetec-admin';
                    } else if (value === 'reseller-fe') {
                        this.selectedStat = 'fe-pending-timetec-admin';
                    } else if (value === 'reseller-ff') {
                        this.selectedStat = 'ff-pending-timetec-admin';
                    } else if (value === 'reseller-fg') {
                        this.selectedStat = 'fg-pending-timetec-admin';
                    } else if (value === 'installation-payment') {
                        this.selectedStat = null;
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
                console.log('Admin reseller Alpine component initialized');
            }
        }"
        x-init="init()"
        @refresh-leadowner-tables.window="
            fetch('{{ route('admin.reseller-handover.counts') }}')
                .then(response => response.json())
                .then(data => {
                    newCount = data.new;
                    pendingInvoiceCount = data.pending_timetec_invoice;
                    pendingLicenseCount = data.pending_timetec_license;
                    pendingResellerPaymentCount = data.pending_reseller_payment || 0;
                    completedCount = data.completed;
                    resellerPortalHandoverCount = newCount + pendingInvoiceCount + pendingLicenseCount;
                    allCount = data.all || 0;
                })
                .catch(error => console.error('Error fetching counts:', error));
        ">

        <div class="dashboard-layout" wire:poll.300s>
            <!-- Left sidebar with groups -->
            <div class="group-column">
                <div class="group-container">
                    <div class="group-box group-reseller-portal"
                            :class="{'selected': selectedGroup === 'reseller-handover'}"
                            @click="setSelectedGroup('reseller-handover')">
                        <div class="group-title">Reseller Portal</div>
                        <div class="group-count" x-show="resellerPortalHandoverCount > 0" x-text="resellerPortalHandoverCount"></div>
                    </div>

                    <div class="group-box group-generate-invoice"
                            :class="{'selected': selectedGroup === 'generate-invoice'}"
                            @click="setSelectedGroup('generate-invoice')">
                        <div class="group-title">Self Billed E-Invoice</div>
                    </div>

                    <div class="group-box group-admin-portal"
                            :class="{'selected': selectedGroup === 'admin-portal'}"
                            @click="setSelectedGroup('admin-portal')">
                        <div class="group-title">Admin Portal</div>
                        <div class="group-count" x-show="adminPortalAllCount > 0" x-text="adminPortalAllCount"></div>
                    </div>

                    <div class="group-box group-reseller-inquiry"
                            :class="{'selected': selectedGroup === 'reseller-inquiry'}"
                            @click="setSelectedGroup('reseller-inquiry')">
                        <div class="group-title">Renewal Inquiry</div>
                        <div class="group-count" x-show="inquiryNewCount > 0" x-text="inquiryNewCount"></div>
                    </div>

                    <div class="group-box group-database-creation"
                            :class="{'selected': selectedGroup === 'database-creation'}"
                            @click="setSelectedGroup('database-creation')">
                        <div class="group-title">Trial Account</div>
                        <div class="group-count" x-show="databaseCreationNewCount > 0" x-text="databaseCreationNewCount"></div>
                    </div>

                    <div class="group-box group-reseller-fd"
                            :class="{'selected': selectedGroup === 'reseller-fd'}"
                            @click="setSelectedGroup('reseller-fd')">
                        <div class="group-title">Bill as Reseller</div>
                        <div class="group-count" x-show="fdPendingTimetecAdminCount > 0" x-text="fdPendingTimetecAdminCount"></div>
                    </div>

                    <div class="group-box group-reseller-fe"
                            :class="{'selected': selectedGroup === 'reseller-fe'}"
                            @click="setSelectedGroup('reseller-fe')">
                        <div class="group-title">Bill as End User</div>
                        <div class="group-count" x-show="fePendingTimetecAdminCount > 0" x-text="fePendingTimetecAdminCount"></div>
                    </div>

                    <div class="group-box group-reseller-ff"
                            :class="{'selected': selectedGroup === 'reseller-ff'}"
                            @click="setSelectedGroup('reseller-ff')">
                        <div class="group-title">USD With Quotation</div>
                        <div class="group-count" x-show="ffPendingTimetecAdminCount > 0" x-text="ffPendingTimetecAdminCount"></div>
                    </div>

                    <div class="group-box group-reseller-fg"
                            :class="{'selected': selectedGroup === 'reseller-fg'}"
                            @click="setSelectedGroup('reseller-fg')">
                        <div class="group-title">USD With Invoice</div>
                        <div class="group-count" x-show="fgPendingTimetecAdminCount > 0" x-text="fgPendingTimetecAdminCount"></div>
                    </div>

                    {{-- <div class="group-box group-reseller-fe"
                            :class="{'selected': selectedGroup === 'reseller-fe'}"
                            @click="setSelectedGroup('reseller-fe')">
                        <div class="group-title">USD With Invoice</div>
                        <div class="group-count" x-text="fePendingTimetecAdminCount"></div>
                    </div> --}}
<!--
                    <div class="group-box group-reseller-handover"
                            :class="{'selected': selectedGroup === 'reseller-handover'}"
                            @click="setSelectedGroup('database-creation')">
                        <div class="group-title">Reseller Handover</div>
                        <div class="group-count" x-text="databaseCreationNewCount"></div>
                    </div>

                    <div class="group-box group-installation-payment"
                            :class="{'selected': selectedGroup === 'installation-payment'}"
                            @click="setSelectedGroup('installation-payment')">
                        <div class="group-title">Installation Payment</div>
                        <div class="group-count" x-text="installationPaymentCount"></div>
                    </div> -->
                </div>
            </div>

            <!-- Right content area -->
            <div class="content-column">
                <!-- Category Container -->
                <div class="category-container" x-show="selectedGroup === 'reseller-handover'">
                    <div class="stat-box reseller-pending-license"
                            :class="{'selected': selectedStat === 'reseller-pending-timetec-admin'}"
                            @click="setSelectedStat('reseller-pending-timetec-admin')">
                        <div class="stat-info">
                            <div class="stat-label">Pending TimeTec Admin</div>
                        </div>
                        <div class="stat-count" x-text="pendingTimetecAdminCount"></div>
                    </div>

                    <div class="stat-box reseller-pending-invoice"
                            :class="{'selected': selectedStat === 'reseller-pending-payment'}"
                            @click="setSelectedStat('reseller-pending-payment')">
                        <div class="stat-info">
                            <div class="stat-label">Pending Reseller Payment</div>
                        </div>
                        <div class="stat-count" x-text="pendingResellerPaymentCount"></div>
                    </div>

                    <div class="stat-box reseller-pending-license"
                            :class="{'selected': selectedStat === 'reseller-pending-timetec-finance'}"
                            @click="setSelectedStat('reseller-pending-timetec-finance')">
                        <div class="stat-info">
                            <div class="stat-label">Pending TimeTec Finance</div>
                        </div>
                        <div class="stat-count" x-text="pendingTimetecFinanceCount"></div>
                    </div>

                    <div class="stat-box reseller-all"
                            :class="{'selected': selectedStat === 'reseller-all'}"
                            @click="setSelectedStat('reseller-all')">
                        <div class="stat-info">
                            <div class="stat-label">All Handovers</div>
                        </div>
                        <div class="stat-count" x-text="allCount"></div>
                    </div>
                </div>

                <!-- Generate Invoice Categories -->
                <div class="category-container" x-show="selectedGroup === 'generate-invoice'">
                    <div class="stat-box reseller-new"
                            :class="{'selected': selectedStat === 'invoice-reseller-portal'}"
                            @click="setSelectedStat('invoice-reseller-portal')">
                        <div class="stat-info">
                            <div class="stat-label">Reseller Portal</div>
                        </div>
                        <div class="stat-count" x-text="resellerPortalCount"></div>
                    </div>

                    <div class="stat-box reseller-pending-invoice"
                            :class="{'selected': selectedStat === 'invoice-admin-portal'}"
                            @click="setSelectedStat('invoice-admin-portal')">
                        <div class="stat-info">
                            <div class="stat-label">Admin Portal</div>
                        </div>
                        <div class="stat-count" x-text="adminPortalCount"></div>
                    </div>

                    <div class="stat-box reseller-completed"
                            :class="{'selected': selectedStat === 'invoice-admin-handover'}"
                            @click="setSelectedStat('invoice-admin-handover')">
                        <div class="stat-info">
                            <div class="stat-label">Handover</div>
                        </div>
                        <div class="stat-count" x-text="handoverPortalCount"></div>
                    </div>

                    <div class="stat-box reseller-all"
                            :class="{'selected': selectedStat === 'invoice-autocount'}"
                            @click="setSelectedStat('invoice-autocount')">
                        <div class="stat-info">
                            <div class="stat-label">AutoCount Invoice</div>
                        </div>
                        <div class="stat-count" x-text="totalInvoiceCount"></div>
                    </div>
                </div>

                <!-- Admin Portal Categories -->
                <div class="category-container" x-show="selectedGroup === 'admin-portal'">
                    <div class="stat-box reseller-new"
                            :class="{'selected': selectedStat === 'admin-portal-new'}"
                            @click="setSelectedStat('admin-portal-new')">
                        <div class="stat-info">
                            <div class="stat-label">New</div>
                        </div>
                        <div class="stat-count" x-text="adminPortalNewCount"></div>
                    </div>

                    <!-- <div class="stat-box reseller-completed"
                            :class="{'selected': selectedStat === 'admin-portal-completed'}"
                            @click="setSelectedStat('admin-portal-completed')">
                        <div class="stat-info">
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-count" x-text="adminPortalCompletedCount"></div>
                    </div> -->
                </div>

                <!-- Reseller Inquiry Categories -->
                <div class="category-container" x-show="selectedGroup === 'reseller-inquiry'">
                    <div class="stat-box reseller-new"
                            :class="{'selected': selectedStat === 'inquiry-new'}"
                            @click="setSelectedStat('inquiry-new')">
                        <div class="stat-info">
                            <div class="stat-label">New</div>
                        </div>
                        <div class="stat-count" x-text="inquiryNewCount"></div>
                    </div>

                    <div class="stat-box reseller-pending-invoice"
                            :class="{'selected': selectedStat === 'inquiry-rejected'}"
                            @click="setSelectedStat('inquiry-rejected')">
                        <div class="stat-info">
                            <div class="stat-label">Rejected</div>
                        </div>
                        <div class="stat-count" x-text="inquiryRejectedCount"></div>
                    </div>

                    <div class="stat-box reseller-completed"
                            :class="{'selected': selectedStat === 'inquiry-completed'}"
                            @click="setSelectedStat('inquiry-completed')">
                        <div class="stat-info">
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-count" x-text="inquiryCompletedCount"></div>
                    </div>

                    <div class="stat-box reseller-all"
                            :class="{'selected': selectedStat === 'inquiry-all'}"
                            @click="setSelectedStat('inquiry-all')">
                        <div class="stat-info">
                            <div class="stat-label">All Inquiries</div>
                        </div>
                        <div class="stat-count" x-text="inquiryAllCount"></div>
                    </div>
                </div>

                <!-- Database Creation Categories -->
                <div class="category-container" x-show="selectedGroup === 'database-creation'">
                    <div class="stat-box reseller-new"
                            :class="{'selected': selectedStat === 'database-creation-new'}"
                            @click="setSelectedStat('database-creation-new')">
                        <div class="stat-info">
                            <div class="stat-label">New</div>
                        </div>
                        <div class="stat-count" x-text="databaseCreationNewCount"></div>
                    </div>

                    <div class="stat-box reseller-pending-invoice"
                            :class="{'selected': selectedStat === 'database-creation-rejected'}"
                            @click="setSelectedStat('database-creation-rejected')">
                        <div class="stat-info">
                            <div class="stat-label">Rejected</div>
                        </div>
                        <div class="stat-count" x-text="databaseCreationRejectedCount"></div>
                    </div>

                    <div class="stat-box reseller-completed"
                            :class="{'selected': selectedStat === 'database-creation-completed'}"
                            @click="setSelectedStat('database-creation-completed')">
                        <div class="stat-info">
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-count" x-text="databaseCreationCompletedCount"></div>
                    </div>

                    <div class="stat-box reseller-all"
                            :class="{'selected': selectedStat === 'database-creation-all'}"
                            @click="setSelectedStat('database-creation-all')">
                        <div class="stat-info">
                            <div class="stat-label">All Requests</div>
                        </div>
                        <div class="stat-count" x-text="databaseCreationAllCount"></div>
                    </div>
                </div>

                <!-- Reseller Portal FD Categories -->
                <div class="category-container" x-show="selectedGroup === 'reseller-fd'">
                    <div class="stat-box reseller-pending-license"
                            :class="{'selected': selectedStat === 'fd-pending-timetec-admin'}"
                            @click="setSelectedStat('fd-pending-timetec-admin')">
                        <div class="stat-info">
                            <div class="stat-label">Pending TimeTec Admin</div>
                        </div>
                        <div class="stat-count" x-text="fdPendingTimetecAdminCount"></div>
                    </div>

                    <div class="stat-box reseller-pending-invoice"
                            :class="{'selected': selectedStat === 'fd-pending-reseller-payment'}"
                            @click="setSelectedStat('fd-pending-reseller-payment')">
                        <div class="stat-info">
                            <div class="stat-label">Pending Reseller Payment</div>
                        </div>
                        <div class="stat-count" x-text="fdPendingResellerPaymentCount"></div>
                    </div>

                    {{-- <div class="stat-box reseller-completed"
                            :class="{'selected': selectedStat === 'fd-pending-finance-payment'}"
                            @click="setSelectedStat('fd-pending-finance-payment')">
                        <div class="stat-info">
                            <div class="stat-label">Pending Finance Payment</div>
                        </div>
                        <div class="stat-count" x-text="fdPendingFinancePaymentCount"></div>
                    </div> --}}

                    <div class="stat-box reseller-all"
                            :class="{'selected': selectedStat === 'fd-all'}"
                            @click="setSelectedStat('fd-all')">
                        <div class="stat-info">
                            <div class="stat-label">All Handovers</div>
                        </div>
                        <div class="stat-count" x-text="fdAllCount"></div>
                    </div>
                </div>

                <!-- Reseller Portal FE Categories -->
                <div class="category-container" x-show="selectedGroup === 'reseller-fe'">
                    <div class="stat-box reseller-pending-license"
                            :class="{'selected': selectedStat === 'fe-pending-timetec-admin'}"
                            @click="setSelectedStat('fe-pending-timetec-admin')">
                        <div class="stat-info">
                            <div class="stat-label">Pending TimeTec Admin</div>
                        </div>
                        <div class="stat-count" x-text="fePendingTimetecAdminCount"></div>
                    </div>

                    <div class="stat-box reseller-pending-invoice"
                            :class="{'selected': selectedStat === 'fe-pending-reseller-payment'}"
                            @click="setSelectedStat('fe-pending-reseller-payment')">
                        <div class="stat-info">
                            <div class="stat-label">Pending Reseller Payment</div>
                        </div>
                        <div class="stat-count" x-text="fePendingResellerPaymentCount"></div>
                    </div>

                    <div class="stat-box reseller-completed"
                            :class="{'selected': selectedStat === 'fe-pending-finance-payment'}"
                            @click="setSelectedStat('fe-pending-finance-payment')">
                        <div class="stat-info">
                            <div class="stat-label">Pending Finance Payment</div>
                        </div>
                        <div class="stat-count" x-text="fePendingFinancePaymentCount"></div>
                    </div>

                    <div class="stat-box reseller-all"
                            :class="{'selected': selectedStat === 'fe-all'}"
                            @click="setSelectedStat('fe-all')">
                        <div class="stat-info">
                            <div class="stat-label">All Handovers</div>
                        </div>
                        <div class="stat-count" x-text="feAllCount"></div>
                    </div>
                </div>
                <!-- Reseller Portal FF Categories -->
                <div class="category-container" x-show="selectedGroup === 'reseller-ff'">
                    <div class="stat-box reseller-pending-license"
                            :class="{'selected': selectedStat === 'ff-pending-timetec-admin'}"
                            @click="setSelectedStat('ff-pending-timetec-admin')">
                        <div class="stat-info">
                            <div class="stat-label">Pending TimeTec Admin</div>
                        </div>
                        <div class="stat-count" x-text="ffPendingTimetecAdminCount"></div>
                    </div>

                    <div class="stat-box reseller-all"
                            :class="{'selected': selectedStat === 'ff-all'}"
                            @click="setSelectedStat('ff-all')">
                        <div class="stat-info">
                            <div class="stat-label">All Handovers</div>
                        </div>
                        <div class="stat-count" x-text="ffAllCount"></div>
                    </div>
                </div>
                <!-- Reseller Portal FG Categories -->
                <div class="category-container" x-show="selectedGroup === 'reseller-fg'">
                    <div class="stat-box reseller-pending-license"
                            :class="{'selected': selectedStat === 'fg-pending-timetec-admin'}"
                            @click="setSelectedStat('fg-pending-timetec-admin')">
                        <div class="stat-info">
                            <div class="stat-label">Pending TimeTec Admin</div>
                        </div>
                        <div class="stat-count" x-text="fgPendingTimetecAdminCount"></div>
                    </div>

                    <div class="stat-box reseller-pending-payment"
                            :class="{'selected': selectedStat === 'fg-pending-reseller-payment'}"
                            @click="setSelectedStat('fg-pending-reseller-payment')">
                        <div class="stat-info">
                            <div class="stat-label">Pending Reseller Payment</div>
                        </div>
                        <div class="stat-count" x-text="fgPendingResellerPaymentCount"></div>
                    </div>

                    <div class="stat-box reseller-all"
                            :class="{'selected': selectedStat === 'fg-all'}"
                            @click="setSelectedStat('fg-all')">
                        <div class="stat-info">
                            <div class="stat-label">All Handovers</div>
                        </div>
                        <div class="stat-count" x-text="fgAllCount"></div>
                    </div>
                </div>

                <br>
                <!-- Content Area for Tables -->
                <div class="content-area">
                    <!-- Display hint message when nothing is selected -->
                    <div class="hint-message" x-show="selectedGroup === null || (selectedStat === null && selectedGroup !== 'reseller-inquiry' && selectedGroup !== 'database-creation' && selectedGroup !== 'reseller-fd' && selectedGroup !== 'reseller-fe' && selectedGroup !== 'reseller-ff' && selectedGroup !== 'reseller-fg' && selectedGroup !== 'installation-payment')" x-transition>
                        <h3 x-text="selectedGroup === null ? 'Select a dashboard to continue' : 'Select a category to view data'"></h3>
                        <p x-text="selectedGroup === null ? 'Click on the Reseller Handover box to see categories' : 'Click on any of the category boxes to display the corresponding information'"></p>
                    </div>

                    <!-- All Handovers -->
                    <div x-show="selectedStat === 'reseller-all'" x-transition>
                        <livewire:admin-reseller-handover-all />
                    </div>

                    <!-- Pending TimeTec Admin (Combined: New, Pending Invoice, Pending License) -->
                    <div x-show="selectedStat === 'reseller-pending-timetec-admin'" x-transition>
                        <livewire:admin-reseller-handover-pending-timetec-admin />
                    </div>

                    <!-- Pending Invoice -->
                    <div x-show="selectedStat === 'inquiry-all'" x-transition>
                        <livewire:admin-reseller-inquiry-all />
                    </div>

                    <!-- Reseller Inquiry - New -->
                    <div x-show="selectedStat === 'inquiry-new'" x-transition>
                        <livewire:admin-reseller-inquiry-new />
                    </div>

                    <!-- Reseller Inquiry - Rejected -->
                    <div x-show="selectedStat === 'inquiry-rejected'" x-transition>
                        <livewire:admin-reseller-inquiry-rejected />
                    </div>

                    <!-- Reseller Inquiry - Completed -->
                    <div x-show="selectedStat === 'inquiry-completed'" x-transition>
                        <livewire:admin-reseller-inquiry-completed />
                    </div>

                    <!-- Pending Reseller Payment -->
                    <div x-show="selectedStat === 'reseller-pending-payment'" x-transition>
                        <livewire:admin-reseller-handover-pending-payment />
                    </div>

                    <!-- Pending TimeTec Finance -->
                    <div x-show="selectedStat === 'reseller-pending-timetec-finance'" x-transition>
                        <livewire:admin-reseller-handover-pending-timetec-finance />
                    </div>

                    <!-- Completed -->
                    <div x-show="selectedStat === 'reseller-completed'" x-transition>
                        <livewire:admin-reseller-handover-completed />
                    </div>

                    <!-- Generate Invoice - Reseller Portal -->
                    <div x-show="selectedStat === 'invoice-reseller-portal'" x-transition>
                        <livewire:finance-invoice.generate-invoice-reseller-portal />
                    </div>

                    <!-- Generate Invoice - Admin Portal -->
                    <div x-show="selectedStat === 'invoice-admin-portal'" x-transition>
                        <livewire:finance-invoice.generate-invoice-admin-portal />
                    </div>

                    <!-- Generate Invoice - Handover Portal -->
                    <div x-show="selectedStat === 'invoice-admin-handover'" x-transition>
                        <livewire:finance-invoice.generate-invoice-handover />
                    </div>

                    <!-- AutoCount Invoice Table -->
                    <div x-show="selectedStat === 'invoice-autocount'" x-transition>
                        <livewire:finance-invoice.auto-count-invoice-table />
                    </div>

                    <!-- Admin Portal - New -->
                    <div x-show="selectedStat === 'admin-portal-new'" x-transition>
                        <livewire:admin-portal-finance-invoice-new />
                    </div>

                    <!-- Admin Portal - Completed -->
                    <div x-show="selectedStat === 'admin-portal-completed'" x-transition>
                        <livewire:admin-portal-finance-invoice-completed />
                    </div>

                    <!-- Database Creation - New -->
                    <div x-show="selectedStat === 'database-creation-new'" x-transition>
                        <livewire:admin-database-creation-new />
                    </div>

                    <!-- Database Creation - Rejected -->
                    <div x-show="selectedStat === 'database-creation-rejected'" x-transition>
                        <livewire:admin-database-creation-rejected />
                    </div>

                    <!-- Database Creation - Completed -->
                    <div x-show="selectedStat === 'database-creation-completed'" x-transition>
                        <livewire:admin-database-creation-completed />
                    </div>

                    <!-- Database Creation - Draft -->
                    <div x-show="selectedStat === 'database-creation-all'" x-transition>
                        <livewire:admin-database-creation-all />
                    </div>

                    <!-- Reseller Portal FD - Pending TimeTec Admin -->
                    <div x-show="selectedStat === 'fd-pending-timetec-admin'" x-transition>
                        <livewire:admin-reseller-handover-fd-pending-timetec-admin />
                    </div>

                    <!-- Reseller Portal FD - Pending Reseller Payment -->
                    <div x-show="selectedStat === 'fd-pending-reseller-payment'" x-transition>
                        <livewire:admin-reseller-handover-fd-pending-payment />
                    </div>

                    <!-- Reseller Portal FD - Pending Finance Payment -->
                    <div x-show="selectedStat === 'fd-pending-finance-payment'" x-transition>
                        <livewire:admin-finance-dashboard.fd-handover-pending-finance-payment-table />
                    </div>

                    <!-- Reseller Portal FD - All Handovers -->
                    <div x-show="selectedStat === 'fd-all'" x-transition>
                        <livewire:admin-reseller-handover-fd-all />
                    </div>

                    <!-- Reseller Portal FE - Pending TimeTec Admin -->
                    <div x-show="selectedStat === 'fe-pending-timetec-admin'" x-transition>
                        <livewire:admin-reseller-handover-fe-pending-timetec-admin />
                    </div>

                    <!-- Reseller Portal FE - Pending Reseller Payment -->
                    <div x-show="selectedStat === 'fe-pending-reseller-payment'" x-transition>
                        <livewire:admin-reseller-handover-fe-pending-payment />
                    </div>

                    <!-- Reseller Portal FE - Pending Finance Payment -->
                    <div x-show="selectedStat === 'fe-pending-finance-payment'" x-transition>
                        <livewire:admin-finance-dashboard.fe-handover-pending-finance-payment-table />
                    </div>

                    <!-- Reseller Portal FE - All Handovers -->
                    <div x-show="selectedStat === 'fe-all'" x-transition>
                        <livewire:admin-reseller-handover-fe-all />
                    </div>

                    <!-- Reseller Portal FF - Pending TimeTec Admin -->
                    <div x-show="selectedStat === 'ff-pending-timetec-admin'" x-transition>
                        <livewire:admin-reseller-handover-ff-pending-timetec-admin />
                    </div>

                    <!-- Reseller Portal FF - All Handovers -->
                    <div x-show="selectedStat === 'ff-all'" x-transition>
                        <livewire:admin-reseller-handover-ff-all />
                    </div>

                    <!-- Reseller Portal FG - Pending TimeTec Admin -->
                    <div x-show="selectedStat === 'fg-pending-timetec-admin'" x-transition>
                        <livewire:admin-reseller-handover-fg-pending-timetec-admin />
                    </div>

                    <!-- Reseller Portal FG - Pending Reseller Payment -->
                    <div x-show="selectedStat === 'fg-pending-reseller-payment'" x-transition>
                        <livewire:admin-reseller-handover-fg-pending-reseller-payment />
                    </div>

                    <!-- Reseller Portal FG - All Handovers -->
                    <div x-show="selectedStat === 'fg-all'" x-transition>
                        <livewire:admin-reseller-handover-fg-all />
                    </div>

                    <!-- Installation Payment -->
                    <div x-show="selectedGroup === 'installation-payment'" x-transition>
                        <div class="hint-message">
                            <h3>Installation Payment</h3>
                            <p>This section is under construction. Content will be added soon.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // When the page loads, setup handlers for admin reseller component
        document.addEventListener('DOMContentLoaded', function() {
            // Function to reset the admin reseller component
            window.resetAdminReseller = function() {
                const container = document.getElementById('admin-reseller-container');
                if (container && container.__x) {
                    container.__x.$data.selectedGroup = null;
                    container.__x.$data.selectedStat = null;
                    console.log('Admin reseller reset via global function');
                }
            };

            // Listen for our custom reset event
            window.addEventListener('reset-admin-reseller', function() {
                window.resetAdminReseller();
            });
        });

        document.addEventListener('livewire:init', () => {
            Livewire.on('refresh-leadowner-tables', () => {
                console.log('Refresh event received');
            });
        });
    </script>
</x-filament-panels::page>
