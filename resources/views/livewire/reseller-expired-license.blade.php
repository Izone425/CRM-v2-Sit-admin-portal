<div style="position: relative;">
    <style>
        .title-section {
            padding: 0;
            margin-bottom: 1.5rem;
        }

        .title-section h2 {
            color: #111827;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
        }

        .title-section p {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0.25rem 0 0 0;
        }

        .search-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: visible;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        }

        .custom-table th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 2px solid #e2e8f0;
        }

        .custom-table th button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-weight: 600;
            transition: color 0.2s;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .custom-table th button:hover {
            color: #667eea;
        }

        .custom-table tbody tr {
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .custom-table tbody tr:hover {
            background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
        }

        .custom-table tbody tr.row-expanded {
            background: linear-gradient(90deg, #f0f4ff 0%, #e8efff 100%);
        }

        .custom-table td {
            padding: 1rem 1.5rem;
            font-size: 0.875rem;
            color: #1f2937;
        }

        .company-name {
            font-weight: 600;
            color: #111827;
        }

        .date-cell {
            color: #6b7280;
        }

        .status-badge {
            display: inline-flex;
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 20px;
            letter-spacing: 0.025em;
        }

        .status-red {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .status-orange {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #9a3412;
            border: 1px solid #fdba74;
        }

        .status-yellow {
            background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
            color: #854d0e;
            border: 1px solid #fde047;
        }

        .status-green {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #86efac;
        }

        .expand-arrow {
            display: inline-block;
            transition: all 0.3s ease;
            opacity: 1;
            color: #d1d5db;
            font-size: 1.25rem;
        }

        .expand-arrow.rotated {
            transform: rotate(180deg);
        }

        .details-section {
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .invoice-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            margin-bottom: 1rem;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .invoice-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .invoice-header {
            font-weight: 600;
            color: #667eea;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
        }

        .product-table thead {
            background: #f9fafb;
        }

        .product-table th {
            padding: 0.625rem 1rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
        }

        .product-table td {
            padding: 0.625rem 1rem;
            font-size: 0.8125rem;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }

        .product-table tbody tr:hover {
            background: #f9fafb;
        }

        .empty-state {
            padding: 3rem 1.5rem;
            text-align: center;
            color: #9ca3af;
        }

        .sort-icon {
            width: 1rem;
            height: 1rem;
        }

        .tabs-container {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid #e5e7eb;
        }

        .tab-button {
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #6b7280;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            bottom: -2px;
        }

        .tab-button:hover {
            color: #667eea;
            background: #f9fafb;
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f0f4ff;
        }

        .tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.5rem;
            height: 1.5rem;
            padding: 0 0.5rem;
            margin-left: 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 12px;
            background: #e5e7eb;
            color: #6b7280;
        }

        .tab-button.active .tab-count {
            background: #667eea;
            color: white;
        }

        .export-button {
            margin-left: auto;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .export-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .export-button svg {
            width: 1.25rem;
            height: 1.25rem;
        }

        .pagination-wrapper {
            padding: 1.5rem;
            background: white;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: center;
        }

        .pagination-wrapper nav {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-wrapper a,
        .pagination-wrapper span {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }

        .pagination-wrapper a {
            color: #667eea;
            cursor: pointer;
        }

        .pagination-wrapper a:hover {
            background: #f0f4ff;
            border-color: #667eea;
        }

        .pagination-wrapper span.current {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* License Summary Styles */
        .license-summary-table {
            margin-bottom: 1.5rem;
        }

        .license-summary-table table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .license-summary-table th {
            padding: 12px 8px;
            text-align: center;
            border: 1px solid #e5e7eb;
            vertical-align: middle;
            font-weight: 600;
            font-size: 14px;
        }

        /* Module column widths - 3/4 of each pair */
        .module-col {
            width: 18.75% !important;
            text-align: center !important;
            padding-left: 12px !important;
        }

        /* Headcount column widths - 1/4 of each pair */
        .headcount-col {
            width: 6.25% !important;
            text-align: center !important;
            font-weight: bold !important;
        }

        /* Color themes for each module */
        .attendance-module {
            background-color: rgba(34, 197, 94, 0.1) !important;
            color: rgba(34, 197, 94, 1) !important;
        }
        .attendance-count {
            background-color: rgba(34, 197, 94, 1) !important;
            color: white !important;
        }

        .leave-module {
            background-color: rgba(37, 99, 235, 0.1) !important;
            color: rgba(37, 99, 235, 1) !important;
        }
        .leave-count {
            background-color: rgba(37, 99, 235, 1) !important;
            color: white !important;
        }

        .claim-module {
            background-color: rgba(124, 58, 237, 0.1) !important;
            color: rgba(124, 58, 237, 1) !important;
        }
        .claim-count {
            background-color: rgba(124, 58, 237, 1) !important;
            color: white !important;
        }

        .payroll-module {
            background-color: rgba(249, 115, 22, 0.1) !important;
            color: rgba(249, 115, 22, 1) !important;
        }
        .payroll-count {
            background-color: rgba(249, 115, 22, 1) !important;
            color: white !important;
        }

        /* Renewal Badge */
        .renewal-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 20px;
            letter-spacing: 0.025em;
            white-space: nowrap;
        }

        .renewal-done {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #86efac;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .renewal-done:hover {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            box-shadow: 0 2px 8px rgba(22, 101, 52, 0.2);
            transform: translateY(-1px);
        }

        .renewal-done-expiring {
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            color: #9a3412;
            border: 1px solid #fdba74;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .renewal-done-expiring:hover {
            background: linear-gradient(135deg, #ffedd5 0%, #fed7aa 100%);
            box-shadow: 0 2px 8px rgba(154, 52, 18, 0.2);
            transform: translateY(-1px);
        }

        .renewal-pending {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            color: #9ca3af;
            border: 1px solid #e5e7eb;
        }

        /* Reminder list buttons */
        .reminder-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }
        .reminder-btn i { font-size: 11px; }
        .reminder-btn-add {
            background: #ffffff;
            color: #2563eb;
            border-color: #bfdbfe;
        }
        .reminder-btn-add:hover {
            background: #eff6ff;
            border-color: #93c5fd;
        }
        .reminder-btn-pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border-color: #fcd34d;
        }
        .reminder-btn-pending:hover {
            background: linear-gradient(135deg, #fde68a 0%, #fbbf24 100%);
        }
        .reminder-btn-on {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border-color: #fcd34d;
        }
        .reminder-btn-on:hover {
            background: linear-gradient(135deg, #fde68a 0%, #fbbf24 100%);
        }
        .reminder-btn-done {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border-color: #6ee7b7;
        }
        .reminder-btn-done:hover {
            background: linear-gradient(135deg, #a7f3d0 0%, #6ee7b7 100%);
        }

        /* Renewal Badge Tooltip */
        .renewal-badge {
            position: relative;
        }

        .renewal-badge .badge-tooltip {
            visibility: hidden;
            opacity: 0;
            position: absolute;
            bottom: calc(100% + 8px);
            left: 50%;
            transform: translateX(-50%);
            background: #000;
            color: #fff;
            font-size: 0.7rem;
            font-weight: 400;
            padding: 6px 10px;
            border-radius: 6px;
            white-space: nowrap;
            z-index: 10;
            transition: opacity 0.2s ease, visibility 0.2s ease;
            pointer-events: none;
        }

        .renewal-badge .badge-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: #000 transparent transparent transparent;
        }

        .renewal-badge:hover .badge-tooltip {
            visibility: visible;
            opacity: 1;
        }

        /* Auto-show tooltip: pop out for 2s every 5s cycle (infinite) */
        @keyframes tooltipPulse {
            0% { visibility: visible; opacity: 1; }
            40% { visibility: visible; opacity: 1; }
            50% { visibility: hidden; opacity: 0; }
            100% { visibility: hidden; opacity: 0; }
        }

        [data-first-renewed] .badge-tooltip {
            animation: tooltipPulse 5s ease-in-out infinite;
        }

        /* Hover overrides animation */
        [data-first-renewed]:hover .badge-tooltip {
            visibility: visible !important;
            opacity: 1 !important;
            animation: none !important;
        }

        /* Renewal Modal */
        .renewal-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: renewalFadeIn 0.2s ease-out;
        }

        @keyframes renewalFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes renewalSlideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .renewal-modal-content {
            background: white;
            border-radius: 12px;
            max-width: 850px;
            width: 90%;
            max-height: 85vh;
            overflow-y: auto;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            animation: renewalSlideUp 0.3s ease-out;
        }

        .renewal-modal-header {
            padding: 1.25rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px 12px 0 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .renewal-modal-header h3 {
            margin: 0;
            color: white;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .renewal-modal-header .close-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.125rem;
            transition: background 0.2s;
        }

        .renewal-modal-header .close-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .renewal-modal-body {
            padding: 1.5rem;
        }

        /* Filter Dropdown */
        .filter-header {
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-icon-btn {
            position: relative;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            color: #64748b;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
        }

        .filter-icon-btn:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .filter-icon-btn.active {
            color: #667eea;
            background: rgba(102, 126, 234, 0.15);
        }

        .filter-icon-btn svg {
            width: 1rem;
            height: 1rem;
        }

        .filter-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.125rem;
            height: 1.125rem;
            padding: 0 0.25rem;
            font-size: 0.625rem;
            font-weight: 700;
            border-radius: 50%;
            background: #667eea;
            color: white;
            position: absolute;
            top: -4px;
            right: -4px;
        }

        .filter-dropdown {
            position: absolute;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            min-width: 220px;
            z-index: 9999;
            animation: filterDropdownIn 0.15s ease-out;
        }

        @keyframes filterDropdownIn {
            from { opacity: 0; transform: translateY(-4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filter-dropdown-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .filter-dropdown-header span {
            font-size: 0.75rem;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .filter-clear-btn {
            font-size: 0.7rem;
            color: #667eea;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            padding: 0.125rem 0.375rem;
            border-radius: 4px;
            transition: all 0.2s;
        }

        .filter-clear-btn:hover {
            background: rgba(102, 126, 234, 0.1);
        }

        .filter-dropdown-body {
            padding: 0.5rem;
        }

        .filter-option {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.5rem 0.625rem;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s;
            font-size: 0.8125rem;
            color: #374151;
        }

        .filter-option:hover {
            background: #f9fafb;
        }

        .filter-checkbox {
            width: 1rem;
            height: 1rem;
            border: 2px solid #d1d5db;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.15s;
            flex-shrink: 0;
        }

        .filter-checkbox.checked {
            background: #667eea;
            border-color: #667eea;
        }

        .filter-checkbox.checked svg {
            display: block;
        }

        .filter-checkbox svg {
            display: none;
            width: 0.625rem;
            height: 0.625rem;
            color: white;
        }

        .filter-option-label {
            display: flex;
            align-items: center;
            gap: 0.375rem;
        }

        .filter-option-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .filter-dot-done { background: #22c55e; }
        .filter-dot-expiring { background: #f97316; }
        .filter-dot-pending { background: #9ca3af; }

        /* Product row background colors */
        .product-row-ta {
            background-color: rgba(34, 197, 94, 0.1) !important;
        }
        .product-row-leave {
            background-color: rgba(37, 99, 235, 0.1) !important;
        }
        .product-row-claim {
            background-color: rgba(124, 58, 237, 0.1) !important;
        }
        .product-row-payroll {
            background-color: rgba(249, 115, 22, 0.1) !important;
        }
    </style>

    <!-- Tabs -->
    <div class="tabs-container">
        <button
            wire:click="switchTab('90days')"
            class="tab-button {{ $activeTab === '90days' ? 'active' : '' }}">
            Expired within 90 Days
            <span class="tab-count">{{ $expiredWithin90DaysCount ?? 0 }}</span>
        </button>
        <button
            wire:click="switchTab('all')"
            class="tab-button {{ $activeTab === 'all' ? 'active' : '' }}">
            All Expired Licenses
            <span class="tab-count">{{ $allExpiredCount ?? 0 }}</span>
        </button>
        <a href="{{ route('reseller.expired-license.export', ['tab' => $activeTab, 'search' => $search, 'renewal_status_filter' => $renewalStatusFilter]) }}" class="export-button">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Export Excel
        </a>
    </div>

    <!-- Search Input -->
    <div class="search-wrapper">
        <div class="search-icon">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
        <input
            type="text"
            wire:model.live="search"
            placeholder="Search by company name"
            class="search-input"
        >
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Company Name</th>
                    <th>
                        <button wire:click="sortBy('f_expiry_date')">
                            Expiry Date
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'f_expiry_date')
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
                        <button wire:click="sortBy('days_until_expiry')">
                            Days Until Expiry
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'days_until_expiry')
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
                    <th>Renewal Progress</th>
                    <th style="width: 100px;">
                        <div style="display: flex; justify-content: center;">
                            <button id="filter-toggle-btn" onclick="event.stopPropagation(); window.toggleFilterDropdown(this);" class="filter-icon-btn {{ !empty($renewalStatusFilter) ? 'active' : '' }}">
                                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                                </svg>
                                @if(!empty($renewalStatusFilter))
                                    <span class="filter-badge">{{ count($renewalStatusFilter) }}</span>
                                @endif
                            </button>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                @php $firstRenewedFound = false; @endphp
                @forelse($companies as $company)
                    @php
                        $isFirstRenewed = false;
                        if (!$firstRenewedFound && in_array($company->renewal_status, ['done', 'done_expiring'])) {
                            $isFirstRenewed = true;
                            $firstRenewedFound = true;
                        }
                    @endphp
                    <tr wire:click="toggleExpand('{{ $company->f_id }}')"
                        wire:key="company-{{ $company->f_id }}"
                        class="{{ $expandedCompany == $company->f_id ? 'row-expanded' : '' }}"
                        style="cursor: pointer;">
                        <td>{{ $loop->iteration }}</td>
                        <td class="company-name">
                            {{ strtoupper($company->f_company_name) }}
                        </td>
                        <td class="date-cell">
                            {{ date('d M Y', strtotime($company->f_expiry_date)) }}
                        </td>
                        <td>
                            <span class="status-badge
                                @if($company->days_until_expiry == 0) status-red
                                @elseif($company->days_until_expiry < 7) status-orange
                                @elseif($company->days_until_expiry < 14) status-yellow
                                @else status-green
                                @endif">
                                {{ $company->days_until_expiry }} days
                            </span>
                        </td>
                        <td>
                            @php
                                $done = $company->done_invoices ?? 0;
                                $total = $company->total_invoices ?? 0;
                                $allDone = $total > 0 && $done >= $total;
                            @endphp
                            <span class="status-badge {{ $allDone ? 'status-green' : ($done > 0 ? 'status-yellow' : 'status-red') }}">
                                {{ $done }}/{{ $total }} done renewal
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <span class="expand-arrow {{ $expandedCompany == $company->f_id ? 'rotated' : '' }}">
                                <i class="fas fa-chevron-down"></i>
                            </span>
                        </td>
                    </tr>

                    @if($expandedCompany == $company->f_id)
                        <tr wire:key="details-{{ $company->f_id }}">
                            <td colspan="6" style="padding: 0;">
                                <div class="details-section">
                                    @if(!empty($invoiceDetails))
                                        <!-- License Summary Table -->
                                        @if(isset($invoiceDetails['_summary']))
                                            <div class="license-summary-table">
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th class="module-col attendance-module">ATTENDANCE</th>
                                                            <th class="headcount-col attendance-count">{{ $invoiceDetails['_summary']['attendance'] }}</th>
                                                            <th class="module-col leave-module">LEAVE</th>
                                                            <th class="headcount-col leave-count">{{ $invoiceDetails['_summary']['leave'] }}</th>
                                                            <th class="module-col claim-module">CLAIM</th>
                                                            <th class="headcount-col claim-count">{{ $invoiceDetails['_summary']['claim'] }}</th>
                                                            <th class="module-col payroll-module">PAYROLL</th>
                                                            <th class="headcount-col payroll-count">{{ $invoiceDetails['_summary']['payroll'] }}</th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        @endif

                                        @foreach($invoiceDetails as $invoiceNo => $invoice)
                                            @if($invoiceNo === '_summary') @continue @endif
                                            <div class="invoice-card">
                                                <div class="invoice-header" style="display: flex; align-items: center; justify-content: space-between; gap: 1rem;">
                                                    <span>Invoice: {{ $invoiceNo }}</span>
                                                    @if($invoice['is_done'] ?? false)
                                                        <button type="button" class="reminder-btn reminder-btn-done"
                                                            wire:click.stop="unmarkRenewalDone('{{ $company->f_id }}', '{{ $invoiceNo }}')"
                                                            title="Click to mark this invoice as Pending again.">
                                                            <i class="fas fa-check-circle"></i> Done Renewal
                                                        </button>
                                                    @else
                                                        <button type="button" class="reminder-btn reminder-btn-pending"
                                                            wire:click.stop="markRenewalDone('{{ $company->f_id }}', '{{ $invoiceNo }}')"
                                                            title="Click when this invoice is renewed.">
                                                            <i class="fas fa-clock"></i> Pending
                                                        </button>
                                                    @endif
                                                </div>

                                                <table class="product-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Product Name</th>
                                                            <th style="width: 20%;">Total User</th>
                                                            <th style="width: 12%;">Cycle</th>
                                                            <th style="width: 20%;">Start Date</th>
                                                            <th style="width: 20%;">Expiry Date</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($invoice['products'] as $product)
                                                            @php
                                                                $productClass = '';
                                                                if (strpos($product['f_name'], 'TimeTec TA') !== false) {
                                                                    $productClass = 'product-row-ta';
                                                                } elseif (strpos($product['f_name'], 'TimeTec Leave') !== false) {
                                                                    $productClass = 'product-row-leave';
                                                                } elseif (strpos($product['f_name'], 'TimeTec Claim') !== false) {
                                                                    $productClass = 'product-row-claim';
                                                                } elseif (strpos($product['f_name'], 'TimeTec Payroll') !== false) {
                                                                    $productClass = 'product-row-payroll';
                                                                }
                                                            @endphp
                                                            <tr class="{{ $productClass }}">
                                                                <td>{{ $product['f_name'] }}</td>
                                                                <td>{{ $product['f_total_user'] }}</td>
                                                                <td>{{ $product['billing_cycle'] }}</td>
                                                                <td>{{ date('Y-m-d', strtotime($product['f_start_date'])) }}</td>
                                                                <td>{{ date('Y-m-d', strtotime($product['f_expiry_date'])) }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endforeach
                                    @else
                                        <p style="color: #6b7280; font-size: 0.875rem;">No invoice details available.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="6" class="empty-state">
                            No licenses found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Filter Dropdown (outside table to avoid overflow clipping) -->
    <div id="renewal-filter-dropdown" class="filter-dropdown" style="display: none;" onclick="event.stopPropagation();">
        <div class="filter-dropdown-header">
            <span>Filter by Status</span>
            @if(!empty($renewalStatusFilter))
                <button onclick="event.stopPropagation(); @this.call('clearRenewalStatusFilter');" class="filter-clear-btn">Clear</button>
            @endif
        </div>
        <div class="filter-dropdown-body">
            <div class="filter-option" onclick="event.stopPropagation(); @this.call('toggleRenewalStatusFilter', 'done');">
                <div class="filter-checkbox {{ in_array('done', $renewalStatusFilter) ? 'checked' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="filter-option-label">
                    <span class="filter-option-dot filter-dot-done"></span>
                    Done Renewal
                </div>
            </div>
            <div class="filter-option" onclick="event.stopPropagation(); @this.call('toggleRenewalStatusFilter', 'done_expiring');">
                <div class="filter-checkbox {{ in_array('done_expiring', $renewalStatusFilter) ? 'checked' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="filter-option-label">
                    <span class="filter-option-dot filter-dot-expiring"></span>
                    Renewed (Expiring Soon)
                </div>
            </div>
            <div class="filter-option" onclick="event.stopPropagation(); @this.call('toggleRenewalStatusFilter', 'pending');">
                <div class="filter-checkbox {{ in_array('pending', $renewalStatusFilter) ? 'checked' : '' }}">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="filter-option-label">
                    <span class="filter-option-dot filter-dot-pending"></span>
                    Pending
                </div>
            </div>
        </div>
    </div>

    <!-- Renewal Modal -->
    @if($showRenewalModal && !empty($renewalDetails))
        <div class="renewal-modal-overlay" wire:click.self="closeRenewalModal">
            <div class="renewal-modal-content">
                <div class="renewal-modal-header">
                    <h3>Renewed Licenses - {{ strtoupper($renewalCompanyName) }}</h3>
                    <button class="close-btn" wire:click="closeRenewalModal">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="renewal-modal-body">
                    <!-- License Summary Table -->
                    @if(isset($renewalDetails['_summary']))
                        <div class="license-summary-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="module-col attendance-module">ATTENDANCE</th>
                                        <th class="headcount-col attendance-count">{{ $renewalDetails['_summary']['attendance'] }}</th>
                                        <th class="module-col leave-module">LEAVE</th>
                                        <th class="headcount-col leave-count">{{ $renewalDetails['_summary']['leave'] }}</th>
                                        <th class="module-col claim-module">CLAIM</th>
                                        <th class="headcount-col claim-count">{{ $renewalDetails['_summary']['claim'] }}</th>
                                        <th class="module-col payroll-module">PAYROLL</th>
                                        <th class="headcount-col payroll-count">{{ $renewalDetails['_summary']['payroll'] }}</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    @endif

                    @foreach($renewalDetails as $invoiceNo => $invoice)
                        @if($invoiceNo === '_summary') @continue @endif
                        <div class="invoice-card">
                            <div class="invoice-header">
                                Invoice: {{ $invoiceNo }}
                            </div>

                            <table class="product-table">
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th style="width: 20%;">Total User</th>
                                        <th style="width: 12%;">Cycle</th>
                                        <th style="width: 20%;">Start Date</th>
                                        <th style="width: 20%;">Expiry Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($invoice['products'] as $product)
                                        @php
                                            $productClass = '';
                                            if (strpos($product['f_name'], 'TimeTec TA') !== false) {
                                                $productClass = 'product-row-ta';
                                            } elseif (strpos($product['f_name'], 'TimeTec Leave') !== false) {
                                                $productClass = 'product-row-leave';
                                            } elseif (strpos($product['f_name'], 'TimeTec Claim') !== false) {
                                                $productClass = 'product-row-claim';
                                            } elseif (strpos($product['f_name'], 'TimeTec Payroll') !== false) {
                                                $productClass = 'product-row-payroll';
                                            }
                                        @endphp
                                        <tr class="{{ $productClass }}">
                                            <td>{{ $product['f_name'] }}</td>
                                            <td>{{ $product['f_total_user'] }}</td>
                                            <td>{{ $product['billing_cycle'] }}</td>
                                            <td>{{ date('Y-m-d', strtotime($product['f_start_date'])) }}</td>
                                            <td>{{ date('Y-m-d', strtotime($product['f_expiry_date'])) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div wire:ignore>
        <script>
            (function() {
                window._filterOpen = false;

                window.toggleFilterDropdown = function(btn) {
                    var dd = document.getElementById('renewal-filter-dropdown');
                    if (!dd) return;

                    if (dd.style.display === 'none' || dd.style.display === '') {
                        var rootDiv = dd.parentElement;
                        var rootRect = rootDiv.getBoundingClientRect();
                        var btnRect = btn.getBoundingClientRect();
                        dd.style.top = (btnRect.bottom - rootRect.top + 4) + 'px';
                        dd.style.right = (rootRect.right - btnRect.right) + 'px';
                        dd.style.display = 'block';
                        window._filterOpen = true;
                    } else {
                        dd.style.display = 'none';
                        window._filterOpen = false;
                    }
                };

                document.addEventListener('click', function(e) {
                    var dd = document.getElementById('renewal-filter-dropdown');
                    if (dd && !dd.contains(e.target) && !e.target.closest('.filter-icon-btn')) {
                        dd.style.display = 'none';
                        window._filterOpen = false;
                    }
                });

                Livewire.hook('morph.updated', ({el}) => {
                    if (window._filterOpen) {
                        var dd = document.getElementById('renewal-filter-dropdown');
                        var btn = document.getElementById('filter-toggle-btn');
                        if (dd && btn) {
                            var rootDiv = dd.parentElement;
                            var rootRect = rootDiv.getBoundingClientRect();
                            var btnRect = btn.getBoundingClientRect();
                            dd.style.top = (btnRect.bottom - rootRect.top + 4) + 'px';
                            dd.style.right = (rootRect.right - btnRect.right) + 'px';
                            dd.style.display = 'block';
                        }
                    }
                });
            })();
        </script>
    </div>

    {{-- Reminder action toast --}}
    <div
        x-data="{
            show: false,
            type: 'success',
            message: '',
            timer: null,
            display(detail) {
                this.type = detail.type || 'success';
                this.message = detail.message || '';
                this.show = true;
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.show = false, 3500);
            }
        }"
        @reminder-toast.window="display($event.detail)"
        x-show="show"
        x-transition:enter="reminder-toast-enter"
        x-transition:leave="reminder-toast-leave"
        x-cloak
        class="reminder-toast"
        :data-type="type"
    >
        <span class="reminder-toast-icon" x-text="type === 'success' ? '✓' : (type === 'info' ? 'i' : '!')"></span>
        <span class="reminder-toast-msg" x-text="message"></span>
    </div>

    <style>
        .reminder-toast {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            min-width: 240px;
            max-width: 420px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #ffffff;
            background: #2563eb;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .reminder-toast[data-type="success"] {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
        }
        .reminder-toast[data-type="info"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .reminder-toast[data-type="error"] {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
        }
        .reminder-toast-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.2);
            font-weight: 700;
            font-size: 13px;
            flex-shrink: 0;
        }
        @keyframes reminderToastIn {
            from { opacity: 0; transform: translateX(20px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes reminderToastOut {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(20px); }
        }
        .reminder-toast-enter { animation: reminderToastIn 0.2s ease-out; }
        .reminder-toast-leave { animation: reminderToastOut 0.2s ease-in; }
    </style>
</div>
