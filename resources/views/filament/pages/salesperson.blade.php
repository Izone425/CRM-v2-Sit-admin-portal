<style>
    /* Container styling */
    .lead-owner-container {
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
        background-color: #f9fafb;
        transform: translateX(5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

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
    .group-demo { border-top-color: #2563eb; }
    .group-pr { border-top-color: #8b5cf6; }
    .group-software { border-top-color: #10b981; }
    .group-hardware { border-top-color: #f59e0b; }
    .group-no-respond { border-top-color: #ec4899; }
    .group-others { border-top-color: #06b6d4; }

    .group-demo .group-count { color: #2563eb; }
    .group-pr .group-count { color: #8b5cf6; }
    .group-software .group-count { color: #10b981; }
    .group-hardware .group-count { color: #f59e0b; }
    .group-no-respond .group-count { color: #ec4899; }
    .group-others .group-count { color: #06b6d4; }

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
        grid-template-columns: repeat(6, 1fr);
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

    /* Column headers */
    .column-header {
        font-size: 14px;
        font-weight: 600;
        color: #4b5563;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 1px solid #e5e7eb;
    }

    /* NEW COLOR CODING FOR STAT BOXES */

    .demo-today { border-left: 4px solid #2563eb; }
    .demo-today .stat-count { color: #2563eb; }

    .demo-tmr { border-left: 4px solid #3b82f6; }
    .demo-tmr .stat-count { color: #3b82f6; }

    .pr-today { border-left: 4px solid #8b5cf6; }
    .pr-today .stat-count { color: #8b5cf6; }

    .pr-overdue { border-left: 4px solid #ef4444; }
    .pr-overdue .stat-count { color: #ef4444; }

    .software-handover-pending { border-left: 4px solid #10b981; }
    .software-handover-pending .stat-count { color: #10b981; }

    .software-handover-completed { border-left: 4px solid #34d399; }
    .software-handover-completed .stat-count { color: #34d399; }

    .hardware-handover-pending { border-left: 4px solid #f59e0b; }
    .hardware-handover-pending .stat-count { color: #f59e0b; }

    .hardware-handover-completed { border-left: 4px solid #fbbf24; }
    .hardware-handover-completed .stat-count { color: #fbbf24; }

    .transfer-lead { border-left: 4px solid #ec4899; }
    .transfer-lead .stat-count { color: #ec4899; }

    .follow-up-lead { border-left: 4px solid #d946ef; }
    .follow-up-lead .stat-count { color: #d946ef; }

    .group-rejected { border-top-color: #ef4444; }
    .group-rejected .group-count { color: #ef4444; }

    .debtor-follow-up-today {
        border-left: 4px solid #06b6d4;
    }
    .debtor-follow-up-today .stat-count {
        color: #06b6d4;
    }

    /* Debtor Follow Up - OVERDUE */
    .debtor-follow-up-overdue {
        border-left: 4px solid #0284c7;
    }
    .debtor-follow-up-overdue .stat-count {
        color: #0284c7;
    }

    /* HRDF Follow Up - TODAY */
    .hrdf-follow-up-today {
        border-left: 4px solid #0ea5e9;
    }
    .hrdf-follow-up-today .stat-count {
        color: #0ea5e9;
    }

    /* HRDF Follow Up - OVERDUE */
    .hrdf-follow-up-overdue {
        border-left: 4px solid #0369a1;
    }
    .hrdf-follow-up-overdue .stat-count {
        color: #0369a1;
    }

    .sales-pending-kickoff { border-left: 4px solid #059669; }
    .sales-pending-kickoff .stat-count { color: #059669; }

    .sales-completed-kickoff { border-left: 4px solid #047857; }
    .sales-completed-kickoff .stat-count { color: #047857; }

    .debtor-all { border-left: 4px solid #06b6d4; }
    .debtor-all .stat-count { color: #06b6d4; }

    .debtor-hrdf { border-left: 4px solid #0ea5e9; }
    .debtor-hrdf .stat-count { color: #0ea5e9; }

    .debtor-product { border-left: 4px solid #3b82f6; }
    .debtor-product .stat-count { color: #3b82f6; }

    .debtor-unpaid { border-left: 4px solid #ef4444; }
    .debtor-unpaid .stat-count { color: #ef4444; }

    .debtor-partial { border-left: 4px solid #f59e0b; }
    .debtor-partial .stat-count { color: #f59e0b; }

    /* Selected states for new categories */
    .stat-box.selected.demo-today { background-color: rgba(37, 99, 235, 0.05); border-left-width: 6px; }
    .stat-box.selected.demo-tmr { background-color: rgba(59, 130, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.pr-today { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.pr-overdue { background-color: rgba(239, 68, 68, 0.05); border-left-width: 6px; }
    .stat-box.selected.software-handover-pending { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }
    .stat-box.selected.software-handover-completed { background-color: rgba(52, 211, 153, 0.05); border-left-width: 6px; }
    .stat-box.selected.hardware-handover-pending { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .stat-box.selected.hardware-handover-completed { background-color: rgba(251, 191, 36, 0.05); border-left-width: 6px; }
    .stat-box.selected.transfer-lead { background-color: rgba(236, 72, 153, 0.05); border-left-width: 6px; }
    .stat-box.selected.follow-up-lead { background-color: rgba(217, 70, 239, 0.05); border-left-width: 6px; }
    .stat-box.selected.debtor-follow-up-today { background-color: rgba(6, 182, 212, 0.05); border-left-width: 6px; }
    .stat-box.selected.debtor-follow-up-overdue { background-color: rgba(6, 182, 212, 0.05); border-left-width: 6px; }
    .stat-box.selected.hrdf-follow-up-today { background-color: rgba(14, 165, 233, 0.05); border-left-width: 6px; }
    .stat-box.selected.hrdf-follow-up-overdue { background-color: rgba(14, 165, 233, 0.05); border-left-width: 6px; }
    .stat-box.selected.sales-pending-kickoff { background-color: rgba(5, 150, 105, 0.05); border-left-width: 6px; }
    .stat-box.selected.sales-completed-kickoff { background-color: rgba(4, 120, 87, 0.05); border-left-width: 6px; }
    .stat-box.selected.debtor-all { background-color: rgba(6, 182, 212, 0.05); border-left-width: 6px; }
    .stat-box.selected.debtor-hrdf { background-color: rgba(14, 165, 233, 0.05); border-left-width: 6px; }
    .stat-box.selected.debtor-product { background-color: rgba(59, 130, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.debtor-unpaid { background-color: rgba(239, 68, 68, 0.05); border-left-width: 6px; }
    .stat-box.selected.debtor-partial { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }
    .software-handover-rejected { border-left: 4px solid #ef4444; }
    .software-handover-rejected .stat-count { color: #ef4444; }
    .hardware-handover-rejected { border-left: 4px solid #ef4444; }
    .hardware-handover-rejected .stat-count { color: #ef4444; }
    .hrdf-handover-rejected { border-left: 4px solid #f97316; }
    .hrdf-handover-rejected .stat-count { color: #f97316; }
    .headcount-handover-rejected { border-left: 4px solid #faaa15; }
    .headcount-handover-rejected .stat-count { color: #faaa15; }

    /* Selected state for hardware rejected */
    .stat-box.selected.hardware-handover-rejected {
        background-color: rgba(239, 68, 68, 0.05);
        border-left-width: 6px;
    }

    /* Selected state for rejected */
    .stat-box.selected.software-handover-rejected {
        background-color: rgba(239, 68, 68, 0.05);
        border-left-width: 6px;
    }
    .stat-box.selected.headcount-handover-rejected {
        background-color: rgba(139, 92, 246, 0.05);
        border-left-width: 6px;
    }

    /* Finance Handover */
    .finance-handover-pending { border-left: 4px solid #7c3aed; }
    .finance-handover-pending .stat-count { color: #7c3aed; }
    .finance-handover-rejected { border-left: 4px solid #be123c; }
    .finance-handover-rejected .stat-count { color: #be123c; }
    .finance-handover-completed { border-left: 4px solid #059669; }
    .finance-handover-completed .stat-count { color: #059669; }
    .stat-box.selected.finance-handover-pending { background-color: rgba(124, 58, 237, 0.05); border-left-width: 6px; }
    .stat-box.selected.finance-handover-rejected { background-color: rgba(190, 18, 60, 0.05); border-left-width: 6px; }
    .stat-box.selected.finance-handover-completed { background-color: rgba(5, 150, 105, 0.05); border-left-width: 6px; }

    /* Table grid styling */
    .table-grid-container {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-bottom: 20px;
    }

    .table-grid-item {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .table-grid-item .fi-ta {
        margin: 0;
        height: 100%;
    }

    .table-grid-item .fi-ta-header {
        padding: 0.5rem !important;
    }

    /* Animation for tab switching */
    [x-transition] {
        transition: all 0.2s ease-out;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .dashboard-layout {
            grid-template-columns: 100%;
            grid-template-rows: auto auto;
        }

        .group-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
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
        .group-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (max-width: 640px) {
        .group-container,
        .category-container,
        .table-grid-container {
            grid-template-columns: 1fr;
        }
    }

    /* Todo List Styles */
    .todo-list-container {
        margin-top: 30px;
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        padding: 24px;
    }

    .todo-list-header {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }

    .todo-tabs {
        display: flex;
        gap: 12px;
        margin-bottom: 20px;
        border-bottom: 2px solid #e5e7eb;
    }

    .todo-tab {
        padding: 10px 20px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
    }

    .todo-tab:hover {
        color: #2563eb;
    }

    .todo-tab.active {
        color: #2563eb;
        border-bottom-color: #2563eb;
    }
</style>


@php
    // New Leads count
    $user = Auth::user();

    $demoTodayCount = app(\App\Livewire\SalespersonDashboard\DemoTodayTable::class)
        ->getTodayDemos()
        ->count();

    $demoTomorrowCount = app(\App\Livewire\SalespersonDashboard\DemoTmrTable::class)
        ->getTomorrowDemos()
        ->count();

    $prospectTodayCount = app(\App\Livewire\SalespersonDashboard\PrTodaySalespersonTable::class)
        ->getTodayProspects()
        ->count();

    $prospectOverdueCount = app(\App\Livewire\SalespersonDashboard\PrOverdueSalespersonTable::class)
        ->getOverdueProspects()
        ->count();

    $softwareHandoverNew = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverNew::class)
        ->getNewSoftwareHandovers()
        ->count();

    $softwareHandoverCompleted = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverCompleted::class)
        ->getNewSoftwareHandovers()
        ->count();

    $salesPendingKickOff = app(\App\Livewire\SalespersonDashboard\SalesPendingKickOff::class)
        ->getPendingKickOffs()
        ->count();

    $salesCompletedKickOff = app(\App\Livewire\SalespersonDashboard\SalesCompletedKickOff::class)
        ->getCompletedKickOffs()
        ->count();

    $hardwareHandoverNew = app(\App\Livewire\SalespersonDashboard\HardwareHandoverNew::class, [
        'currentDashboard' => 'Salesperson'
    ])->getHardwareHandoverCount();

    $hardwareHandoverCompleted = app(\App\Livewire\SalespersonDashboard\HardwareHandoverCompleted::class)
        ->getOverdueHardwareHandovers()
        ->count();

    $hardwareHandoverRejected = app(\App\Livewire\SalespersonDashboard\HardwareHandoverRejected::class, [
        'currentDashboard' => 'Salesperson'
    ])->getHardwareHandoverCount();

    $followUpLead = app(\App\Livewire\SalespersonDashboard\FollowUpLead::class)
        ->getFollowUpLead()
        ->count();

    $transferLead = app(\App\Livewire\SalespersonDashboard\TransferLead::class)
        ->getTransferLead()
        ->count();

    $debtorFollowUpToday = app(\App\Livewire\SalespersonDashboard\DebtorFollowUpTodayTable::class)
        ->getTodayProspects()
        ->count();

    $debtorFollowUpOverdue = app(\App\Livewire\SalespersonDashboard\DebtorFollowUpOverdueTable::class)
        ->getOverdueProspects()
        ->count();

    $hrdfFollowUpToday = app(\App\Livewire\SalespersonDashboard\HrdfFollowUpTodayTable::class)
        ->getTodayProspects()
        ->count();

    $hrdfFollowUpOverdue = app(\App\Livewire\SalespersonDashboard\HrdfFollowUpOverdueTable::class)
        ->getTodayProspects()
        ->count();

    $softwareHandoverRejected = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverRejected::class)
        ->getPendingKickOffs()
        ->count();

    $softwareHandoverV2Rejected = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverV2Rejected::class)
        ->getPendingKickOffs()
        ->count();

    $hrdfHandoverPending = app(\App\Livewire\SalespersonDashboard\HrdfNewTableSalesperson::class)
        ->getNewHrdfHandovers()
        ->count();

    $hrdfHandoverCompleted = app(\App\Livewire\SalespersonDashboard\HrdfCompletedTableSalesperson::class)
        ->getNewHrdfHandovers()
        ->count();

    $hrdfRejected = app(\App\Livewire\SalespersonDashboard\HrdfRejectedTableSalesperson::class)
        ->getNewHrdfHandovers()
        ->count();

    $headcountHandoverPending = app(\App\Livewire\SalespersonDashboard\HeadcountNewTableSalesperson::class)
        ->getNewHeadcountHandovers()
        ->count();

    $headcountHandoverCompleted = app(\App\Livewire\SalespersonDashboard\HeadcountCompletedTableSalesperson::class)
        ->getCompletedHeadcountHandovers()
        ->count();

    $headcountHandoverRejected = app(\App\Livewire\SalespersonDashboard\HeadcountRejectedTableSalesperson::class)
        ->getRejectedHeadcountHandovers()
        ->count();

    // E-Invoice counts
    $eInvoicePending = app(\App\Livewire\SalespersonDashboard\EInvoiceHandoverNew::class)
        ->getNewEInvoiceHandovers()
        ->count();

    $eInvoiceCompleted = app(\App\Livewire\SalespersonDashboard\EInvoiceHandoverCompleted::class)
        ->getCompletedEInvoiceHandovers()
        ->count();

    $eInvoiceRejected = app(\App\Livewire\SalespersonDashboard\EInvoiceHandoverRejected::class)
        ->getRejectedEInvoiceHandovers()
        ->count();

    // Finance Handover counts
    $financePending = app(\App\Livewire\SalespersonDashboard\FinanceHandoverPending::class)
        ->getPendingFinanceHandovers()
        ->count();

    $financeRejected = app(\App\Livewire\SalespersonDashboard\FinanceHandoverRejected::class)
        ->getRejectedFinanceHandovers()
        ->count();

    $financeCompleted = app(\App\Livewire\SalespersonDashboard\FinanceHandoverCompleted::class)
        ->getCompletedFinanceHandovers()
        ->count();

    // Calculate totals for each group
    $pendingHandoverTotal = $softwareHandoverNew + $hardwareHandoverNew + $headcountHandoverPending + $hrdfHandoverPending + $eInvoicePending + $financePending;
    $rejectedHandoverTotal = $softwareHandoverRejected + $hardwareHandoverRejected + $softwareHandoverV2Rejected + $hrdfRejected + $headcountHandoverRejected + $eInvoiceRejected + $financeRejected;
    $completedHandoverTotal = $softwareHandoverCompleted + $hardwareHandoverCompleted + $headcountHandoverCompleted + $hrdfHandoverCompleted + $eInvoiceCompleted + $financeCompleted;

    // All Debtors - Getting real counts now
    $allDebtorsTable = app(\App\Livewire\SalespersonDashboard\AllDebtorsTable::class);
    $allDebtorCount = $allDebtorsTable->getDebtorCount();
    $allDebtorInvoiceCount = $allDebtorsTable->getInvoiceCount();
    $allDebtorAmount = $allDebtorsTable->getTotalAmount();

    // HRDF Debtors
    $hrdfDebtorsTable = app(\App\Livewire\SalespersonDashboard\HrdfDebtorsTable::class);
    $hrdfDebtorCount = $hrdfDebtorsTable->getDebtorCount();
    $hrdfDebtorInvoiceCount = $hrdfDebtorsTable->getInvoiceCount();
    $hrdfDebtorAmount = $hrdfDebtorsTable->getTotalAmount();

    // Product Debtors
    $productDebtorsTable = app(\App\Livewire\SalespersonDashboard\ProductDebtorsTable::class);
    $productDebtorCount = $productDebtorsTable->getDebtorCount();
    $productDebtorInvoiceCount = $productDebtorsTable->getInvoiceCount();
    $productDebtorAmount = $productDebtorsTable->getTotalAmount();

    // Unpaid Debtors
    $unpaidDebtorsTable = app(\App\Livewire\SalespersonDashboard\UnpaidDebtorsTable::class);
    $unpaidDebtorCount = $unpaidDebtorsTable->getDebtorCount();
    $unpaidDebtorInvoiceCount = $unpaidDebtorsTable->getInvoiceCount();
    $unpaidDebtorAmount = $unpaidDebtorsTable->getTotalAmount();

    // Partial Payment Debtors
    $partialDebtorsTable = app(\App\Livewire\SalespersonDashboard\PartialDebtorsTable::class);
    $partialDebtorCount = $partialDebtorsTable->getDebtorCount();
    $partialDebtorInvoiceCount = $partialDebtorsTable->getInvoiceCount();
    $partialDebtorAmount = $partialDebtorsTable->getTotalAmount();

    // Total for badge display on group box
    $totalDebtorCount = $allDebtorCount;

    // Todo List Counts
    $todoPendingCount = app(\App\Livewire\SalespersonDashboard\TodoListPending::class)
        ->getTodoQuery()
        ->count();

    $todoCompletedCount = app(\App\Livewire\SalespersonDashboard\TodoListCompleted::class)
        ->getTodoQuery()
        ->count();

    // Installation Payment counts
    $installationPaymentPending = app(\App\Livewire\SalespersonDashboard\InstallationPaymentPending::class)
        ->getPendingPayments()
        ->count();

    $installationPaymentCompleted = app(\App\Livewire\SalespersonDashboard\InstallationPaymentCompleted::class)
        ->getCompletedPayments()
        ->count();
@endphp

<div id="lead-owner-container" class="lead-owner-container"
    x-data="{
        selectedGroup: 'demo-session',
        selectedStat: 'demo-today',

        setSelectedGroup(value) {
            if (this.selectedGroup === value) {
                this.selectedGroup = null;
                this.selectedStat = null;
            } else {
                this.selectedGroup = value;

                // Set default stat for each group
                if (value === 'demo-session') {
                    this.selectedStat = 'demo-today';
                } else if (value === 'prospect-reminder') {
                    this.selectedStat = 'pr-today';
                } else if (value === 'pending-handover') {
                    this.selectedStat = 'pending-software';
                } else if (value === 'rejected-handover') {
                    this.selectedStat = 'rejected-software';
                } else if (value === 'completed-handover') {
                    this.selectedStat = 'completed-software';
                } else if (value === 'todo-list') {
                    this.selectedStat = 'todo-pending';
                } else if (value === 'others') {
                    this.selectedStat = 'debtor-all';
                } else {
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
        }
    }">

    <div class="dashboard-layout" wire:poll.300s>
        <div class="group-column">
            <div class="group-container">
                <!-- Group: Demo Session -->
                <div class="group-box group-demo"
                        :class="{'selected': selectedGroup === 'demo-session'}"
                        @click="setSelectedGroup('demo-session')">
                    <div class="group-title">Sales Demo Reminder</div>
                    <div class="group-count">{{ $demoTodayCount + $demoTomorrowCount }}</div>
                </div>

                <!-- Group: Prospect Reminder -->
                <div class="group-box group-pr"
                        :class="{'selected': selectedGroup === 'prospect-reminder'}"
                        @click="setSelectedGroup('prospect-reminder')">
                    <div class="group-title">Follow Up Reminder</div>
                    <div class="group-count">{{ $prospectTodayCount + $prospectOverdueCount }}</div>
                </div>

                <!-- Group: To Do List -->
                <div class="group-box group-others"
                        :class="{'selected': selectedGroup === 'todo-list'}"
                        @click="setSelectedGroup('todo-list')">
                    <div class="group-title">Task Reminder</div>
                    <div class="group-count">{{ $todoPendingCount + $installationPaymentPending }}</div>
                </div>

                <!-- Group: Pending Handover -->
                <div class="group-box group-hardware"
                        :class="{'selected': selectedGroup === 'pending-handover'}"
                        @click="setSelectedGroup('pending-handover')">
                    <div class="group-title">Pending Handover</div>
                    <div class="group-count">{{ $pendingHandoverTotal }}</div>
                </div>

                <!-- Group: Rejected Handover -->
                <div class="group-box group-rejected"
                        :class="{'selected': selectedGroup === 'rejected-handover'}"
                        @click="setSelectedGroup('rejected-handover')">
                    <div class="group-title">Rejected Handover</div>
                    <div class="group-count">{{ $rejectedHandoverTotal }}</div>
                </div>

                <!-- Group: Completed Handover -->
                <div class="group-box group-software"
                        :class="{'selected': selectedGroup === 'completed-handover'}"
                        @click="setSelectedGroup('completed-handover')">
                    <div class="group-title">Completed Handover</div>
                    <div class="group-count">{{ $completedHandoverTotal }}</div>
                </div>
            </div>
        </div>

        <div class="content-column">
            <div class="category-container" x-show="selectedGroup === 'demo-session'">
                <div class="stat-box demo-today"
                        :class="{'selected': selectedStat === 'demo-today'}"
                        @click="setSelectedStat('demo-today')">
                    <div class="stat-info">
                        <div class="stat-label">Demo Today</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $demoTodayCount }}</div>
                    </div>
                </div>
                <div class="stat-box demo-tmr"
                        :class="{'selected': selectedStat === 'demo-tmr'}"
                        @click="setSelectedStat('demo-tmr')">
                    <div class="stat-info">
                        <div class="stat-label">Demo Tomorrow</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $demoTomorrowCount }}</div>
                    </div>
                </div>
            </div>

            <!-- PROSPECT REMINDER -->
            <div class="category-container" x-show="selectedGroup === 'prospect-reminder'">
                <div class="stat-box pr-today"
                        :class="{'selected': selectedStat === 'pr-today'}"
                        @click="setSelectedStat('pr-today')">
                    <div class="stat-info">
                        <div class="stat-label">Follow Up - Today</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $prospectTodayCount }}</div>
                    </div>
                </div>
                <div class="stat-box pr-overdue"
                        :class="{'selected': selectedStat === 'pr-overdue'}"
                        @click="setSelectedStat('pr-overdue')">
                    <div class="stat-info">
                        <div class="stat-label">Follow Up - Overdue</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $prospectOverdueCount }}</div>
                    </div>
                </div>
                <div class="stat-box transfer-lead"
                        :class="{'selected': selectedStat === 'transfer-lead'}"
                        @click="setSelectedStat('transfer-lead')">
                    <div class="stat-info">
                        <div class="stat-label">No Response</div>
                        <div class="stat-label">Transfer Leads (37 Days)</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $transferLead }}</div>
                    </div>
                </div>
                <div class="stat-box follow-up-lead"
                        :class="{'selected': selectedStat === 'follow-up-lead'}"
                        @click="setSelectedStat('follow-up-lead')">
                    <div class="stat-info">
                        <div class="stat-label">No Response</div>
                        <div class="stat-label">Follow Up - Leads (97 Days)</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $followUpLead }}</div>
                    </div>
                </div>
            </div>

            <!-- PENDING HANDOVER -->
            <div class="category-container" x-show="selectedGroup === 'pending-handover'">
                <div class="stat-box software-handover-pending"
                        :class="{'selected': selectedStat === 'pending-software'}"
                        @click="setSelectedStat('pending-software')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Software</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $softwareHandoverNew }}</div>
                    </div>
                </div>
                <div class="stat-box hardware-handover-pending"
                        :class="{'selected': selectedStat === 'pending-hardware'}"
                        @click="setSelectedStat('pending-hardware')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Hardware</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $hardwareHandoverNew }}</div>
                    </div>
                </div>
                <div class="stat-box headcount-handover-rejected"
                        :class="{'selected': selectedStat === 'pending-headcount'}"
                        @click="setSelectedStat('pending-headcount')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Headcount</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $headcountHandoverPending }}</div>
                    </div>
                </div>
                <div class="stat-box hrdf-handover-rejected"
                        :class="{'selected': selectedStat === 'pending-hrdf'}"
                        @click="setSelectedStat('pending-hrdf')">
                    <div class="stat-info">
                        <div class="stat-label">Pending HRDF</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $hrdfHandoverPending }}</div>
                    </div>
                </div>
                <div class="stat-box debtor-all"
                        :class="{'selected': selectedStat === 'pending-einvoice'}"
                        @click="setSelectedStat('pending-einvoice')">
                    <div class="stat-info">
                        <div class="stat-label">Pending E-Invoice</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $eInvoicePending }}</div>
                    </div>
                </div>
                <div class="stat-box finance-handover-pending"
                        :class="{'selected': selectedStat === 'pending-finance'}"
                        @click="setSelectedStat('pending-finance')">
                    <div class="stat-info">
                        <div class="stat-label">Pending Finance</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $financePending }}</div>
                    </div>
                </div>
            </div>

            <!-- REJECTED HANDOVER -->
            <div class="category-container" x-show="selectedGroup === 'rejected-handover'">
                <div class="stat-box software-handover-rejected"
                        :class="{'selected': selectedStat === 'rejected-software'}"
                        @click="setSelectedStat('rejected-software')">
                    <div class="stat-info">
                        <div class="stat-label">Rejected Software</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $softwareHandoverRejected }}</div>
                    </div>
                </div>

                <div class="stat-box hardware-handover-rejected"
                        :class="{'selected': selectedStat === 'rejected-hardware'}"
                        @click="setSelectedStat('rejected-hardware')">
                    <div class="stat-info">
                        <div class="stat-label">Rejected Hardware</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $hardwareHandoverRejected }}</div>
                    </div>
                </div>

                <div class="stat-box headcount-handover-rejected"
                        :class="{'selected': selectedStat === 'rejected-headcount'}"
                        @click="setSelectedStat('rejected-headcount')">
                    <div class="stat-info">
                        <div class="stat-label">Rejected Headcount</div>
                    </div>
                    <div class="stat-count">
                        {{ $headcountHandoverRejected }}
                    </div>
                </div>

                <div class="stat-box hrdf-handover-rejected"
                        :class="{'selected': selectedStat === 'rejected-hrdf'}"
                        @click="setSelectedStat('rejected-hrdf')">
                    <div class="stat-info">
                        <div class="stat-label">Rejected HRDF</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $hrdfRejected }}</div>
                    </div>
                </div>

                <div class="stat-box debtor-partial"
                        :class="{'selected': selectedStat === 'rejected-einvoice'}"
                        @click="setSelectedStat('rejected-einvoice')">
                    <div class="stat-info">
                        <div class="stat-label">Rejected E-Invoice</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $eInvoiceRejected }}</div>
                    </div>
                </div>
                <div class="stat-box finance-handover-rejected"
                        :class="{'selected': selectedStat === 'rejected-finance'}"
                        @click="setSelectedStat('rejected-finance')">
                    <div class="stat-info">
                        <div class="stat-label">Rejected Finance</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $financeRejected }}</div>
                    </div>
                </div>
            </div>

            <!-- COMPLETED HANDOVER -->
            <div class="category-container" x-show="selectedGroup === 'completed-handover'">
                <div class="stat-box software-handover-completed"
                        :class="{'selected': selectedStat === 'completed-software'}"
                        @click="setSelectedStat('completed-software')">
                    <div class="stat-info">
                        <div class="stat-label">Completed Software</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $softwareHandoverCompleted }}</div>
                    </div>
                </div>
                <div class="stat-box hardware-handover-completed"
                        :class="{'selected': selectedStat === 'completed-hardware'}"
                        @click="setSelectedStat('completed-hardware')">
                    <div class="stat-info">
                        <div class="stat-label">Completed Hardware</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $hardwareHandoverCompleted }}</div>
                    </div>
                </div>
                <div class="stat-box headcount-handover-rejected"
                        :class="{'selected': selectedStat === 'completed-headcount'}"
                        @click="setSelectedStat('completed-headcount')">
                    <div class="stat-info">
                        <div class="stat-label">Completed Headcount</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $headcountHandoverCompleted }}</div>
                    </div>
                </div>
                <div class="stat-box hrdf-handover-rejected"
                        :class="{'selected': selectedStat === 'completed-hrdf'}"
                        @click="setSelectedStat('completed-hrdf')">
                    <div class="stat-info">
                        <div class="stat-label">Completed HRDF</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $hrdfHandoverCompleted }}</div>
                    </div>
                </div>
                <div class="stat-box debtor-unpaid"
                        :class="{'selected': selectedStat === 'completed-einvoice'}"
                        @click="setSelectedStat('completed-einvoice')">
                    <div class="stat-info">
                        <div class="stat-label">Completed E-Invoice</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $eInvoiceCompleted }}</div>
                    </div>
                </div>
                <div class="stat-box finance-handover-completed"
                        :class="{'selected': selectedStat === 'completed-finance'}"
                        @click="setSelectedStat('completed-finance')">
                    <div class="stat-info">
                        <div class="stat-label">Completed Finance</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $financeCompleted }}</div>
                    </div>
                </div>
            </div>

            <!-- TO DO LIST -->
            <div class="category-container" x-show="selectedGroup === 'todo-list'">
                <div class="stat-box debtor-all"
                        :class="{'selected': selectedStat === 'todo-pending'}"
                        @click="setSelectedStat('todo-pending')">
                    <div class="stat-info">
                        <div class="stat-label">Pending <br> To Do List Reminder</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $todoPendingCount }}</div>
                    </div>
                </div>
                <div class="stat-box debtor-hrdf"
                        :class="{'selected': selectedStat === 'todo-completed'}"
                        @click="setSelectedStat('todo-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed <br> To Do List Reminder</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $todoCompletedCount }}</div>
                    </div>
                </div>
                <div class="stat-box hardware-handover-pending"
                        :class="{'selected': selectedStat === 'installation-pending'}"
                        @click="setSelectedStat('installation-pending')">
                    <div class="stat-info">
                        <div class="stat-label">Pending <br>Installation Payment</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $installationPaymentPending }}</div>
                    </div>
                </div>
                <div class="stat-box hardware-handover-completed"
                        :class="{'selected': selectedStat === 'installation-completed'}"
                        @click="setSelectedStat('installation-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed <br>Installation Payment</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $installationPaymentCompleted }}</div>
                    </div>
                </div>
            </div>

            <!-- OTHERS -->
            <div class="category-container" x-show="selectedGroup === 'others'" style="grid-template-columns: repeat(5, 1fr);">
                <!-- 1. All Debtor -->
                <div class="stat-box debtor-all"
                        :class="{'selected': selectedStat === 'debtor-all'}"
                        @click="setSelectedStat('debtor-all')">
                    <div class="stat-info">
                        <div class="stat-label">All Debtor</div>
                        <div class="text-xs font-medium stat-label">RM {{ number_format($allDebtorAmount, 2) }}</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $allDebtorCount }}</div>
                    </div>
                </div>

                <!-- 2. HRDF Debtor -->
                <div class="stat-box debtor-hrdf"
                        :class="{'selected': selectedStat === 'debtor-hrdf'}"
                        @click="setSelectedStat('debtor-hrdf')">
                    <div class="stat-info">
                        <div class="stat-label">HRDF Debtor</div>
                        <div class="text-xs font-medium stat-label">RM {{ number_format($hrdfDebtorAmount, 2) }}</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $hrdfDebtorCount }}</div>
                    </div>
                </div>

                <!-- 3. Product Debtor -->
                <div class="stat-box debtor-product"
                        :class="{'selected': selectedStat === 'debtor-product'}"
                        @click="setSelectedStat('debtor-product')">
                    <div class="stat-info">
                        <div class="stat-label">Product Debtor</div>
                        <div class="text-xs font-medium stat-label">RM {{ number_format($productDebtorAmount, 2) }}</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $productDebtorCount }}</div>
                    </div>
                </div>

                <!-- 4. Unpaid Debtor -->
                <div class="stat-box debtor-unpaid"
                        :class="{'selected': selectedStat === 'debtor-unpaid'}"
                        @click="setSelectedStat('debtor-unpaid')">
                    <div class="stat-info">
                        <div class="stat-label">Unpaid Debtor</div>
                        <div class="text-xs font-medium stat-label">RM {{ number_format($unpaidDebtorAmount, 2) }}</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $unpaidDebtorCount }}</div>
                    </div>
                </div>

                <!-- 5. Partial Payment Debtor -->
                <div class="stat-box debtor-partial"
                        :class="{'selected': selectedStat === 'debtor-partial'}"
                        @click="setSelectedStat('debtor-partial')">
                    <div class="stat-info">
                        <div class="stat-label">Partial Payment Debtor</div>
                        <div class="text-xs font-medium stat-label">RM {{ number_format($partialDebtorAmount, 2) }}</div>
                    </div>
                    <div class="stat-count">
                        <div class="stat-count">{{ $partialDebtorCount }}</div>
                    </div>
                </div>
            </div>
            <br>
            <div class="content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a group to continue' : 'Select a category to view leads'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the group boxes to see categories' : 'Click on any of the category boxes to display the corresponding information'"></p>
                </div>

                <!-- Content panels for each table (keep the same as your original) -->
                <div x-show="selectedStat === 'demo-today'" x-transition>
                    <livewire:salesperson-dashboard.demo-today-table />
                </div>
                <div x-show="selectedStat === 'demo-tmr'" x-transition>
                    <livewire:salesperson-dashboard.demo-tmr-table />
                </div>

                <!-- Prospect Reminder -->
                <div x-show="selectedStat === 'pr-today'" x-transition>
                    <livewire:salesperson-dashboard.pr-today-salesperson-table />
                </div>
                <div x-show="selectedStat === 'pr-overdue'" x-transition>
                    <livewire:salesperson-dashboard.pr-overdue-salesperson-table />
                </div>
                <div x-show="selectedStat === 'transfer-lead'" x-transition>
                    <livewire:salesperson-dashboard.transfer-lead />
                </div>
                <div x-show="selectedStat === 'follow-up-lead'" x-transition>
                    <livewire:salesperson-dashboard.follow-up-lead />
                </div>

                <!-- PENDING HANDOVER -->
                <div x-show="selectedStat === 'pending-software'" x-transition>
                    <livewire:salesperson-dashboard.software-handover-new />
                </div>
                <div x-show="selectedStat === 'pending-hardware'" x-transition>
                    <livewire:salesperson-dashboard.hardware-handover-new :currentDashboard="'Salesperson'" />
                </div>
                <div x-show="selectedStat === 'pending-headcount'" x-transition>
                    <livewire:admin-headcount-dashboard.headcount-new-table />
                </div>
                <div x-show="selectedStat === 'pending-hrdf'" x-transition>
                    <livewire:salesperson-dashboard.hrdf-new-table-salesperson />
                </div>
                <div x-show="selectedStat === 'pending-einvoice'" x-transition>
                    <livewire:salesperson-dashboard.e-invoice-handover-new />
                </div>
                <div x-show="selectedStat === 'pending-finance'" x-transition>
                    <livewire:salesperson-dashboard.finance-handover-pending />
                </div>

                <!-- REJECTED HANDOVER -->
                <div x-show="selectedStat === 'rejected-software'" x-transition>
                    <livewire:salesperson-dashboard.software-handover-rejected />
                </div>
                <div x-show="selectedStat === 'rejected-hardware'" x-transition>
                    <livewire:salesperson-dashboard.hardware-handover-rejected :currentDashboard="'Salesperson'" />
                </div>
                <div x-show="selectedStat === 'rejected-headcount'" x-transition>
                    <livewire:salesperson-dashboard.headcount-rejected-table-salesperson />
                </div>
                <div x-show="selectedStat === 'rejected-hrdf'" x-transition>
                    <livewire:salesperson-dashboard.hrdf-rejected-table-salesperson />
                </div>
                <div x-show="selectedStat === 'rejected-einvoice'" x-transition>
                    <livewire:salesperson-dashboard.e-invoice-handover-rejected />
                </div>
                <div x-show="selectedStat === 'rejected-finance'" x-transition>
                    <livewire:salesperson-dashboard.finance-handover-rejected />
                </div>

                <!-- COMPLETED HANDOVER -->
                <div x-show="selectedStat === 'completed-software'" x-transition>
                    <livewire:salesperson-dashboard.software-handover-completed />
                </div>
                <div x-show="selectedStat === 'completed-hardware'" x-transition>
                    <livewire:salesperson-dashboard.hardware-handover-completed />
                </div>
                <div x-show="selectedStat === 'completed-headcount'" x-transition>
                    <livewire:salesperson-dashboard.headcount-completed-table-salesperson />
                </div>
                <div x-show="selectedStat === 'completed-hrdf'" x-transition>
                    <livewire:salesperson-dashboard.hrdf-completed-table-salesperson />
                </div>
                <div x-show="selectedStat === 'completed-einvoice'" x-transition>
                    <livewire:salesperson-dashboard.e-invoice-handover-completed />
                </div>
                <div x-show="selectedStat === 'completed-finance'" x-transition>
                    <livewire:salesperson-dashboard.finance-handover-completed />
                </div>

                <!-- To Do List -->
                <div x-show="selectedStat === 'todo-pending'" x-transition>
                    <livewire:salesperson-dashboard.todo-list-pending />
                </div>
                <div x-show="selectedStat === 'todo-completed'" x-transition>
                    <livewire:salesperson-dashboard.todo-list-completed />
                </div>

                <!-- Installation Payment -->
                <div x-show="selectedStat === 'installation-pending'" x-transition>
                    <livewire:salesperson-dashboard.installation-payment-pending />
                </div>
                <div x-show="selectedStat === 'installation-completed'" x-transition>
                    <livewire:salesperson-dashboard.installation-payment-completed />
                </div>

                <!-- Others (Debtors) -->
                <div x-show="selectedStat === 'debtor-all'" x-transition>
                    <livewire:salesperson-dashboard.all-debtors-table />
                </div>
                <div x-show="selectedStat === 'debtor-hrdf'" x-transition>
                    <livewire:salesperson-dashboard.hrdf-debtors-table />
                </div>
                <div x-show="selectedStat === 'debtor-product'" x-transition>
                    <livewire:salesperson-dashboard.product-debtors-table />
                </div>
                <div x-show="selectedStat === 'debtor-unpaid'" x-transition>
                    <livewire:salesperson-dashboard.unpaid-debtors-table />
                </div>
                <div x-show="selectedStat === 'debtor-partial'" x-transition>
                    <livewire:salesperson-dashboard.partial-debtors-table />
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to reset the lead owner component
        window.resetLeadOwner = function() {
            const container = document.getElementById('lead-owner-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
                console.log('Lead owner reset via global function');
            }
        };

        // Listen for our custom reset event
        window.addEventListener('reset-lead-dashboard', function() {
            window.resetLeadOwner();
        });
    });
</script>
