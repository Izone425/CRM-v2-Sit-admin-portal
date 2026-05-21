<x-filament::page>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <style>
        /* Hide content until Livewire is fully initialized */
        [x-cloak],
        .livewire-loading {
            display: none !important;
        }

        /* Add a loading state class */
        .tabs-container {
            opacity: 0;
            transition: opacity 0.1s ease-in-out;
        }

        .tabs-container.initialized {
            opacity: 1;
        }

        /* Progressive loading animations */
        .dashboard-skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, transparent 37%, #f0f0f0 63%);
            background-size: 400% 100%;
            animation: shimmer 1.5s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% { background-position: 100% 50%; }
            100% { background-position: -100% 50%; }
        }

        .content-loading {
            min-height: 200px;
            border-radius: 8px;
        }

        /* ── Leadowner-style "selected" treatment for ALL toggle buttons ── */
        /* Solid purple selected (parent pills, Admin D/Y/F, Manager, LeadOwner, etc.) */
        button[style*="background: #431fa1"],
        button[style*="background:#431fa1"] {
            background: linear-gradient(135deg, #431fa1, #7c4ddb) !important;
            color: #ffffff !important;
            box-shadow: 0 6px 16px rgba(67, 31, 161, 0.45),
                        inset 0 1px 0 rgba(255, 255, 255, 0.2) !important;
            outline: 2px solid rgba(124, 77, 219, 0.6) !important;
            outline-offset: 1px !important;
            transform: translateY(-1px) !important;
            transition: all 0.15s ease !important;
            font-weight: 700 !important;
        }

        /* Light tinted selected (dropdown children) */
        button[style*="background: #ede9fe"],
        button[style*="background:#ede9fe"] {
            background: rgba(124, 77, 219, 0.12) !important;
            color: #431fa1 !important;
            border-left: 4px solid #7c4ddb !important;
            box-shadow: inset 0 0 0 1px rgba(124, 77, 219, 0.25),
                        0 2px 6px rgba(67, 31, 161, 0.08) !important;
            font-weight: 700 !important;
            transition: all 0.15s ease !important;
        }

        /* Hover feedback for any toggle button on the page */
        button[wire\:click^="toggleDashboard"]:hover,
        button[wire\:click^="toggleDashboard"]:focus-visible {
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(67, 31, 161, 0.18) !important;
            transition: all 0.12s ease !important;
        }
        button[wire\:click^="toggleDashboard"] {
            transition: all 0.12s ease !important;
        }

        /* User filter <select> dropdown — branded look */
        #userFilter {
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            padding: 8px 38px 8px 14px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            color: #431fa1 !important;
            background-color: #faf9ff !important;
            background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke-width='2.4' stroke='%237c4ddb'><path stroke-linecap='round' stroke-linejoin='round' d='m19.5 8.25-7.5 7.5-7.5-7.5'/></svg>") !important;
            background-repeat: no-repeat !important;
            background-position: right 12px center !important;
            background-size: 14px 14px !important;
            border: 1.5px solid #d0c9f0 !important;
            border-radius: 10px !important;
            box-shadow: 0 1px 2px rgba(67, 31, 161, 0.06) !important;
            cursor: pointer !important;
            transition: border-color 0.15s, box-shadow 0.15s, background-color 0.15s !important;
            min-width: 200px !important;
        }
        #userFilter:hover {
            border-color: #a78bfa !important;
            background-color: #ffffff !important;
        }
        #userFilter:focus {
            outline: none !important;
            border-color: #7c4ddb !important;
            box-shadow: 0 0 0 3px rgba(124, 77, 219, 0.22) !important;
            background-color: #ffffff !important;
        }
        #userFilter optgroup {
            font-weight: 700;
            color: #431fa1;
            background: #f5f3ff;
        }
        #userFilter option {
            color: #1f2937;
            background: #ffffff;
            padding: 6px;
        }

        /* Optimized badge styles */
        .badge-container {
            display: inline-flex;
            align-items: center;
            background: #ef4444;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 9999px;
            padding: 0.125rem 0.375rem;
            min-width: 1.25rem;
            height: 1.25rem;
            justify-content: center;
        }
    </style>

    @php
        // Get cached counts for better performance
        $cachedCounts = $this->getCachedCounts();
        $managerTotal = $cachedCounts['manager_total'] ?? 0;
        $adminSoftwareTotal = $cachedCounts['admin_software_total'] ?? 0;
        $adminHardwareTotal = $cachedCounts['admin_hardware_total'] ?? 0;
        $renewalCounts = $cachedCounts['admin_renewal_counts'] ?? [];

        // Get other counts
        try {
            $adminHrdfAttLogTotal = app(\App\Livewire\AdminHRDFAttendanceLog\HrdfAttLogNewTable::class)
                ->getNewHrdfAttendanceLogs()
                ->count();
        } catch (Exception $e) {
            $adminHrdfAttLogTotal = 0;
        }

        $adminGeneralTotal = \App\Models\InternalTicket::where('status', 'new')->count();

        // Set default values for counts that will be loaded lazily
        $followUpTodayMYR = 0;
        $followUpOverdueMYR = 0;
        $followUpTodayUSD = 0;
        $followUpOverdueUSD = 0;

        // Other counts set to 0 initially (will be loaded on demand)
        $followUpFutureMYR = 0;
        $followUpAllMYR = 0;
        $followUpTodayMYRNonReseller = 0;
        $followUpOverdueMYRNonReseller = 0;
        $followUpFutureMYRNonReseller = 0;
        $followUpAllMYRNonReseller = 0;
        $followUpFutureUSD = 0;
        $followUpAllUSD = 0;
        $followUpTodayUSDNonReseller = 0;
        $followUpOverdueUSDNonReseller = 0;
        $followUpFutureUSDNonReseller = 0;
        $followUpAllUSDNonReseller = 0;
        $followUpTodayMYRv2 = 0;
        $followUpOverdueMYRv2 = 0;
        $followUpFutureMYRv2 = 0;
        $followUpAllMYRv2 = 0;
        $followUpTodayMYRv2NonReseller = 0;
        $followUpOverdueMYRv2NonReseller = 0;
        $followUpFutureMYRv2NonReseller = 0;
        $followUpAllMYRv2NonReseller = 0;
        $followUpTodayUSDv2 = 0;
        $followUpOverdueUSDv2 = 0;
        $followUpFutureUSDv2 = 0;
        $followUpAllUSDv2 = 0;
        $followUpTodayUSDv2NonReseller = 0;
        $followUpOverdueUSDv2NonReseller = 0;
        $followUpFutureUSDv2NonReseller = 0;
        $followUpAllUSDv2NonReseller = 0;

        // Get minimal counts needed for initial display
        try {
            $adminHeadcountTotal = app(\App\Livewire\AdminHeadcountDashboard\HeadcountNewTable::class)
                ->getNewHeadcountHandovers()
                ->count();
        } catch (Exception $e) {
            $adminHeadcountTotal = 0;
        }

        try {
            $adminHrdfTotal = app(\App\Livewire\AdminHRDFDashboard\HrdfNewTable::class)
                ->getNewHrdfHandovers()
                ->count();
        } catch (Exception $e) {
            $adminHrdfTotal = 0;
        }

        $adminFinanceTotal = \App\Models\FinanceHandover::whereIn('status', ['New'])->count();

        $resellerPortalHandoverCount = \App\Models\ResellerHandover::whereIn('status', ['new', 'pending_timetec_invoice', 'pending_timetec_license'])->count();
        $adminPortalAllCount = \App\Models\CrmInvoiceDetail::pendingInvoices()->get()->count();
        $resellerInquiryNewCount = \App\Models\ResellerInquiry::where('status', 'new')->count();
        $databaseCreationNewCount = \App\Models\ResellerDatabaseCreation::where('status', 'new')->count();
        $fdPendingTimetecAdminCount = \App\Models\ResellerHandoverFd::whereIn('status', ['new', 'pending_timetec_invoice', 'pending_timetec_license'])->count();
        $fePendingTimetecAdminCount = \App\Models\ResellerHandoverFe::whereIn('status', ['new', 'pending_timetec_invoice', 'pending_timetec_license'])->count();
        $ffPendingTimetecAdminCount = \App\Models\ResellerHandoverFf::where('status', 'new')->count();
        $fgPendingTimetecAdminCount = \App\Models\ResellerHandoverFg::whereIn('status', ['new', 'pending_timetec_invoice', 'pending_timetec_license'])->count();
        $adminResellerTotal = $resellerPortalHandoverCount + $adminPortalAllCount + $resellerInquiryNewCount + $databaseCreationNewCount + $fdPendingTimetecAdminCount + $fePendingTimetecAdminCount + $ffPendingTimetecAdminCount + $fgPendingTimetecAdminCount;

        // Hardware V2 counts
        try {
            $newTaskCount = app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2NewTable::class)
                ->getNewHardwareHandovers()
                ->count();
        } catch (Exception $e) {
            $newTaskCount = 0;
        }

        try {
            $pendingStockCount = app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingStockTable::class)
                ->getHardwareHandoverCount();
        } catch (Exception $e) {
            $pendingStockCount = 0;
        }

        try {
            $pendingCourierCount = app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingCourierTable::class)
                ->getHardwareHandoverCount();
        } catch (Exception $e) {
            $pendingCourierCount = 0;
        }

        try {
            $pendingAdminPickUpCount = app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingAdminSelfPickUpTable::class)
                ->getHardwareHandoverCount();
        } catch (Exception $e) {
            $pendingAdminPickUpCount = 0;
        }

        try {
            $pendingExternalInstallationCount = app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingExternalInstallationTable::class)
                ->getHardwareHandoverCount();
        } catch (Exception $e) {
            $pendingExternalInstallationCount = 0;
        }

        try {
            $pendingInternalInstallationCount = app(\App\Livewire\AdminHardwareV2Dashboard\HardwareV2PendingInternalInstallationTable::class)
                ->getHardwareHandoverCount();
        } catch (Exception $e) {
            $pendingInternalInstallationCount = 0;
        }

        $initialStageTotal = $newTaskCount + $pendingStockCount + $pendingCourierCount + $pendingAdminPickUpCount + $pendingExternalInstallationCount + $pendingInternalInstallationCount;

        // Software V2 counts
        try {
            $softwareV2NewCount = app(\App\Livewire\SalespersonDashboard\SoftwareHandoverV2New::class)
                ->getNewSoftwareHandovers()
                ->count();
        } catch (Exception $e) {
            $softwareV2NewCount = 0;
        }

        $softwareV2PendingKickOffCount = 0;
        $softwareV2PendingLicenseCount = 0;
        $adminSoftwareV2Total = $softwareV2NewCount;

        // Calculate minimal total for initial display
        $adminTotal = $adminSoftwareTotal + $adminSoftwareV2Total + $adminHeadcountTotal +
                     $adminHrdfTotal + $initialStageTotal +
                     $adminHrdfAttLogTotal + $adminGeneralTotal;
    @endphp

    <div
        x-data="{
            initialized: false,
            currentTab: '{{ $currentDashboard }}',
            loadingStage: 'initial',
            contentLoaded: false,
            init() {
                document.querySelector('.tabs-container').classList.add('livewire-loading');

                // Progressive loading
                setTimeout(() => {
                    this.loadingStage = 'layout';
                    this.initialized = true;
                    document.querySelector('.tabs-container').classList.remove('livewire-loading');
                    document.querySelector('.tabs-container').classList.add('initialized');
                }, 50);

                // Load content after layout is ready
                setTimeout(() => {
                    this.loadingStage = 'content';
                    this.contentLoaded = true;
                }, 200);
            }
        }"
        x-init="init()"
        class="tabs-container"
        :class="initialized ? 'initialized' : ''"
    >
        <!-- Your existing tab buttons, but add x-cloak to initially hide them -->
        <div x-cloak x-show="initialized">
            @if (auth()->user()->role_id == 1)
                {{-- Common heading for all role_id=1 users --}}
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                    @if (auth()->user()->additional_role == 1)
                        <div style="display: flex; background: #f0f0f0; border-radius: 25px; padding: 3px;">
                            <div class="admin-dropdown admin-dropdown-3" id="adminDropdown2" style="position: relative; display: inline-block;">
                                <button
                                    class="admin-dropdown-button"
                                    style="
                                        padding: 10px 15px;
                                        font-size: 14px;
                                        font-weight: bold;
                                        border: none;
                                        border-radius: 20px;
                                        background: {{ in_array($currentDashboard, ['MainAdminDashboard', 'SoftwareAdmin', 'HardwareAdmin', 'HardwareAdminV2', 'AdminHRDF', 'AdminHRDFAttLog', 'AdminHeadcount']) ? '#431fa1' : 'transparent' }};
                                        color: {{ in_array($currentDashboard, ['MainAdminDashboard', 'SoftwareAdmin', 'HardwareAdmin', 'HardwareAdminV2', 'AdminHRDF', 'AdminHRDFAttLog', 'AdminHeadcount']) ? '#ffffff' : '#555' }};
                                        cursor: pointer;
                                        display: flex;
                                        align-items: center;
                                        gap: 4px;
                                    "
                                >
                                    Admin (D) <i class="fas fa-caret-down" style="font-size: 12px;"></i>
                                </button>

                                <!-- This is the bridge element that covers the gap -->
                                <div class="dropdown-bridge" style="
                                    position: absolute;
                                    height: 20px;
                                    left: 0;
                                    right: 0;
                                    bottom: -10px;
                                    background: transparent;
                                    z-index: 10;
                                "></div>

                                <div class="admin-dropdown-content" style="
                                    display: none;
                                    position: absolute;
                                    background-color: white;
                                    min-width: 250px;
                                    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                                    z-index: 1000;
                                    border-radius: 6px;
                                    overflow: hidden;
                                    top: 100%; /* Position at the bottom of the button */
                                    left: 0;
                                    margin-top: 5px; /* Add a small gap */
                                ">
                                    <button
                                        wire:click="toggleDashboard('MainAdminDashboard')"
                                        style="
                                            display: block;
                                            width: 250px;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'MainAdminDashboard' ? '#ede9fe' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        General Inquiry
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('SoftwareAdmin')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'SoftwareAdmin' ? '#ede9fe' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Software v1
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('HardwareAdminV2')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'HardwareAdminV2' ? '#ede9fe' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Hardware v2
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('AdminHeadcount')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminHeadcount' ? '#ede9fe' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Headcount
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('AdminHRDFAttLog')"
                                        style="
                                            display: flex;
                                            justify-content: space-between;
                                            align-items: center;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminHRDFAttLog' ? '#ede9fe' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        <span>HRDF - Log</span>
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('AdminHRDF')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminHRDF' ? '#ede9fe' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        HRDF - Claim
                                    </button>

                                    <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                        HRDF - Query
                                    </div>

                                    <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                        Renewal (Dina)
                                    </div>

                                    <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                        Debtor (Dina)
                                    </div>
                                </div>
                            </div>

                            <!-- Admin (Y) Dropdown -->
                            <div class="admin-dropdown" style="position: relative; display: inline-block;">
                                <button
                                    class="admin-dropdown-button"
                                    style="
                                        padding: 10px 15px;
                                        font-size: 14px;
                                        font-weight: bold;
                                        border: none;
                                        border-radius: 20px;
                                        background: {{ in_array($currentDashboard, ['AdminFinance', 'AdminRepair', 'Debtor']) ? '#431fa1' : 'transparent' }};
                                        color: {{ in_array($currentDashboard, ['AdminFinance', 'AdminRepair', 'Debtor']) ? '#ffffff' : '#555' }};
                                        cursor: pointer;
                                        display: flex;
                                        align-items: center;
                                        gap: 4px;
                                    "
                                >
                                    Admin (Y) <i class="fas fa-caret-down" style="font-size: 12px;"></i>
                                </button>

                                <div class="dropdown-bridge" style="
                                    position: absolute;
                                    height: 20px;
                                    left: 0;
                                    right: 0;
                                    bottom: -10px;
                                    background: transparent;
                                    z-index: 10;
                                "></div>

                                <div class="admin-dropdown-content" style="
                                    display: none;
                                    position: absolute;
                                    background-color: white;
                                    min-width: 250px;
                                    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                                    z-index: 1000;
                                    border-radius: 6px;
                                    overflow: hidden;
                                    top: 100%;
                                    left: 0;
                                    margin-top: 5px;
                                ">
                                    <button
                                        wire:click="toggleDashboard('AdminFinance')"
                                        style="
                                            display: flex;
                                            justify-content: space-between;
                                            align-items: center;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminFinance' ? '#ede9fe' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Finance Payment
                                        @if($adminFinanceTotal > 0)
                                            <span style="
                                                background: #ef4444;
                                                color: white;
                                                border-radius: 12px;
                                                padding: 2px 8px;
                                                font-size: 12px;
                                                font-weight: bold;
                                                min-width: 20px;
                                                text-align: center;
                                            ">{{ $adminFinanceTotal }}</span>
                                        @endif
                                    </button>

                                    <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                        Debtor (Yat)
                                    </div>

                                    <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                        Renewal (End User)
                                    </div>

                                    <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                        Renewal (Reseller)
                                    </div>

                                    <button
                                        wire:click="toggleDashboard('AdminRepair')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminRepair' ? '#ede9fe' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Onsite Repair
                                    </button>

                                    <button
                                        wire:click="toggleDashboard('Debtor')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'Debtor' ? '#ede9fe' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        InHouse Repair
                                    </button>
                                </div>
                            </div>

                            <!-- Admin (F) Dropdown -->
                            <div class="admin-dropdown" style="position: relative; display: inline-block;">
                                <button
                                    class="admin-dropdown-button"
                                    style="
                                        padding: 10px 15px;
                                        font-size: 14px;
                                        font-weight: bold;
                                        border: none;
                                        border-radius: 20px;
                                        background: {{ in_array($currentDashboard, ['AdminReseller']) ? '#431fa1' : 'transparent' }};
                                        color: {{ in_array($currentDashboard, ['AdminReseller']) ? '#ffffff' : '#555' }};
                                        cursor: pointer;
                                        display: flex;
                                        align-items: center;
                                        gap: 4px;
                                    "
                                >
                                    Admin (F) <i class="fas fa-caret-down" style="font-size: 12px;"></i>
                                </button>

                                <div class="dropdown-bridge" style="
                                    position: absolute;
                                    height: 20px;
                                    left: 0;
                                    right: 0;
                                    bottom: -10px;
                                    background: transparent;
                                    z-index: 10;
                                "></div>

                                <div class="admin-dropdown-content" style="
                                    display: none;
                                    position: absolute;
                                    background-color: white;
                                    min-width: 250px;
                                    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                                    z-index: 1000;
                                    border-radius: 6px;
                                    overflow: hidden;
                                    top: 100%;
                                    left: 0;
                                    margin-top: 5px;
                                ">
                                    <button
                                        wire:click="toggleDashboard('AdminReseller')"
                                        style="
                                            display: block;
                                            width: 100%;
                                            padding: 10px 16px;
                                            text-align: left;
                                            border: none;
                                            background: {{ $currentDashboard === 'AdminReseller' ? '#ede9fe' : 'white' }};
                                            cursor: pointer;
                                            font-size: 14px;
                                        "
                                    >
                                        Reseller System
                                    </button>

                                    <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                        Debtor (Fatimah)
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <br>

                {{-- Two-column grid for all role_id=1 users --}}
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @if (auth()->user()->additional_role == 1)
                        @if ($currentDashboard === 'LeadOwner')
                            @include('filament.pages.leadowner')
                        @elseif ($currentDashboard === 'SoftwareAdmin')
                            @include('filament.pages.softwarehandover')
                        @elseif ($currentDashboard === 'HardwareAdmin')
                            @include('filament.pages.hardwarehandover')
                        @elseif ($currentDashboard === 'HardwareAdminV2')
                            @include('filament.pages.hardwarehandoverv2')
                        @elseif ($currentDashboard === 'AdminRepair')
                            @include('filament.pages.adminrepair')
                        @endif
                    @else
                        <!-- Regular Lead Owner view for role_id=1 users without additional_role=1 -->
                        @include('filament.pages.leadowner')
                    @endif
                </div>
            @elseif (auth()->user()->role_id == 1 && auth()->user()->additional_role == 2)
                {{-- Admin Repair Dashboard for role_id=1 with additional_role=2 --}}
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Repair Admin Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.implementer')
                </div>
            @elseif (auth()->user()->role_id == 2)
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.salesperson')
                </div>
            @elseif(auth()->user()->role_id == 5)
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Implementer Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center mb-6">
                        <div>
                            <select
                                wire:model.live="selectedUser"
                                id="userFilter"
                                class="border-gray-300 rounded-md shadow-sm"
                            >
                                @if(auth()->id() == 26)
                                    <option value="all-implementer">All Implementers</option>
                                    <option value="{{ auth()->id() }}">Dashboard</option>
                                @else
                                    <option value="{{ auth()->id() }}">Dashboard</option>
                                    <option value="all-implementer">All Implementers</option>
                                @endif

                                <optgroup label="Implementer">
                                    @foreach ($users->whereIn('role_id', [4,5])->where('id', '!=', auth()->id()) as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.implementer')
                </div>
            @elseif (auth()->user()->role_id == 4)
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Implementer Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.implementer')
                </div>
            @elseif (auth()->user()->role_id == 10)
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Finance Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.finance')
                </div>
            @elseif (auth()->user()->role_id == 8)
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Support Dashboard</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @include('filament.pages.support')
                </div>
            @elseif (auth()->user()->role_id == 9)
                <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                    <div class="flex items-center space-x-2">
                        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">{{ $currentDashboard === 'Support' ? 'Support Dashboard' : 'Technician Dashboard' }}</h1>
                        <div x-data="{ lastRefresh: '{{ now()->format('Y-m-d H:i:s') }}' }" class="relative">
                            <button
                                wire:click="refreshTable"
                                wire:loading.attr="disabled"
                                class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 tooltip"
                                title="Last refreshed: {{ $lastRefreshTime }}"
                            >
                                <span wire:loading.remove wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                </span>
                                <span wire:loading wire:target="refreshTable">
                                    <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Add toggle buttons for Technician with Hardware Badge -->
                    <div style="display: flex; background: #f0f0f0; border-radius: 25px; padding: 3px;">
                        <button
                            wire:click="toggleDashboard('Technician')"
                            style="
                                padding: 10px 15px;
                                font-size: 14px;
                                font-weight: bold;
                                border: none;
                                border-radius: 20px;
                                background: {{ $currentDashboard === 'Technician' ? '#431fa1' : 'transparent' }};
                                color: {{ $currentDashboard === 'Technician' ? '#ffffff' : '#555' }};
                                cursor: pointer;
                            "
                        >
                            Technician Dashboard
                        </button>

                        <button
                            wire:click="toggleDashboard('Support')"
                            style="
                                padding: 10px 15px;
                                font-size: 14px;
                                font-weight: bold;
                                border: none;
                                border-radius: 20px;
                                background: {{ $currentDashboard === 'Support' ? '#431fa1' : 'transparent' }};
                                color: {{ $currentDashboard === 'Support' ? '#ffffff' : '#555' }};
                                cursor: pointer;
                            "
                        >
                            Support Dashboard
                        </button>

                        <button
                            wire:click="toggleDashboard('HardwareAdminV2')"
                            style="
                                padding: 10px 15px;
                                font-size: 14px;
                                font-weight: bold;
                                border: none;
                                border-radius: 20px;
                                background: {{ $currentDashboard === 'HardwareAdminV2' ? '#431fa1' : 'transparent' }};
                                color: {{ $currentDashboard === 'HardwareAdminV2' ? '#ffffff' : '#555' }};
                                cursor: pointer;
                                display: flex;
                                align-items: center;
                                gap: 4px;
                            "
                        >
                            Admin Hardware
                            @if($pendingInternalInstallationCount > 0)
                                <span style="
                                    background: #ef4444;
                                    color: white;
                                    border-radius: 12px;
                                    padding: 2px 8px;
                                    font-size: 12px;
                                    font-weight: bold;
                                    min-width: 20px;
                                    text-align: center;
                                    margin-left: 4px;
                                ">{{ $pendingInternalInstallationCount }}</span>
                            @endif
                        </button>
                    </div>
                </div>
                <br>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    @if ($currentDashboard === 'HardwareAdminV2')
                        @include('filament.pages.hardwarehandoverv2')
                    @elseif ($currentDashboard === 'Support')
                        @include('filament.pages.support')
                    @else
                        @include('filament.pages.technician')
                    @endif
                </div>
            @elseif (auth()->user()->role_id == 3)
                <div class="space-y-4">
                    <div class="flex flex-col items-start justify-between w-full mb-6 md:flex-row md:items-center">
                        <div class="flex items-center space-x-2" x-data="{ showRefresh: false }"
                            @mouseenter="showRefresh = true"
                            @mouseleave="showRefresh = false">
                            <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">
                                Dashboard
                            </h1>
                            <div class="relative ml-2" x-cloak>
                                <button
                                    x-show="showRefresh"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 transform scale-100"finance
                                    x-transition:leave-end="opacity-0 transform scale-95"
                                    wire:click="refreshTable"
                                    wire:loading.attr="disabled"
                                    class="flex items-center px-3 py-1 text-sm font-medium transition-colors bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    title="Last refreshed: {{ $lastRefreshTime }}"
                                >
                                    <span wire:loading.remove wire:target="refreshTable">
                                        <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </span>
                                    <span wire:loading wire:target="refreshTable">
                                        <svg class="w-4 h-4 mr-1 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center mb-6">
                            <div>
                                <select
                                    wire:model.live="selectedUser"
                                    id="userFilter"
                                    class="border-gray-300 rounded-md shadow-sm"
                                >
                                    <option value="{{ auth()->id() }}">Dashboard</option>

                                    <optgroup label="All Groups">
                                        <option value="all-lead-owners">All Lead Owners</option>
                                        <option value="all-implementer">All Implementer</option>
                                        <option value="all-salespersons">All Salespersons</option>
                                        <option value="all-support">All Support</option>
                                    </optgroup>

                                    <optgroup label="Lead Owner">
                                        @foreach ($users->where('role_id', 1) as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>

                                    <optgroup label="Implementer">
                                        @foreach ($users->whereIn('role_id', [4, 5]) as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>

                                    <optgroup label="Salesperson">
                                        @foreach ($users->where('role_id', 2) as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>

                                    <optgroup label="Support">
                                        @foreach ($users->where('role_id', 8) as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>

                                    <optgroup label="Technician">
                                        @foreach ($users->where('role_id', 9) as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                </select>
                            </div>
                            &nbsp;&nbsp;
                            <!-- Toggle Buttons (conditionally shown) -->
                            @if ($selectedUser == 1 || $selectedUser == 14 || $selectedUser == null)
                                <div style="display: flex; align-items: center;">
                                    <div style="display: flex; background: #f0f0f0; border-radius: 25px; padding: 3px;">
                                        <button
                                            wire:click="toggleDashboard('Manager')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'Manager' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'Manager' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                                display: flex;
                                                align-items: center;
                                                gap: 4px;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('Manager')">
                                                Manager
                                                @if($managerTotal > 0)
                                                    <span style="
                                                        background: #ef4444;
                                                        color: white;
                                                        border-radius: 12px;
                                                        padding: 2px 8px;
                                                        font-size: 12px;
                                                        font-weight: bold;
                                                        min-width: 20px;
                                                        text-align: center;
                                                        margin-left: 4px;
                                                    ">{{ $managerTotal }}</span>
                                                @endif
                                            </span>
                                            <span wire:loading wire:target="toggleDashboard('Manager')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>

                                        @if (auth()->user()->role_id == 3 && auth()->user()->additional_role != 1)
                                        <!-- Lead Owner Button -->
                                        <button
                                            wire:click="toggleDashboard('LeadOwner')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'LeadOwner' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'LeadOwner' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('LeadOwner')">Lead Owner</span>
                                            <span wire:loading wire:target="toggleDashboard('LeadOwner')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>

                                        <!-- Salesperson Button -->
                                        <button
                                            wire:click="toggleDashboard('Salesperson')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'Salesperson' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'Salesperson' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('Salesperson')">Salesperson</span>
                                            <span wire:loading wire:target="toggleDashboard('Salesperson')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>
                                        @endif

                                        <!-- Admin Dropdown -->
                                        <div class="admin-dropdown admin-dropdown-1" id="adminDropdown1" style="position: relative; display: inline-block;">
                                            <button
                                                class="admin-dropdown-button"
                                                style="
                                                    padding: 10px 15px;
                                                    font-size: 14px;
                                                    font-weight: bold;
                                                    border: none;
                                                    border-radius: 20px;
                                                    background: {{ in_array($currentDashboard, ['MainAdminDashboard','SoftwareAdmin', 'SoftwareAdminV2', 'HardwareAdmin', 'HardwareAdminV2', 'AdminHRDF', 'AdminHRDFAttLog', 'AdminHeadcount', 'AdminGeneral']) ? '#431fa1' : 'transparent' }};
                                                    color: {{ in_array($currentDashboard, ['MainAdminDashboard','SoftwareAdmin','SoftwareAdminV2', 'HardwareAdmin', 'HardwareAdminV2', 'AdminRenewalv1', 'AdminRenewalEndUser', 'AdminRenewalv2', 'AdminHRDF', 'AdminHRDFAttLog', 'AdminHeadcount', 'AdminGeneral']) ? '#ffffff' : '#555' }};
                                                    cursor: pointer;
                                                    display: flex;
                                                    align-items: center;
                                                    gap: 4px;
                                                "
                                            >
                                                Admin (D)
                                                @if($adminTotal > 0)
                                                    <span style="
                                                        background: #ef4444;
                                                        color: white;
                                                        border-radius: 12px;
                                                        padding: 2px 8px;
                                                        font-size: 12px;
                                                        font-weight: bold;
                                                        min-width: 20px;
                                                        text-align: center;
                                                    ">{{ $adminTotal }}</span>
                                                @endif
                                                <i class="fas fa-caret-down" style="font-size: 12px;"></i>
                                            </button>

                                            <!-- This is the bridge element that covers the gap -->
                                            <div class="dropdown-bridge" style="
                                                position: absolute;
                                                height: 20px;
                                                left: 0;
                                                right: 0;
                                                bottom: -10px;
                                                background: transparent;
                                                z-index: 10;
                                            "></div>

                                            <div class="admin-dropdown-content" style="
                                                display: none;
                                                position: absolute;
                                                background-color: white;
                                                min-width: 250px;
                                                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                                                z-index: 1000;
                                                border-radius: 6px;
                                                overflow: hidden;
                                                top: 100%;
                                                left: 0;
                                                margin-top: 5px;
                                            ">
                                                <button
                                                    wire:click="toggleDashboard('AdminGeneral')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminGeneral' ? '#ede9fe' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminGeneral')">General Inquiry</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminGeneral')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminGeneralTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminGeneralTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('SoftwareAdmin')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'SoftwareAdmin' ? '#ede9fe' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('SoftwareAdmin')">Software v1</span>
                                                    <span wire:loading wire:target="toggleDashboard('SoftwareAdmin')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminSoftwareTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminSoftwareTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('SoftwareAdminV2')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'SoftwareAdminV2' ? '#ede9fe' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('SoftwareAdminV2')">Software v2</span>
                                                    <span wire:loading wire:target="toggleDashboard('SoftwareAdminV2')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    {{-- @if($adminSoftwareTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminSoftwareTotal }}</span>
                                                    @endif --}}
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('HardwareAdminV2')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'HardwareAdminV2' ? '#ede9fe' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('HardwareAdminV2')">Hardware v2</span>
                                                    <span wire:loading wire:target="toggleDashboard('HardwareAdminV2')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($initialStageTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $initialStageTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('AdminHeadcount')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminHeadcount' ? '#ede9fe' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminHeadcount')">Headcount</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminHeadcount')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminHeadcountTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminHeadcountTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('AdminHRDFAttLog')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminHRDFAttLog' ? '#ede9fe' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminHRDFAttLog')">HRDF - Log</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminHRDFAttLog')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminHrdfAttLogTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminHrdfAttLogTotal }}</span>
                                                    @endif
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('AdminHRDF')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminHRDF' ? '#ede9fe' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminHRDF')">HRDF - Claim</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminHRDF')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminHrdfTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminHrdfTotal }}</span>
                                                    @endif
                                                </button>

                                                <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                                    HRDF - Query
                                                </div>

                                                <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                                    Renewal (Dina)
                                                </div>

                                                <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                                    Debtor (Dina)
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Admin (Y) Dropdown -->
                                        <div class="admin-dropdown admin-dropdown-2" id="adminDropdown2" style="position: relative; display: inline-block;">
                                            <button
                                                class="admin-dropdown-button"
                                                style="
                                                    padding: 10px 15px;
                                                    font-size: 14px;
                                                    font-weight: bold;
                                                    border: none;
                                                    border-radius: 20px;
                                                    background: {{ in_array($currentDashboard, ['AdminFinance', 'AdminRepair', 'Debtor']) ? '#431fa1' : 'transparent' }};
                                                    color: {{ in_array($currentDashboard, ['AdminFinance', 'AdminRepair', 'Debtor']) ? '#ffffff' : '#555' }};
                                                    cursor: pointer;
                                                    display: flex;
                                                    align-items: center;
                                                    gap: 4px;
                                                "
                                            >
                                                Admin (Y)
                                                @if(($adminFinanceTotal ?? 0) > 0)
                                                    <span style="
                                                        background: #ef4444;
                                                        color: white;
                                                        border-radius: 12px;
                                                        padding: 2px 8px;
                                                        font-size: 12px;
                                                        font-weight: bold;
                                                        min-width: 20px;
                                                        text-align: center;
                                                    ">{{ ($adminFinanceTotal ?? 0) }}</span>
                                                @endif
                                                <i class="fas fa-caret-down" style="font-size: 12px;"></i>
                                            </button>

                                            <div class="dropdown-bridge" style="
                                                position: absolute;
                                                height: 20px;
                                                left: 0;
                                                right: 0;
                                                bottom: -10px;
                                                background: transparent;
                                                z-index: 10;
                                            "></div>

                                            <div class="admin-dropdown-content" style="
                                                display: none;
                                                position: absolute;
                                                background-color: white;
                                                min-width: 250px;
                                                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                                                z-index: 1000;
                                                border-radius: 6px;
                                                overflow: hidden;
                                                top: 100%;
                                                left: 0;
                                                margin-top: 5px;
                                            ">
                                                <button
                                                    wire:click="toggleDashboard('AdminFinance')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminFinance' ? '#ede9fe' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminFinance')">Finance Payment</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminFinance')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminFinanceTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminFinanceTotal }}</span>
                                                    @endif
                                                </button>

                                                <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                                    Debtor (Yat)
                                                </div>

                                                <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                                    Renewal (End User)
                                                </div>

                                                <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                                    Renewal (Reseller)
                                                </div>

                                                <button
                                                    wire:click="toggleDashboard('AdminRepair')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: block;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminRepair' ? '#ede9fe' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminRepair')">Onsite Repair</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminRepair')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                </button>

                                                <button
                                                    wire:click="toggleDashboard('Debtor')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: block;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'Debtor' ? '#ede9fe' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('Debtor')">InHouse Repair</span>
                                                    <span wire:loading wire:target="toggleDashboard('Debtor')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Admin (F) Dropdown -->
                                        <div class="admin-dropdown admin-dropdown-4" id="adminDropdown3" style="position: relative; display: inline-block;">
                                            <button
                                                class="admin-dropdown-button"
                                                style="
                                                    padding: 10px 15px;
                                                    font-size: 14px;
                                                    font-weight: bold;
                                                    border: none;
                                                    border-radius: 20px;
                                                    background: {{ in_array($currentDashboard, ['AdminReseller']) ? '#431fa1' : 'transparent' }};
                                                    color: {{ in_array($currentDashboard, ['AdminReseller']) ? '#ffffff' : '#555' }};
                                                    cursor: pointer;
                                                    display: flex;
                                                    align-items: center;
                                                    gap: 4px;
                                                "
                                            >
                                                Admin (F)
                                                @if(($adminResellerTotal ?? 0) > 0)
                                                    <span style="
                                                        background: #ef4444;
                                                        color: white;
                                                        border-radius: 12px;
                                                        padding: 2px 8px;
                                                        font-size: 12px;
                                                        font-weight: bold;
                                                        min-width: 20px;
                                                        text-align: center;
                                                    ">{{ ($adminResellerTotal ?? 0) }}</span>
                                                @endif
                                                <i class="fas fa-caret-down" style="font-size: 12px;"></i>
                                            </button>

                                            <div class="dropdown-bridge" style="
                                                position: absolute;
                                                height: 20px;
                                                left: 0;
                                                right: 0;
                                                bottom: -10px;
                                                background: transparent;
                                                z-index: 10;
                                            "></div>

                                            <div class="admin-dropdown-content" style="
                                                display: none;
                                                position: absolute;
                                                background-color: white;
                                                min-width: 250px;
                                                box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
                                                z-index: 1000;
                                                border-radius: 6px;
                                                overflow: hidden;
                                                top: 100%;
                                                left: 0;
                                                margin-top: 5px;
                                            ">
                                                <button
                                                    wire:click="toggleDashboard('AdminReseller')"
                                                    wire:loading.attr="disabled"
                                                    wire:loading.class="opacity-50"
                                                    style="
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: center;
                                                        width: 100%;
                                                        padding: 10px 16px;
                                                        text-align: left;
                                                        border: none;
                                                        background: {{ $currentDashboard === 'AdminReseller' ? '#ede9fe' : 'white' }};
                                                        cursor: pointer;
                                                        font-size: 14px;
                                                    "
                                                >
                                                    <span wire:loading.remove wire:target="toggleDashboard('AdminReseller')">Reseller System</span>
                                                    <span wire:loading wire:target="toggleDashboard('AdminReseller')" class="flex items-center">
                                                        <svg class="w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Loading...
                                                    </span>
                                                    @if($adminResellerTotal > 0)
                                                        <span style="
                                                            background: #ef4444;
                                                            color: white;
                                                            border-radius: 12px;
                                                            padding: 2px 8px;
                                                            font-size: 12px;
                                                            font-weight: bold;
                                                            min-width: 20px;
                                                            text-align: center;
                                                        ">{{ $adminResellerTotal }}</span>
                                                    @endif
                                                </button>

                                                <div style="display: block; width: 100%; padding: 10px 16px; text-align: left; border: none; background: white; font-size: 14px; color: #9CA3AF; cursor: default;">
                                                    Debtor (Fatimah)
                                                </div>
                                            </div>
                                        </div>

                                        @if (auth()->user()->role_id == 3)
                                        <!-- Finance Button -->
                                        <button
                                            wire:click="toggleDashboard('Finance')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'Finance' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'Finance' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                                display: flex;
                                                align-items: center;
                                                gap: 4px;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('Finance')">Finance</span>
                                            <span wire:loading wire:target="toggleDashboard('Finance')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                            @if(($cachedCounts['finance_total'] ?? 0) > 0)
                                                <span style="
                                                    background: #ef4444;
                                                    color: white;
                                                    border-radius: 12px;
                                                    padding: 2px 8px;
                                                    font-size: 12px;
                                                    font-weight: bold;
                                                    min-width: 20px;
                                                    text-align: center;
                                                    margin-left: 4px;
                                                ">{{ $cachedCounts['finance_total'] ?? 0 }}</span>
                                            @endif
                                        </button>
                                        @endif

                                        <!-- Technician Button -->
                                        <button
                                            wire:click="toggleDashboard('Technician')"
                                            wire:loading.attr="disabled"
                                            wire:loading.class="opacity-50 cursor-not-allowed"
                                            style="
                                                padding: 10px 15px;
                                                font-size: 14px;
                                                font-weight: bold;
                                                border: none;
                                                border-radius: 20px;
                                                background: {{ $currentDashboard === 'Technician' ? '#431fa1' : 'transparent' }};
                                                color: {{ $currentDashboard === 'Technician' ? '#ffffff' : '#555' }};
                                                cursor: pointer;
                                            "
                                        >
                                            <span wire:loading.remove wire:target="toggleDashboard('Technician')">Technician</span>
                                            <span wire:loading wire:target="toggleDashboard('Technician')">
                                                <svg class="inline w-4 h-4 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Loading...
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <br>
                <div class="grid grid-cols-1 gap-6">
                    @if ($selectedUserRole == 1)
                        @if (isset($selectedUserModel) && $selectedUserModel && $selectedUserModel->role_id == 1 && $selectedUserModel->additional_role == 1)
                            @if ($currentDashboard === 'LeadOwner')
                                @include('filament.pages.leadowner')
                            @elseif ($currentDashboard === 'SoftwareHandover')
                                @include('filament.pages.softwarehandover')
                            @elseif ($currentDashboard === 'HardwareHandover')
                                @include('filament.pages.hardwarehandover')
                            @else
                                @include('filament.pages.leadowner')
                            @endif
                        @else
                            @include('filament.pages.leadowner')
                        @endif
                    @elseif ($selectedUserRole == 2)
                        @include('filament.pages.salesperson')
                    @elseif ($selectedUserRole == 3)
                        @include('filament.pages.manager')
                    @elseif ($selectedUserRole == 4 || $selectedUserRole == 5)
                        @include('filament.pages.implementer')
                    @elseif ($selectedUserRole == 9)
                        @include('filament.pages.technician')
                    @else
                        @if ($currentDashboard === 'LeadOwner')
                            @include('filament.pages.leadowner')
                        @elseif ($currentDashboard === 'Salesperson')
                            @include('filament.pages.salesperson')
                        @elseif ($currentDashboard === 'Manager')
                            @include('filament.pages.manager')
                        @elseif ($currentDashboard === 'MainAdminDashboard')
                            @include('filament.pages.admin-main-dashboard')
                        @elseif ($currentDashboard === 'SoftwareHandover')
                            @include('filament.pages.softwarehandover')
                        @elseif ($currentDashboard === 'HardwareHandover')
                            @include('filament.pages.hardwarehandover')
                        @elseif ($currentDashboard === 'AdminGeneral')
                            @include('filament.pages.admingeneral')
                        @elseif ($currentDashboard === 'AdminRepair')
                            @include('filament.pages.adminrepair')
                        {{-- @elseif ($currentDashboard === 'AdminRenewalv1')
                            @include('filament.pages.adminrenewal')
                        @elseif ($currentDashboard === 'AdminRenewalEndUser')
                            @include('filament.pages.adminrenewalnonreseller') --}}
                        @elseif ($currentDashboard === 'AdminHRDF')
                            @include('filament.pages.adminhrdf')
                        @elseif ($currentDashboard === 'AdminFinance')
                            @include('filament.pages.adminfinance')
                        @elseif ($currentDashboard === 'AdminReseller')
                            @include('filament.pages.admin-reseller')
                        @elseif ($currentDashboard === 'AdminHRDFAttLog')
                            @include('filament.pages.adminhrdfattlog')
                        @elseif ($currentDashboard === 'AdminHeadcount')
                            @include('filament.pages.adminheadcount')
                        @elseif ($currentDashboard === 'SoftwareAdmin')
                            @include('filament.pages.softwarehandover')
                        @elseif ($currentDashboard === 'SoftwareAdminV2')
                            @include('filament.pages.softwarehandoverv2')
                        @elseif ($currentDashboard === 'Debtor')
                            {{-- @include('filament.pages.admindebtor') --}}
                        @elseif ($currentDashboard === 'HardwareAdminV2')
                            @include('filament.pages.hardwarehandoverv2')
                        @elseif ($currentDashboard === 'Finance')
                            @include('filament.pages.finance')
                        @elseif ($currentDashboard === 'Trainer')
                            {{-- @include('filament.pages.trainer') --}}
                        @elseif ($currentDashboard === 'Implementer')
                            @include('filament.pages.implementer')
                        @elseif ($currentDashboard === 'Support')
                            @include('filament.pages.support')
                        @elseif ($currentDashboard === 'Technician')
                            @include('filament.pages.technician')
                        @else
                            @include('filament.pages.manager')
                        @endif
                    @endif
                </div>
            @endif
            <!-- JavaScript for dropdown behavior -->
            <script>
                // Function to initialize all dropdowns
                function initializeDropdowns() {
                    const adminDropdowns = document.querySelectorAll('.admin-dropdown');

                    // Don't clear existing event listeners on wire:click elements
                    adminDropdowns.forEach(function(dropdown) {
                        const button = dropdown.querySelector('.admin-dropdown-button');

                        // Only replace non-wire elements
                        if (button && !button.hasAttribute('wire:click')) {
                            const newButton = button.cloneNode(true);
                            if (button.parentNode) {
                                button.parentNode.replaceChild(newButton, button);
                            }
                        }
                    });

                    // Re-attach event listeners
                    adminDropdowns.forEach(function(dropdown) {
                        const button = dropdown.querySelector('.admin-dropdown-button');
                        const content = dropdown.querySelector('.admin-dropdown-content');
                        const bridge = dropdown.querySelector('.dropdown-bridge');

                        if (button && content) {
                            // Show dropdown on mouseenter for button
                            button.addEventListener('mouseenter', function() {
                                content.style.display = 'block';
                            });

                            // Keep dropdown open when hovering over dropdown content
                            content.addEventListener('mouseenter', function() {
                                content.style.display = 'block';
                            });

                            // Keep dropdown open when hovering over bridge
                            if (bridge) {
                                bridge.addEventListener('mouseenter', function() {
                                    content.style.display = 'block';
                                });
                            }

                            // Hide dropdown when mouse leaves entire component
                            dropdown.addEventListener('mouseleave', function() {
                                content.style.display = 'none';
                            });

                            // MODIFIED: Don't prevent default for the button - allow events to bubble
                            button.addEventListener('click', function() {
                                if (content.style.display === 'block') {
                                    content.style.display = 'none';
                                } else {
                                    // Close all other dropdowns first
                                    document.querySelectorAll('.admin-dropdown-content').forEach(function(otherContent) {
                                        if (otherContent !== content) {
                                            otherContent.style.display = 'none';
                                        }
                                    });
                                    content.style.display = 'block';
                                }
                            });
                        }

                        // Add specific handling for menu items with wire:click
                        const menuItems = dropdown.querySelectorAll('.admin-dropdown-content button[wire\\:click]');
                        menuItems.forEach(function(item) {
                            // Don't replace wire:click elements, just add a simple click handler
                            // that closes the dropdown without interfering with Livewire
                            item.addEventListener('click', function(e) {
                                // Allow the wire:click to execute first, then close dropdown
                                setTimeout(function() {
                                    content.style.display = 'none';
                                }, 100);
                            });
                        });
                    });

                    // Only close dropdowns when clicking outside (but not on dropdown menu items)
                    document.addEventListener('click', function(event) {
                        // Check if the click was on a wire:click element in a dropdown
                        const clickedWireElement = event.target.closest('button[wire\\:click]');
                        const clickedInDropdown = event.target.closest('.admin-dropdown');

                        if (clickedWireElement && clickedInDropdown) {
                            // For wire:click elements inside dropdowns, just close the dropdown after a delay
                            setTimeout(function() {
                                const dropdown = clickedWireElement.closest('.admin-dropdown');
                                const content = dropdown.querySelector('.admin-dropdown-content');
                                if (content) {
                                    content.style.display = 'none';
                                }
                            }, 150);
                            return;
                        }

                        // For other clicks outside dropdowns, close all dropdowns
                        adminDropdowns.forEach(function(dropdown) {
                            if (!dropdown.contains(event.target)) {
                                const content = dropdown.querySelector('.admin-dropdown-content');
                                if (content) {
                                    content.style.display = 'none';
                                }
                            }
                        });
                    }, { capture: true });
                }

                // Initialize on DOMContentLoaded
                document.addEventListener('DOMContentLoaded', initializeDropdowns);

                // Re-initialize on Livewire updates (less frequently)
                document.addEventListener('livewire:navigated', initializeDropdowns);
                document.addEventListener('livewire:load', initializeDropdowns);

                // Only re-initialize when specifically needed, not on every update
                let initTimeout;
                document.addEventListener('livewire:update', function() {
                    clearTimeout(initTimeout);
                    initTimeout = setTimeout(initializeDropdowns, 500);
                });
            </script>
        </div>
    </div>
</x-filament::page>
