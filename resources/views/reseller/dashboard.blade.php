{{-- filepath: /var/www/html/timeteccrm/resources/views/reseller/dashboard.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TimeTec CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    @livewireStyles

    <style>
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        html {
            overflow-y: scroll; /* Always show scrollbar space to prevent layout shift */
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .stats-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .stats-card-2 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .stats-card-3 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }

        /* Header Styles */
        .main-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }

        /* Sidebar Styles */
        .sidebar {
            position: absolute;
            left: 0;
            top: 140px;
            width: 290px;
            z-index: 50;
        }

        .sidebar-menu {
            padding: 10px;
        }

        .sidebar-menu hr {
            margin: 0 0 10px 0;
            border: 0;
            border-top: 2px solid #d1d5db;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            margin-bottom: 8px;
            border-radius: 10px;
            color: #64748b;
            transition: all 0.3s ease;
            cursor: pointer;
            font-weight: 500;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
        }

        .menu-item:hover {
            background: #f1f5f9;
            color: #667eea;
            transform: translateX(4px);
        }

        .menu-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .menu-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .action-button {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            margin-bottom: 8px;
            border-radius: 10px;
            color: #64748b;
            transition: all 0.3s ease;
            cursor: pointer;
            font-weight: 500;
            border: none;
            background: transparent;
            width: 100%;
            text-align: left;
            font-size: 1rem;
        }

        .action-button:hover {
            background: #f1f5f9;
            color: #667eea;
            transform: translateX(4px);
        }

        .action-button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .action-button i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        /* Main Content with Sidebar */
        .main-wrapper {
            margin-left: 280px;
            margin-top: 20px;
            min-height: calc(100vh - 125px - 125px); /* viewport height - header - footer */
            flex: 1;
        }

        .page-footer {
            padding: 10px 0;
            text-align: center;
            color: #6b7280;
            background: transparent;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        .handover-subtab {
            display: inline-flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            color: #64748b;
            background: transparent;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            min-width: 120px;
            min-height: 40px;
            text-align: left;
            gap: 6px;
        }

        .handover-subtab-text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 2px;
        }

        .handover-subtab:hover {
            color: #667eea;
        }

        .handover-subtab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .handover-subtab.pending-timetec-active {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #f97316;
            box-shadow: 0 2px 4px rgba(249, 115, 22, 0.2);
        }

        .tab-separator {
            width: 3px;
            height: 3rem;
            background: linear-gradient(to bottom, transparent, #d1d5db, transparent);
            margin: 0 2rem;
        }

        .handover-subtab i {
            font-size: 14px;
        }

        .handover-subtab-content {
            display: none;
        }

        .handover-subtab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        .inquiry-tab-content {
            display: none;
        }

        .inquiry-tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        .inquiry-tab:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .inquiry-tab.active {
            background: white !important;
            color: #667eea !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .inquiry-tab.active i {
            color: #667eea;
        }

        .database-tab-content {
            display: none;
        }

        .database-tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        .database-tab:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .database-tab.active {
            background: white !important;
            color: #667eea !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .database-tab.active i {
            color: #667eea;
        }

        .installation-tab-content {
            display: none;
        }

        .installation-tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in-out;
        }

        .installation-tab:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .installation-tab.active {
            background: white !important;
            color: #667eea !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .installation-tab.active i {
            color: #667eea;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Fixed Header with Gradient -->
    <div class="relative overflow-hidden shadow-xl main-header gradient-bg">
        <div class="absolute inset-0 bg-black opacity-10"></div>
        <div class="relative px-4 py-6 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    {{-- <div class="flex items-center justify-center w-12 h-12 bg-white rounded-full shadow-lg">
                        <i class="text-2xl text-indigo-600 fas fa-handshake"></i>
                    </div> --}}
                    <div>
                        <h1 class="text-3xl font-bold text-white drop-shadow-lg">Reseller Portal</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <div class="text-right">
                        @php
                            $reseller = Auth::guard('reseller')->user();
                        @endphp

                        <p class="font-semibold text-white">{{ $resellerName }}</p>
                        <p class="text-sm font-medium text-indigo-200">{{ $companyName }}</p>
                    </div>
                    <form method="POST" action="{{ route('reseller.logout') }}">
                        @csrf
                        <button type="submit" class="px-6 py-3 font-semibold text-white transition-all duration-300 bg-red-500 rounded-full shadow-lg hover:bg-red-600 hover:shadow-xl hover:scale-105">
                            <i class="mr-2 fas fa-sign-out-alt"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @php
        $reseller = Auth::guard('reseller')->user();
        $resellerId = $reseller ? $reseller->reseller_id : null;

        $sidebarPendingConfirmationCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->where('status', 'pending_quotation_confirmation')->count() : 0;
        $sidebarPendingResellerCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->where('status', 'pending_invoice_confirmation')->count() : 0;
        $sidebarPendingPaymentCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->where('status', 'pending_reseller_payment')->count() : 0;
        $sidebarTotalPending = $sidebarPendingConfirmationCount + $sidebarPendingResellerCount + $sidebarPendingPaymentCount;

        $sidebarFdPendingCount = $resellerId ? \App\Models\ResellerHandoverFd::where('reseller_id', $resellerId)->whereIn('status', ['pending_quotation_confirmation', 'pending_invoice_confirmation', 'pending_reseller_payment'])->count() : 0;
        $sidebarFePendingCount = $resellerId ? \App\Models\ResellerHandoverFe::where('reseller_id', $resellerId)->whereIn('status', ['pending_quotation_confirmation', 'pending_invoice_confirmation', 'pending_reseller_payment'])->count() : 0;
        $sidebarFfPendingCount = $resellerId ? \App\Models\ResellerHandoverFf::where('reseller_id', $resellerId)->where('status', 'pending_quotation_confirmation')->count() : 0;
        $sidebarFgPendingCount = $resellerId ? \App\Models\ResellerHandoverFg::where('reseller_id', $resellerId)->whereIn('status', ['pending_quotation_confirmation', 'pending_invoice_confirmation', 'pending_reseller_payment'])->count() : 0;
        $sidebarRcPendingResellerCount = $resellerId ? \App\Models\ResellerCommissionHandover::where('reseller_id', $resellerId)->where('status', 'pending_reseller')->count() : 0;
    @endphp

    <div class="sidebar"
         x-data="{
             totalPending: {{ $sidebarTotalPending }},
             fdTotalPending: {{ $sidebarFdPendingCount }},
             feTotalPending: {{ $sidebarFePendingCount }},
             ffTotalPending: {{ $sidebarFfPendingCount }},
             fgTotalPending: {{ $sidebarFgPendingCount }},
             rcPendingResellerCount: {{ $sidebarRcPendingResellerCount }}
         }"
         @handover-updated.window="
             fetch('{{ route('reseller.handover.counts') }}')
                 .then(response => response.json())
                 .then(data => {
                     totalPending = data.pending_quotation_confirmation + data.pending_invoice_confirmation + data.pending_payment;
                 })
         "
         @fd-handover-updated.window="
             fetch('{{ route('reseller.fd-handover.counts') }}')
                 .then(response => response.json())
                 .then(data => {
                     fdTotalPending = (data.pending_confirmation || 0) + (data.pending_invoice_confirmation || 0) + (data.pending_payment || 0);
                 })
         "
         @fe-handover-updated.window="
             fetch('{{ route('reseller.fe-handover.counts') }}')
                 .then(response => response.json())
                 .then(data => {
                     feTotalPending = (data.pending_confirmation || 0) + (data.pending_invoice_confirmation || 0) + (data.pending_payment || 0);
                 })
         "
         @fg-handover-updated.window="
             fetch('{{ route('reseller.fg-handover.counts') }}')
                 .then(response => response.json())
                 .then(data => {
                     fgTotalPending = (data.pending_confirmation || 0) + (data.pending_invoice_confirmation || 0) + (data.pending_payment || 0);
                 })
         "
         @rc-commission-updated.window="
             rcPendingResellerCount = Math.max(0, rcPendingResellerCount - 1)
         ">
        <div class="sidebar-menu">
            <button onclick="switchTab('customers')"
                    id="customers-tab"
                    class="menu-item active">
                <i class="fas fa-users"></i>
                <span>Customers</span>
            </button>

            <button onclick="switchTab('expired')"
                    id="expired-tab"
                    class="menu-item">
                <i class="fas fa-calendar-times"></i>
                <span>Expired Licenses</span>
            </button>

            <hr>
            @if($reseller->renewal_quotation === 'enable')
                <button onclick="switchTab('handover'); closeSidebarMobile();"
                        id="handover-tab"
                        class="menu-item"
                        style="justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-plus"></i>
                        <span>Renewal Quotation</span>
                    </div>
                    <span x-show="totalPending > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="totalPending"></span>
                </button>
            @endif

            <!-- Submit Inquiry Button -->
            <button onclick="switchTab('submit-inquiry')"
                    id="submit-inquiry-tab"
                    class="action-button"
                    style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-plus"></i>
                    <span>Renewal Inquiry</span>
                </div>
                @livewire('reseller-inquiries-new', ['countOnly' => true], key('main-inquiry-count'))
            </button>

            <!-- Dealer Information Button -->
            @if($reseller && $reseller->id === 79)
            <button onclick="switchTab('dealer-information')"
                    id="dealer-information-tab"
                    class="action-button">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-building"></i>
                    <span>Dealer Information</span>
                </div>
            </button>
            @endif

            <!-- Trial Account Button -->
            @if($reseller && $reseller->trial_account_feature === 'enable')
            <button onclick="switchTab('database-creation')"
                    id="database-creation-tab"
                    class="action-button"
                    style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-plus"></i>
                    <span>Request Trial Account</span>
                </div>
                @livewire('reseller-database-creation-new', ['countOnly' => true], key('main-database-count'))
            </button>
            @endif

            <!-- Installation Payment Button -->
            @if($reseller && $reseller->installation_payment_feature === 'enable')
            <button onclick="switchTab('installation-payment'); closeSidebarMobile();"
                    id="installation-payment-tab"
                    class="action-button">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-plus"></i>
                    <span>Installation Payment</span>
                </div>
            </button>
            @endif

            <!-- Bill as Reseller (FD) Button -->
            @if($reseller && $reseller->bill_as_reseller === 'enable')
            <button onclick="switchTab('fd-handover'); closeSidebarMobile();"
                    id="fd-handover-tab"
                    class="action-button"
                    style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-plus"></i>
                    <span>Renewal Quotation</span>
                </div>
                <span x-show="fdTotalPending > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="fdTotalPending"></span>
            </button>
            @endif

            <!-- Bill as End User (FE) Button -->
            @if($reseller && $reseller->bill_as_end_user === 'enable')
            <button onclick="switchTab('fe-handover'); closeSidebarMobile();"
                    id="fe-handover-tab"
                    class="action-button"
                    style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-file-invoice"></i>
                    <span>Bill as End User</span>
                </div>
                <span x-show="feTotalPending > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="feTotalPending"></span>
            </button>
            @endif

            <!-- USD With Quotation (FF) Button -->
            @if($reseller && $reseller->usd_with_quotation === 'enable')
            <button onclick="switchTab('ff-handover'); closeSidebarMobile();"
                    id="ff-handover-tab"
                    class="action-button"
                    style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-plus"></i>
                    <span>Renewal Quotation</span>
                </div>
                <span x-show="ffTotalPending > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="ffTotalPending"></span>
            </button>
            @endif

            <!-- USD With Invoice (FG) Button -->
            @if($reseller && $reseller->usd_with_invoice === 'enable')
            <button onclick="switchTab('fg-handover'); closeSidebarMobile();"
                    id="fg-handover-tab"
                    class="action-button"
                    style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-plus"></i>
                    <span>Renewal Quotation</span>
                </div>
                <span x-show="fgTotalPending > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="fgTotalPending"></span>
            </button>
            @endif

            <!-- Reseller Commission Button -->
            @if($reseller && $reseller->reseller_commission === 'enable')
            <button onclick="switchTab('reseller-commission'); closeSidebarMobile();"
                    id="reseller-commission-tab"
                    class="action-button"
                    style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-hand-holding-usd"></i>
                    <span>Reseller Commission</span>
                </div>
                <span x-show="rcPendingResellerCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="rcPendingResellerCount"></span>
            </button>
            @endif
        </div>
    </div>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
        <!-- Main Content -->
        <main class="relative">
            <div>
                <!-- Active Customers Tab Content -->
                <div id="customers-content" class="p-8 tab-content active">
                    @livewire('reseller-active-customer-list')
                </div>

                <!-- Expired Licenses Tab Content -->
                <div id="expired-content" class="p-8 tab-content">
                    @livewire('reseller-expired-license')
                </div>

                <!-- Renewal Handover Tab Content -->
                @php
                    $reseller = Auth::guard('reseller')->user();
                    $resellerId = $reseller ? $reseller->reseller_id : null;

                    $pendingConfirmationCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->where('status', 'pending_quotation_confirmation')->count() : 0;
                    $pendingResellerCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->where('status', 'pending_invoice_confirmation')->count() : 0;
                    $pendingPaymentCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->where('status', 'pending_reseller_payment')->count() : 0;
                    $pendingTimetecActionCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->whereIn('status', ['pending_timetec_license', 'new', 'pending_timetec_invoice', 'pending_timetec_finance'])->count() : 0;
                    $completedCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->where('status', 'completed')->count() : 0;
                    $allItemsCount = $resellerId ? \App\Models\ResellerHandover::where('reseller_id', $resellerId)->count() : 0;
                @endphp

                <div id="handover-content" class="pt-2 pl-4 pr-4 pb-28 tab-content"
                     x-data="{
                         pendingConfirmationCount: {{ $pendingConfirmationCount }},
                         pendingResellerCount: {{ $pendingResellerCount }},
                         pendingPaymentCount: {{ $pendingPaymentCount }},
                         pendingTimetecActionCount: {{ $pendingTimetecActionCount }},
                         completedCount: {{ $completedCount }},
                         allItemsCount: {{ $allItemsCount }}
                     }"
                     @handover-completed-notification.window="
                         setTimeout(() => {
                             window.dispatchEvent(new CustomEvent('handover-updated'));
                         }, 2500);
                     "
                     @handover-updated.window="
                         fetch('{{ route('reseller.handover.counts') }}')
                             .then(response => response.json())
                             .then(data => {
                                 pendingConfirmationCount = data.pending_quotation_confirmation;
                                 pendingResellerCount = data.pending_invoice_confirmation;
                                 pendingPaymentCount = data.pending_payment;
                                 pendingTimetecActionCount = data.pending_timetec_license;
                                 completedCount = data.completed;
                                 allItemsCount = data.all_items || (data.pending_quotation_confirmation + data.pending_invoice_confirmation + data.pending_payment + data.pending_timetec_license + data.completed);
                             })
                     ">

                    <!-- Renewal Request Button -->
                    <div class="flex justify-end mb-2">
                        @livewire('reseller-renewal-request')
                    </div>

                    <div class="mb-6">
                        <div class="flex w-full p-1 space-x-1 bg-gray-100 rounded-lg" role="tablist">
                            <button
                                onclick="switchHandoverSubTab('pending-confirmation')"
                                id="pending-confirmation-subtab"
                                class="handover-subtab active"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Quotation</span>
                                    <span>Confirmation</span>
                                </div>
                                <span x-show="pendingConfirmationCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="pendingConfirmationCount"></span>
                            </button>
                            <button
                                onclick="switchHandoverSubTab('pending-reseller')"
                                id="pending-reseller-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Invoice</span>
                                    <span>Confirmation</span>
                                </div>
                                <span x-show="pendingResellerCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="pendingResellerCount"></span>
                            </button>
                            <button
                                onclick="switchHandoverSubTab('pending-payment')"
                                id="pending-payment-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Reseller</span>
                                    <span>Payment</span>
                                </div>
                                <span x-show="pendingPaymentCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="pendingPaymentCount"></span>
                            </button>
                            <button
                                onclick="switchHandoverSubTab('completed')"
                                id="completed-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Status</span>
                                    <span>Completed</span>
                                </div>
                                <span x-show="completedCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="completedCount"></span>
                            </button>
                            <div class="tab-separator"></div>
                            <button
                                onclick="switchHandoverSubTab('pending-timetec-action')"
                                id="pending-timetec-action-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending</span>
                                    <span>TimeTec</span>
                                </div>
                                <span x-show="pendingTimetecActionCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="pendingTimetecActionCount"></span>
                            </button>
                            <button
                                onclick="switchHandoverSubTab('all-items')"
                                id="all-items-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>All</span>
                                    <span>Status</span>
                                </div>
                                <span x-show="allItemsCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="allItemsCount"></span>
                            </button>
                        </div>
                    </div>

                    <div id="pending-confirmation-subtab-content" class="handover-subtab-content active">
                        @livewire('reseller-handover-pending-confirmation')
                    </div>

                    <div id="pending-reseller-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-pending-reseller')
                    </div>

                    <div id="pending-payment-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-pending-payment')
                    </div>

                    <div id="completed-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-completed')
                    </div>

                    <div id="pending-timetec-action-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-pending-timetec-action')
                    </div>

                    <div id="all-items-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-all-items')
                    </div>
                </div>

                <!-- Submit Inquiry Tab Content -->
                @php
                    $newInquiryCount = $resellerId ? \App\Models\ResellerInquiry::where('reseller_id', $resellerId)->where('status', 'new')->count() : 0;
                    $draftInquiryCount = $resellerId ? \App\Models\ResellerInquiry::where('reseller_id', $resellerId)->where('status', 'draft')->count() : 0;
                    $rejectedInquiryCount = $resellerId ? \App\Models\ResellerInquiry::where('reseller_id', $resellerId)->where('status', 'rejected')->count() : 0;
                    $completedInquiryCount = $resellerId ? \App\Models\ResellerInquiry::where('reseller_id', $resellerId)->where('status', 'completed')->count() : 0;
                @endphp

                <div id="submit-inquiry-content" class="p-4 tab-content"
                     x-data="{
                         newInquiryCount: {{ $newInquiryCount }},
                         draftInquiryCount: {{ $draftInquiryCount }},
                         rejectedInquiryCount: {{ $rejectedInquiryCount }},
                         completedInquiryCount: {{ $completedInquiryCount }}
                     }"
                     @handover-updated.window="
                         fetch('{{ route('reseller.inquiry.counts') }}')
                             .then(response => response.json())
                             .then(data => {
                                 newInquiryCount = data.new_inquiries;
                                 draftInquiryCount = data.draft_inquiries;
                                 rejectedInquiryCount = data.rejected_inquiries;
                                 completedInquiryCount = data.completed_inquiries;
                             })
                     ">

                    <!-- Submit Inquiry Button -->
                    <div class="flex justify-end mb-2">
                        @livewire('reseller-submit-inquiry-button')
                    </div>

                    <!-- Tab Buttons -->
                    <div class="flex w-full p-1 mb-6 space-x-1 bg-gray-100 rounded-lg" role="tablist">
                        <button
                            onclick="switchInquiryTab('new')"
                            id="new-inquiry-tab"
                            class="inquiry-tab active"
                            role="tab"
                            style="flex: 1; display: inline-flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.2s ease; min-height: 48px; gap: 8px;">
                            <span>New</span>
                            <span x-show="newInquiryCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="newInquiryCount"></span>
                        </button>
                        <button
                            onclick="switchInquiryTab('completed')"
                            id="completed-inquiry-tab"
                            class="inquiry-tab"
                            role="tab"
                            style="flex: 1; display: inline-flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.2s ease; min-height: 48px; gap: 8px;">
                            <span>Completed</span>
                            <span x-show="completedInquiryCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="completedInquiryCount"></span>
                        </button>
                        <button
                            onclick="switchInquiryTab('rejected')"
                            id="rejected-inquiry-tab"
                            class="inquiry-tab"
                            role="tab"
                            style="flex: 1; display: inline-flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.2s ease; min-height: 48px; gap: 8px;">
                            <span>Rejected</span>
                            <span x-show="rejectedInquiryCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="rejectedInquiryCount"></span>
                        </button>
                        <button
                            onclick="switchInquiryTab('draft')"
                            id="draft-inquiry-tab"
                            class="inquiry-tab"
                            role="tab"
                            style="flex: 1; display: inline-flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.2s ease; min-height: 48px; gap: 8px;">
                            <span>Draft</span>
                            <span x-show="draftInquiryCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="draftInquiryCount"></span>
                        </button>
                    </div>

                    <!-- New Inquiries Table -->
                    <div id="new-inquiry-content" class="inquiry-tab-content active">
                        @livewire('reseller-inquiries-new', key('new-inquiry-table'))
                    </div>

                    <!-- Draft Inquiries Table -->
                    <div id="draft-inquiry-content" class="inquiry-tab-content">
                        @livewire('reseller-inquiries-draft', key('draft-inquiry-table'))
                    </div>

                    <!-- Rejected Inquiries Table -->
                    <div id="rejected-inquiry-content" class="inquiry-tab-content">
                        @livewire('reseller-inquiries-rejected', key('rejected-inquiry-table'))
                    </div>

                    <!-- Completed Inquiries Table -->
                    <div id="completed-inquiry-content" class="inquiry-tab-content">
                        @livewire('reseller-inquiries-completed', key('completed-inquiry-table'))
                    </div>
                </div>

                <!-- Database Creation Tab Content -->
                @if($reseller && $reseller->trial_account_feature === 'enable')
                @php
                    $newDatabaseCount = $resellerId ? \App\Models\ResellerDatabaseCreation::where('reseller_id', $resellerId)->where('status', 'new')->count() : 0;
                    $draftDatabaseCount = $resellerId ? \App\Models\ResellerDatabaseCreation::where('reseller_id', $resellerId)->where('status', 'draft')->count() : 0;
                    $rejectedDatabaseCount = $resellerId ? \App\Models\ResellerDatabaseCreation::where('reseller_id', $resellerId)->where('status', 'rejected')->count() : 0;
                    $completedDatabaseCount = $resellerId ? \App\Models\ResellerDatabaseCreation::where('reseller_id', $resellerId)->where('status', 'completed')->count() : 0;
                @endphp

                <div id="database-creation-content" class="p-4 tab-content"
                     x-data="{
                         newDatabaseCount: {{ $newDatabaseCount }},
                         draftDatabaseCount: {{ $draftDatabaseCount }},
                         rejectedDatabaseCount: {{ $rejectedDatabaseCount }},
                         completedDatabaseCount: {{ $completedDatabaseCount }}
                     }"
                     @database-creation-updated.window="
                         fetch('{{ route('reseller.database-creation.counts') }}')
                             .then(response => response.json())
                             .then(data => {
                                 newDatabaseCount = data.new_database;
                                 draftDatabaseCount = data.draft_database;
                                 rejectedDatabaseCount = data.rejected_database;
                                 completedDatabaseCount = data.completed_database;
                             })
                     ">

                    <!-- Database Creation Button -->
                    <div class="flex justify-end mb-2">
                        @livewire('reseller-database-creation-button', key('database-creation-button'))
                    </div>

                    <!-- Tab Buttons -->
                    <div class="flex w-full p-1 mb-6 space-x-1 bg-gray-100 rounded-lg" role="tablist">
                        <button
                            onclick="switchDatabaseCreationTab('new')"
                            id="new-database-tab"
                            class="database-tab active"
                            role="tab"
                            style="flex: 1; display: inline-flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.2s ease; min-height: 48px; gap: 8px;">
                            <span>New</span>
                            <span x-show="newDatabaseCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="newDatabaseCount"></span>
                        </button>
                        <button
                            onclick="switchDatabaseCreationTab('completed')"
                            id="completed-database-tab"
                            class="database-tab"
                            role="tab"
                            style="flex: 1; display: inline-flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.2s ease; min-height: 48px; gap: 8px;">
                            <span>Completed</span>
                            <span x-show="completedDatabaseCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="completedDatabaseCount"></span>
                        </button>
                        <button
                            onclick="switchDatabaseCreationTab('rejected')"
                            id="rejected-database-tab"
                            class="database-tab"
                            role="tab"
                            style="flex: 1; display: inline-flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.2s ease; min-height: 48px; gap: 8px;">
                            <span>Rejected</span>
                            <span x-show="rejectedDatabaseCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="rejectedDatabaseCount"></span>
                        </button>
                        <button
                            onclick="switchDatabaseCreationTab('draft')"
                            id="draft-database-tab"
                            class="database-tab"
                            role="tab"
                            style="flex: 1; display: inline-flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.2s ease; min-height: 48px; gap: 8px;">
                            <span>Draft</span>
                            <span x-show="draftDatabaseCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="draftDatabaseCount"></span>
                        </button>
                    </div>

                    <!-- New Database Creation Requests Table -->
                    <div id="new-database-content" class="database-tab-content active">
                        @livewire('reseller-database-creation-new', key('new-database-table'))
                    </div>

                    <!-- Draft Database Creation Requests Table -->
                    <div id="draft-database-content" class="database-tab-content">
                        @livewire('reseller-database-creation-draft', key('draft-database-table'))
                    </div>

                    <!-- Rejected Database Creation Requests Table -->
                    <div id="rejected-database-content" class="database-tab-content">
                        @livewire('reseller-database-creation-rejected', key('rejected-database-table'))
                    </div>

                    <!-- Completed Database Creation Requests Table -->
                    <div id="completed-database-content" class="database-tab-content">
                        @livewire('reseller-database-creation-completed', key('completed-database-table'))
                    </div>
                </div>
                @endif

                <!-- Installation Payment Tab Content -->
                @if($reseller && $reseller->installation_payment_feature === 'enable')
                @php
                    $newPaymentCount = $resellerId ? \App\Models\ResellerInstallationPayment::where('reseller_id', $resellerId)->where('status', 'new')->count() : 0;
                    $completedPaymentCount = $resellerId ? \App\Models\ResellerInstallationPayment::where('reseller_id', $resellerId)->where('status', 'completed')->count() : 0;
                @endphp

                <div id="installation-payment-content" class="p-4 tab-content"
                     x-data="{
                         newPaymentCount: {{ $newPaymentCount }},
                         completedPaymentCount: {{ $completedPaymentCount }}
                     }"
                     @installation-payment-updated.window="
                         fetch('{{ route('reseller.installation-payment.counts') }}')
                             .then(response => response.json())
                             .then(data => {
                                 newPaymentCount = data.new_payments;
                                 completedPaymentCount = data.completed_payments;
                             })
                     ">

                    <!-- Submit Button -->
                    <div class="flex justify-end mb-2">
                        @livewire('reseller-installation-payment-button')
                    </div>

                    <!-- Tab Buttons -->
                    <div class="mb-6 tab-bar-scroll">
                        <div class="flex w-full p-1 space-x-1 bg-gray-100 rounded-lg tab-bar-inner" role="tablist">
                            <button
                                onclick="switchInstallationTab('new')"
                                id="new-installation-tab"
                                class="installation-tab active"
                                role="tab"
                                style="flex: 1; display: inline-flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.2s ease; min-height: 48px; gap: 8px;">
                                <span>New</span>
                                <span x-show="newPaymentCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="newPaymentCount"></span>
                            </button>
                            <button
                                onclick="switchInstallationTab('completed')"
                                id="completed-installation-tab"
                                class="installation-tab"
                                role="tab"
                                style="flex: 1; display: inline-flex; flex-direction: row; align-items: center; justify-content: space-between; padding: 12px 16px; border-radius: 8px; font-size: 0.875rem; font-weight: 600; color: #64748b; background: transparent; border: none; cursor: pointer; transition: all 0.2s ease; min-height: 48px; gap: 8px;">
                                <span>Completed</span>
                                <span x-show="completedPaymentCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="completedPaymentCount"></span>
                            </button>
                        </div>
                    </div>

                    <!-- New Payments Table -->
                    <div id="new-installation-content" class="installation-tab-content active">
                        @livewire('reseller-installation-payment-new', key('new-installation-table'))
                    </div>

                    <!-- Completed Payments Table -->
                    <div id="completed-installation-content" class="installation-tab-content">
                        @livewire('reseller-installation-payment-completed', key('completed-installation-table'))
                    </div>
                </div>
                @endif

                {{-- <!-- Project Plan Tab Content -->
                @if($hasProjectPlan)
                    <div id="project-content" class="p-8 tab-content">
                        @livewire('customer-project-plan')
                    </div>
                @endif --}}

                <!-- Bill as Reseller (FD) Tab Content -->
                @if($reseller && $reseller->bill_as_reseller === 'enable')
                @php
                    $fdPendingConfirmationCount = $resellerId ? \App\Models\ResellerHandoverFd::where('reseller_id', $resellerId)->where('status', 'pending_quotation_confirmation')->count() : 0;
                    $fdPendingInvoiceConfirmationCount = $resellerId ? \App\Models\ResellerHandoverFd::where('reseller_id', $resellerId)->where('status', 'pending_invoice_confirmation')->count() : 0;
                    $fdPendingPaymentCount = $resellerId ? \App\Models\ResellerHandoverFd::where('reseller_id', $resellerId)->where('status', 'pending_reseller_payment')->count() : 0;
                    $fdPendingTimetecCount = $resellerId ? \App\Models\ResellerHandoverFd::where('reseller_id', $resellerId)->whereIn('status', ['new', 'pending_timetec_invoice'])->count() : 0;
                    $fdCompletedCount = $resellerId ? \App\Models\ResellerHandoverFd::where('reseller_id', $resellerId)->where('status', 'completed')->count() : 0;
                    $fdAllItemsCount = $resellerId ? \App\Models\ResellerHandoverFd::where('reseller_id', $resellerId)->count() : 0;
                @endphp

                <div id="fd-handover-content" class="pt-2 pl-4 pr-4 pb-28 tab-content"
                     x-data="{
                         fdPendingConfirmationCount: {{ $fdPendingConfirmationCount }},
                         fdPendingInvoiceConfirmationCount: {{ $fdPendingInvoiceConfirmationCount }},
                         fdPendingPaymentCount: {{ $fdPendingPaymentCount }},
                         fdPendingTimetecCount: {{ $fdPendingTimetecCount }},
                         fdCompletedCount: {{ $fdCompletedCount }},
                         fdAllItemsCount: {{ $fdAllItemsCount }}
                     }"
                     @fd-handover-updated.window="
                         fetch('{{ route('reseller.fd-handover.counts') }}')
                             .then(response => response.json())
                             .then(data => {
                                 fdPendingConfirmationCount = data.pending_confirmation;
                                 fdPendingInvoiceConfirmationCount = data.pending_invoice_confirmation;
                                 fdPendingPaymentCount = data.pending_payment;
                                 fdPendingTimetecCount = data.pending_timetec;
                                 fdCompletedCount = data.completed;
                                 fdAllItemsCount = data.all_items;
                             })
                     ">

                    <!-- FD Request Button -->
                    <div class="flex justify-end mb-2">
                        @livewire('reseller-renewal-request-fd')
                    </div>

                    <div class="mb-6">
                        <div class="flex w-full p-1 space-x-1 bg-gray-100 rounded-lg" role="tablist">
                            <button
                                onclick="switchFdHandoverSubTab('fd-pending-confirmation')"
                                id="fd-pending-confirmation-subtab"
                                class="handover-subtab active"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Quotation</span>
                                    <span>Confirmation</span>
                                </div>
                                <span x-show="fdPendingConfirmationCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="fdPendingConfirmationCount"></span>
                            </button>
                            <button
                                onclick="switchFdHandoverSubTab('fd-pending-invoice-confirmation')"
                                id="fd-pending-invoice-confirmation-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Invoice</span>
                                    <span>Confirmation</span>
                                </div>
                                <span x-show="fdPendingInvoiceConfirmationCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="fdPendingInvoiceConfirmationCount"></span>
                            </button>
                            <button
                                onclick="switchFdHandoverSubTab('fd-pending-payment')"
                                id="fd-pending-payment-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Reseller</span>
                                    <span>Payment</span>
                                </div>
                                <span x-show="fdPendingPaymentCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="fdPendingPaymentCount"></span>
                            </button>
                            <button
                                onclick="switchFdHandoverSubTab('fd-completed')"
                                id="fd-completed-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Status</span>
                                    <span>Completed</span>
                                </div>
                                <span x-show="fdCompletedCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="fdCompletedCount"></span>
                            </button>
                            <div class="tab-separator"></div>
                            <button
                                onclick="switchFdHandoverSubTab('fd-pending-timetec')"
                                id="fd-pending-timetec-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending</span>
                                    <span>TimeTec</span>
                                </div>
                                <span x-show="fdPendingTimetecCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="fdPendingTimetecCount"></span>
                            </button>
                            <button
                                onclick="switchFdHandoverSubTab('fd-all-items')"
                                id="fd-all-items-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>All</span>
                                    <span>Status</span>
                                </div>
                                <span x-show="fdAllItemsCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="fdAllItemsCount"></span>
                            </button>
                        </div>
                    </div>

                    <div id="fd-pending-confirmation-subtab-content" class="handover-subtab-content active">
                        @livewire('reseller-handover-fd-pending-confirmation')
                    </div>

                    <div id="fd-pending-invoice-confirmation-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fd-pending-invoice-confirmation')
                    </div>

                    <div id="fd-pending-payment-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fd-pending-payment')
                    </div>

                    <div id="fd-completed-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fd-completed')
                    </div>

                    <div id="fd-pending-timetec-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fd-pending-timetec-action')
                    </div>

                    <div id="fd-all-items-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fd-all-items')
                    </div>
                </div>
                @endif

                <!-- Bill as End User (FE) Tab Content -->
                @if($reseller && $reseller->bill_as_end_user === 'enable')
                @php
                    $fePendingConfirmationCount = $resellerId ? \App\Models\ResellerHandoverFe::where('reseller_id', $resellerId)->where('status', 'pending_quotation_confirmation')->count() : 0;
                    $fePendingInvoiceConfirmationCount = $resellerId ? \App\Models\ResellerHandoverFe::where('reseller_id', $resellerId)->where('status', 'pending_invoice_confirmation')->count() : 0;
                    $fePendingPaymentCount = $resellerId ? \App\Models\ResellerHandoverFe::where('reseller_id', $resellerId)->where('status', 'pending_reseller_payment')->count() : 0;
                    $fePendingTimetecCount = $resellerId ? \App\Models\ResellerHandoverFe::where('reseller_id', $resellerId)->whereIn('status', ['new', 'pending_timetec_invoice'])->count() : 0;
                    $feCompletedCount = $resellerId ? \App\Models\ResellerHandoverFe::where('reseller_id', $resellerId)->where('status', 'completed')->count() : 0;
                    $feAllItemsCount = $resellerId ? \App\Models\ResellerHandoverFe::where('reseller_id', $resellerId)->count() : 0;
                @endphp

                <div id="fe-handover-content" class="pt-2 pl-4 pr-4 pb-28 tab-content"
                     x-data="{
                         fePendingConfirmationCount: {{ $fePendingConfirmationCount }},
                         fePendingInvoiceConfirmationCount: {{ $fePendingInvoiceConfirmationCount }},
                         fePendingPaymentCount: {{ $fePendingPaymentCount }},
                         fePendingTimetecCount: {{ $fePendingTimetecCount }},
                         feCompletedCount: {{ $feCompletedCount }},
                         feAllItemsCount: {{ $feAllItemsCount }}
                     }"
                     @fe-handover-updated.window="
                         fetch('{{ route('reseller.fe-handover.counts') }}')
                             .then(response => response.json())
                             .then(data => {
                                 fePendingConfirmationCount = data.pending_confirmation;
                                 fePendingInvoiceConfirmationCount = data.pending_invoice_confirmation;
                                 fePendingPaymentCount = data.pending_payment;
                                 fePendingTimetecCount = data.pending_timetec;
                                 feCompletedCount = data.completed;
                                 feAllItemsCount = data.all_items;
                             })
                     ">

                    <!-- FE Request Button -->
                    <div class="flex justify-end mb-2">
                        @livewire('reseller-renewal-request-fe')
                    </div>

                    <div class="mb-6">
                        <div class="flex w-full p-1 space-x-1 bg-gray-100 rounded-lg" role="tablist">
                            <button
                                onclick="switchFeHandoverSubTab('fe-pending-confirmation')"
                                id="fe-pending-confirmation-subtab"
                                class="handover-subtab active"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Quotation</span>
                                    <span>Confirmation</span>
                                </div>
                                <span x-show="fePendingConfirmationCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="fePendingConfirmationCount"></span>
                            </button>
                            <button
                                onclick="switchFeHandoverSubTab('fe-pending-invoice-confirmation')"
                                id="fe-pending-invoice-confirmation-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Invoice</span>
                                    <span>Confirmation</span>
                                </div>
                                <span x-show="fePendingInvoiceConfirmationCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="fePendingInvoiceConfirmationCount"></span>
                            </button>
                            <button
                                onclick="switchFeHandoverSubTab('fe-pending-payment')"
                                id="fe-pending-payment-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Reseller</span>
                                    <span>Payment</span>
                                </div>
                                <span x-show="fePendingPaymentCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="fePendingPaymentCount"></span>
                            </button>
                            <button
                                onclick="switchFeHandoverSubTab('fe-completed')"
                                id="fe-completed-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Status</span>
                                    <span>Completed</span>
                                </div>
                                <span x-show="feCompletedCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="feCompletedCount"></span>
                            </button>
                            <div class="tab-separator"></div>
                            <button
                                onclick="switchFeHandoverSubTab('fe-pending-timetec')"
                                id="fe-pending-timetec-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending</span>
                                    <span>TimeTec</span>
                                </div>
                                <span x-show="fePendingTimetecCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="fePendingTimetecCount"></span>
                            </button>
                            <button
                                onclick="switchFeHandoverSubTab('fe-all-items')"
                                id="fe-all-items-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>All</span>
                                    <span>Status</span>
                                </div>
                                <span x-show="feAllItemsCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="feAllItemsCount"></span>
                            </button>
                        </div>
                    </div>

                    <div id="fe-pending-confirmation-subtab-content" class="handover-subtab-content active">
                        @livewire('reseller-handover-fe-pending-confirmation')
                    </div>

                    <div id="fe-pending-invoice-confirmation-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fe-pending-invoice-confirmation')
                    </div>

                    <div id="fe-pending-payment-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fe-pending-payment')
                    </div>

                    <div id="fe-completed-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fe-completed')
                    </div>

                    <div id="fe-pending-timetec-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fe-pending-timetec-action')
                    </div>

                    <div id="fe-all-items-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fe-all-items')
                    </div>
                </div>
                @endif

                <!-- USD With Quotation (FF) Tab Content -->
                @if($reseller && $reseller->usd_with_quotation === 'enable')
                @php
                    $ffPendingConfirmationCount = $resellerId ? \App\Models\ResellerHandoverFf::where('reseller_id', $resellerId)->where('status', 'pending_quotation_confirmation')->count() : 0;
                    $ffCompletedCount = $resellerId ? \App\Models\ResellerHandoverFf::where('reseller_id', $resellerId)->where('status', 'completed')->count() : 0;
                    $ffAllItemsCount = $resellerId ? \App\Models\ResellerHandoverFf::where('reseller_id', $resellerId)->count() : 0;
                @endphp

                <div id="ff-handover-content" class="pt-2 pl-4 pr-4 pb-28 tab-content"
                     x-data="{
                         ffPendingConfirmationCount: {{ $ffPendingConfirmationCount }},
                         ffCompletedCount: {{ $ffCompletedCount }},
                         ffAllItemsCount: {{ $ffAllItemsCount }}
                     }"
                     @ff-handover-updated.window="
                         fetch('{{ route('reseller.ff-handover.counts') }}')
                             .then(response => response.json())
                             .then(data => {
                                 ffPendingConfirmationCount = data.pending_confirmation;
                                 ffCompletedCount = data.completed;
                                 ffAllItemsCount = data.all_items;
                             })
                     ">

                    <!-- FF Request Button -->
                    <div class="flex justify-end mb-2">
                        @livewire('reseller-renewal-request-ff')
                    </div>

                    <div class="mb-6">
                        <div class="flex w-full p-1 space-x-1 bg-gray-100 rounded-lg" role="tablist">
                            <button
                                onclick="switchFfHandoverSubTab('ff-pending-confirmation')"
                                id="ff-pending-confirmation-subtab"
                                class="handover-subtab active"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Quotation</span>
                                    <span>Confirmation</span>
                                </div>
                                <span x-show="ffPendingConfirmationCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="ffPendingConfirmationCount"></span>
                            </button>
                            <button
                                onclick="switchFfHandoverSubTab('ff-completed')"
                                id="ff-completed-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Status</span>
                                    <span>Completed</span>
                                </div>
                                <span x-show="ffCompletedCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="ffCompletedCount"></span>
                            </button>
                            <button
                                onclick="switchFfHandoverSubTab('ff-all-items')"
                                id="ff-all-items-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>All</span>
                                    <span>Status</span>
                                </div>
                                <span x-show="ffAllItemsCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="ffAllItemsCount"></span>
                            </button>
                        </div>
                    </div>

                    <div id="ff-pending-confirmation-subtab-content" class="handover-subtab-content active">
                        @livewire('reseller-handover-ff-pending-confirmation')
                    </div>

                    <div id="ff-completed-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-ff-all-items', ['statusFilter' => 'completed'])
                    </div>

                    <div id="ff-all-items-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-ff-all-items')
                    </div>
                </div>
                @endif

                <!-- USD With Invoice (FG) Tab Content -->
                @php
                    $fgPendingConfirmationCount = $resellerId ? \App\Models\ResellerHandoverFg::where('reseller_id', $resellerId)->where('status', 'pending_quotation_confirmation')->count() : 0;
                    $fgPendingInvoiceConfirmationCount = $resellerId ? \App\Models\ResellerHandoverFg::where('reseller_id', $resellerId)->where('status', 'pending_invoice_confirmation')->count() : 0;
                    $fgPendingPaymentCount = $resellerId ? \App\Models\ResellerHandoverFg::where('reseller_id', $resellerId)->where('status', 'pending_reseller_payment')->count() : 0;
                    $fgCompletedCount = $resellerId ? \App\Models\ResellerHandoverFg::where('reseller_id', $resellerId)->where('status', 'completed')->count() : 0;
                    $fgPendingTimetecCount = $resellerId ? \App\Models\ResellerHandoverFg::where('reseller_id', $resellerId)->whereIn('status', ['new', 'pending_timetec_invoice', 'pending_timetec_license', 'pending_timetec_finance'])->count() : 0;
                    $fgAllItemsCount = $resellerId ? \App\Models\ResellerHandoverFg::where('reseller_id', $resellerId)->count() : 0;
                @endphp

                <div id="fg-handover-content" class="pt-2 pl-4 pr-4 pb-28 tab-content"
                     x-data="{
                         fgPendingConfirmationCount: {{ $fgPendingConfirmationCount }},
                         fgPendingInvoiceConfirmationCount: {{ $fgPendingInvoiceConfirmationCount }},
                         fgPendingPaymentCount: {{ $fgPendingPaymentCount }},
                         fgCompletedCount: {{ $fgCompletedCount }},
                         fgPendingTimetecCount: {{ $fgPendingTimetecCount }},
                         fgAllItemsCount: {{ $fgAllItemsCount }}
                     }"
                     @fg-handover-updated.window="
                         fetch('{{ route('reseller.fg-handover.counts') }}')
                             .then(response => response.json())
                             .then(data => {
                                 fgPendingConfirmationCount = data.pending_confirmation;
                                 fgPendingInvoiceConfirmationCount = data.pending_invoice_confirmation || 0;
                                 fgPendingPaymentCount = data.pending_payment;
                                 fgCompletedCount = data.completed;
                                 fgPendingTimetecCount = data.pending_timetec;
                                 fgAllItemsCount = data.all_items;
                             })
                     ">

                    <!-- FG Request Button -->
                    <div class="flex justify-end mb-2">
                        @livewire('reseller-renewal-request-fg')
                    </div>

                    <div class="mb-6">
                        <div class="flex w-full p-1 space-x-1 bg-gray-100 rounded-lg" role="tablist">
                            <button
                                onclick="switchFgHandoverSubTab('fg-pending-confirmation')"
                                id="fg-pending-confirmation-subtab"
                                class="handover-subtab active"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Quotation</span>
                                    <span>Confirmation</span>
                                </div>
                                <span x-show="fgPendingConfirmationCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="fgPendingConfirmationCount"></span>
                            </button>
                            <button
                                onclick="switchFgHandoverSubTab('fg-pending-invoice-confirmation')"
                                id="fg-pending-invoice-confirmation-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Invoice</span>
                                    <span>Confirmation</span>
                                </div>
                                <span x-show="fgPendingInvoiceConfirmationCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="fgPendingInvoiceConfirmationCount"></span>
                            </button>
                            <button
                                onclick="switchFgHandoverSubTab('fg-pending-payment')"
                                id="fg-pending-payment-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending Reseller</span>
                                    <span>Payment</span>
                                </div>
                                <span x-show="fgPendingPaymentCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="fgPendingPaymentCount"></span>
                            </button>
                            <button
                                onclick="switchFgHandoverSubTab('fg-completed')"
                                id="fg-completed-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Status</span>
                                    <span>Completed</span>
                                </div>
                                <span x-show="fgCompletedCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="fgCompletedCount"></span>
                            </button>
                            <button
                                onclick="switchFgHandoverSubTab('fg-pending-timetec')"
                                id="fg-pending-timetec-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending</span>
                                    <span>TimeTec</span>
                                </div>
                                <span x-show="fgPendingTimetecCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-yellow-500 rounded-full" x-text="fgPendingTimetecCount"></span>
                            </button>
                            <button
                                onclick="switchFgHandoverSubTab('fg-all-items')"
                                id="fg-all-items-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>All</span>
                                    <span>Status</span>
                                </div>
                                <span x-show="fgAllItemsCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="fgAllItemsCount"></span>
                            </button>
                        </div>
                    </div>

                    <div id="fg-pending-confirmation-subtab-content" class="handover-subtab-content active">
                        @livewire('reseller-handover-fg-pending-confirmation')
                    </div>

                    <div id="fg-pending-invoice-confirmation-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fg-pending-invoice-confirmation')
                    </div>

                    <div id="fg-pending-payment-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fg-pending-payment')
                    </div>

                    <div id="fg-completed-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fg-all-items', ['statusFilter' => 'completed'])
                    </div>

                    <div id="fg-pending-timetec-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fg-pending-timetec-action')
                    </div>

                    <div id="fg-all-items-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-handover-fg-all-items')
                    </div>
                </div>

                <!-- Reseller Commission Tab Content -->
                @php
                    $rcResellerId = $resellerId;
                    $rcPendingResellerCount = $rcResellerId ? \App\Models\ResellerCommissionHandover::where('reseller_id', $rcResellerId)->where('status', 'pending_reseller')->count() : 0;
                    $rcPendingFinanceCount = $rcResellerId ? \App\Models\ResellerCommissionHandover::where('reseller_id', $rcResellerId)->where('status', 'pending_finance')->count() : 0;
                    $rcCompletedCount = $rcResellerId ? \App\Models\ResellerCommissionHandover::where('reseller_id', $rcResellerId)->where('status', 'completed')->count() : 0;
                @endphp

                <div id="reseller-commission-content" class="pt-2 pl-4 pr-4 pb-28 tab-content"
                     x-data="{
                         rcPendingResellerCount: {{ $rcPendingResellerCount }},
                         rcPendingFinanceCount: {{ $rcPendingFinanceCount }},
                         rcCompletedCount: {{ $rcCompletedCount }}
                     }"
                     @rc-commission-updated.window="
                         rcPendingResellerCount = Math.max(0, rcPendingResellerCount - 1);
                         rcPendingFinanceCount = rcPendingFinanceCount + 1;
                     ">

                    <!-- Empty space to match other handover tabs -->
                    <div class="flex justify-end mb-2">&nbsp;</div>

                    <div class="mb-6">
                        <div class="flex w-full p-1 space-x-1 bg-gray-100 rounded-lg" role="tablist">
                            <button
                                onclick="switchCommissionSubTab('rc-pending-reseller')"
                                id="rc-pending-reseller-subtab"
                                class="handover-subtab active"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending</span>
                                    <span>Reseller</span>
                                </div>
                                <span x-show="rcPendingResellerCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-red-500 rounded-full" x-text="rcPendingResellerCount"></span>
                            </button>
                            <button
                                onclick="switchCommissionSubTab('rc-pending-finance')"
                                id="rc-pending-finance-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Pending</span>
                                    <span>Finance</span>
                                </div>
                                <span x-show="rcPendingFinanceCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-yellow-500 rounded-full" x-text="rcPendingFinanceCount"></span>
                            </button>
                            <button
                                onclick="switchCommissionSubTab('rc-completed')"
                                id="rc-completed-subtab"
                                class="handover-subtab"
                                role="tab"
                                style="flex: 1;">
                                <div class="handover-subtab-text">
                                    <span>Status</span>
                                    <span>Completed</span>
                                </div>
                                <span x-show="rcCompletedCount > 0" class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" x-text="rcCompletedCount"></span>
                            </button>
                        </div>
                    </div>

                    <div id="rc-pending-reseller-subtab-content" class="handover-subtab-content active">
                        @livewire('reseller-dashboard.rc-pending-reseller-table')
                    </div>

                    <div id="rc-pending-finance-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-dashboard.rc-pending-finance-table')
                    </div>

                    <div id="rc-completed-subtab-content" class="handover-subtab-content">
                        @livewire('reseller-dashboard.rc-completed-table')
                    </div>
                </div>

            </div>

                <!-- Dealer Information Tab Content -->
                <div id="dealer-information-content" class="p-8 tab-content">
                    @livewire('reseller-dealer-information')
                </div>

        </main>
    </div>

    @livewireScripts

    <!-- Enhanced JavaScript -->
    <script>
        function switchTab(tab) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all buttons (both menu-item and action-button)
            document.querySelectorAll('.menu-item, .action-button').forEach(button => {
                button.classList.remove('active');
            });

            // Show selected tab content
            const tabContent = document.getElementById(tab + '-content');
            const tabButton = document.getElementById(tab + '-tab');

            if (tabContent && tabButton) {
                tabContent.classList.add('active');
                tabButton.classList.add('active');

                // Store active tab in localStorage
                localStorage.setItem('resellerActiveTab', tab);

                // Reset subtabs to first one when switching main tabs
                if (tab === 'handover') {
                    switchHandoverSubTab('pending-confirmation');
                } else if (tab === 'fd-handover') {
                    switchFdHandoverSubTab('fd-pending-confirmation');
                } else if (tab === 'fe-handover') {
                    switchFeHandoverSubTab('fe-pending-confirmation');
                } else if (tab === 'ff-handover') {
                    switchFfHandoverSubTab('ff-pending-confirmation');
                } else if (tab === 'fg-handover') {
                    switchFgHandoverSubTab('fg-pending-confirmation');
                } else if (tab === 'reseller-commission') {
                    switchCommissionSubTab('rc-pending-reseller');
                }
            }
        }

        function activateRequestQuotation() {
            // Remove active class from all buttons
            document.querySelectorAll('.menu-item, .action-button').forEach(button => {
                button.classList.remove('active');
            });

            // Activate the request quotation button
            const requestButton = document.getElementById('request-quotation-tab');
            if (requestButton) {
                requestButton.classList.add('active');
            }
        }

        function switchHandoverSubTab(subtab) {
            // Only target subtabs within the handover content
            const handoverContent = document.getElementById('handover-content');
            if (!handoverContent) return;

            handoverContent.querySelectorAll('.handover-subtab-content').forEach(content => {
                content.classList.remove('active');
            });

            handoverContent.querySelectorAll('.handover-subtab').forEach(button => {
                button.classList.remove('active');
                button.classList.remove('pending-timetec-active');
            });

            // Show selected subtab content
            const subtabContent = document.getElementById(subtab + '-subtab-content');
            const subtabButton = document.getElementById(subtab + '-subtab');

            if (subtabContent && subtabButton) {
                subtabContent.classList.add('active');

                // Add special class for pending-timetec-action
                if (subtab === 'pending-timetec-action') {
                    subtabButton.classList.add('pending-timetec-active');
                } else {
                    subtabButton.classList.add('active');
                }

                // Store active subtab in localStorage
                localStorage.setItem('resellerActiveHandoverSubTab', subtab);
            }
        }

        function switchInquiryTab(tab) {
            // Hide all inquiry tab contents
            document.querySelectorAll('.inquiry-tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all inquiry tab buttons
            document.querySelectorAll('.inquiry-tab').forEach(button => {
                button.classList.remove('active');
            });

            // Show selected inquiry tab content
            const tabContent = document.getElementById(tab + '-inquiry-content');
            const tabButton = document.getElementById(tab + '-inquiry-tab');

            if (tabContent && tabButton) {
                tabContent.classList.add('active');
                tabButton.classList.add('active');

                // Store active inquiry tab in localStorage
                localStorage.setItem('resellerActiveInquiryTab', tab);
            }
        }

        function switchDatabaseCreationTab(tab) {
            // Hide all database tab contents
            document.querySelectorAll('.database-tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all database tab buttons
            document.querySelectorAll('.database-tab').forEach(button => {
                button.classList.remove('active');
            });

            // Show selected database tab content
            const tabContent = document.getElementById(tab + '-database-content');
            const tabButton = document.getElementById(tab + '-database-tab');

            if (tabContent && tabButton) {
                tabContent.classList.add('active');
                tabButton.classList.add('active');

                // Store active database tab in localStorage
                localStorage.setItem('resellerActiveDatabaseTab', tab);
            }
        }

        function switchInstallationTab(tab) {
            // Hide all installation tab contents
            document.querySelectorAll('.installation-tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Remove active class from all installation tab buttons
            document.querySelectorAll('.installation-tab').forEach(button => {
                button.classList.remove('active');
            });

            // Show selected installation tab content
            const tabContent = document.getElementById(tab + '-installation-content');
            const tabButton = document.getElementById(tab + '-installation-tab');

            if (tabContent && tabButton) {
                tabContent.classList.add('active');
                tabButton.classList.add('active');

                // Store active installation tab in localStorage
                localStorage.setItem('resellerActiveInstallationTab', tab);
            }
        }

        function switchFdHandoverSubTab(subtab) {
            // Only target subtabs within the FD handover content
            const fdContent = document.getElementById('fd-handover-content');
            if (!fdContent) return;

            fdContent.querySelectorAll('.handover-subtab-content').forEach(content => {
                content.classList.remove('active');
            });
            fdContent.querySelectorAll('.handover-subtab').forEach(button => {
                button.classList.remove('active');
                button.classList.remove('pending-timetec-active');
            });

            const subtabContent = document.getElementById(subtab + '-subtab-content');
            const subtabButton = document.getElementById(subtab + '-subtab');

            if (subtabContent && subtabButton) {
                subtabContent.classList.add('active');
                if (subtab === 'fd-pending-timetec') {
                    subtabButton.classList.add('pending-timetec-active');
                } else {
                    subtabButton.classList.add('active');
                }
                localStorage.setItem('resellerActiveFdSubTab', subtab);
            }
        }

        function switchFeHandoverSubTab(subtab) {
            // Only target subtabs within the FE handover content
            const feContent = document.getElementById('fe-handover-content');
            if (!feContent) return;

            feContent.querySelectorAll('.handover-subtab-content').forEach(content => {
                content.classList.remove('active');
            });
            feContent.querySelectorAll('.handover-subtab').forEach(button => {
                button.classList.remove('active');
                button.classList.remove('pending-timetec-active');
            });

            const subtabContent = document.getElementById(subtab + '-subtab-content');
            const subtabButton = document.getElementById(subtab + '-subtab');

            if (subtabContent && subtabButton) {
                subtabContent.classList.add('active');
                if (subtab === 'fe-pending-timetec') {
                    subtabButton.classList.add('pending-timetec-active');
                } else {
                    subtabButton.classList.add('active');
                }
                localStorage.setItem('resellerActiveFeSubTab', subtab);
            }
        }

        function switchFfHandoverSubTab(subtab) {
            const ffContent = document.getElementById('ff-handover-content');
            if (!ffContent) return;

            ffContent.querySelectorAll('.handover-subtab-content').forEach(content => {
                content.classList.remove('active');
            });
            ffContent.querySelectorAll('.handover-subtab').forEach(button => {
                button.classList.remove('active');
            });

            const subtabContent = document.getElementById(subtab + '-subtab-content');
            const subtabButton = document.getElementById(subtab + '-subtab');

            if (subtabContent && subtabButton) {
                subtabContent.classList.add('active');
                subtabButton.classList.add('active');
                localStorage.setItem('resellerActiveFfSubTab', subtab);
            }
        }

        function switchFgHandoverSubTab(subtab) {
            const fgContent = document.getElementById('fg-handover-content');
            if (!fgContent) return;

            fgContent.querySelectorAll('.handover-subtab-content').forEach(content => {
                content.classList.remove('active');
            });
            fgContent.querySelectorAll('.handover-subtab').forEach(button => {
                button.classList.remove('active');
                button.classList.remove('pending-timetec-active');
            });

            const subtabContent = document.getElementById(subtab + '-subtab-content');
            const subtabButton = document.getElementById(subtab + '-subtab');

            if (subtabContent && subtabButton) {
                subtabContent.classList.add('active');
                if (subtab === 'fg-pending-timetec') {
                    subtabButton.classList.add('pending-timetec-active');
                } else {
                    subtabButton.classList.add('active');
                }
                localStorage.setItem('resellerActiveFgSubTab', subtab);
            }
        }

        function switchCommissionSubTab(subtab) {
            const rcContent = document.getElementById('reseller-commission-content');
            if (!rcContent) return;

            rcContent.querySelectorAll('.handover-subtab-content').forEach(content => {
                content.classList.remove('active');
            });
            rcContent.querySelectorAll('.handover-subtab').forEach(button => {
                button.classList.remove('active');
            });

            const subtabContent = document.getElementById(subtab + '-subtab-content');
            const subtabButton = document.getElementById(subtab + '-subtab');

            if (subtabContent && subtabButton) {
                subtabContent.classList.add('active');
                subtabButton.classList.add('active');
                localStorage.setItem('resellerActiveCommissionSubTab', subtab);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            let activeTab = localStorage.getItem('resellerActiveTab') || 'customers';
            let activeHandoverSubTab = localStorage.getItem('resellerActiveHandoverSubTab') || 'pending-confirmation';
            let activeInquiryTab = localStorage.getItem('resellerActiveInquiryTab') || 'new';
            let activeDatabaseTab = localStorage.getItem('resellerActiveDatabaseTab') || 'new';
            let activeInstallationTab = localStorage.getItem('resellerActiveInstallationTab') || 'new';
            let activeFdSubTab = localStorage.getItem('resellerActiveFdSubTab') || 'fd-pending-confirmation';
            let activeFeSubTab = localStorage.getItem('resellerActiveFeSubTab') || 'fe-pending-confirmation';
            let activeFfSubTab = localStorage.getItem('resellerActiveFfSubTab') || 'ff-pending-confirmation';

            if (activeTab !== 'customers') {
                switchTab(activeTab);
            }

            // Restore handover subtab state
            if (activeTab === 'handover' && activeHandoverSubTab !== 'pending-confirmation') {
                switchHandoverSubTab(activeHandoverSubTab);
            }

            // Restore inquiry tab state
            if (activeTab === 'submit-inquiry' && activeInquiryTab !== 'new') {
                switchInquiryTab(activeInquiryTab);
            }

            // Restore database creation tab state
            if (activeTab === 'database-creation' && activeDatabaseTab !== 'new') {
                switchDatabaseCreationTab(activeDatabaseTab);
            }

            // Restore installation payment tab state
            if (activeTab === 'installation-payment' && activeInstallationTab !== 'new') {
                switchInstallationTab(activeInstallationTab);
            }

            // Restore FD handover subtab state
            if (activeTab === 'fd-handover' && activeFdSubTab !== 'fd-pending-confirmation') {
                switchFdHandoverSubTab(activeFdSubTab);
            }

            // Restore FE handover subtab state
            if (activeTab === 'fe-handover' && activeFeSubTab !== 'fe-pending-confirmation') {
                switchFeHandoverSubTab(activeFeSubTab);
            }

            // Restore FF handover subtab state
            if (activeTab === 'ff-handover' && activeFfSubTab !== 'ff-pending-confirmation') {
                switchFfHandoverSubTab(activeFfSubTab);
            }

            // Restore FG handover subtab state
            const activeFgSubTab = localStorage.getItem('resellerActiveFgSubTab') || 'fg-pending-confirmation';
            if (activeTab === 'fg-handover' && activeFgSubTab !== 'fg-pending-confirmation') {
                switchFgHandoverSubTab(activeFgSubTab);
            }

            // Restore commission subtab state
            const activeCommissionSubTab = localStorage.getItem('resellerActiveCommissionSubTab') || 'rc-pending-reseller';
            if (activeTab === 'reseller-commission' && activeCommissionSubTab !== 'rc-pending-reseller') {
                switchCommissionSubTab(activeCommissionSubTab);
            }

            // Smooth scroll to customers
            const customersLink = document.querySelector('a[href="#customers"]');
            if (customersLink) {
                customersLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    document.getElementById('customers').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                });
            }

            // Add loading animation for action cards
            const actionCards = document.querySelectorAll('.group');
            actionCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.6s cubic-bezier(0.4, 0, 0.2, 1)';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Add pulse animation to notification dot
            const statsCards = document.querySelectorAll('[class*="stats-card"]');
            statsCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                });

                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>

    <!-- Footer -->
    <footer class="page-footer">
        <p style="font-size: 13px; margin: 0;">
            &copy; {{ date('Y') }} TimeTec Cloud Sdn Bhd (200801031210). All Rights Reserved.
        </p>
    </footer>
</body>
</html>
