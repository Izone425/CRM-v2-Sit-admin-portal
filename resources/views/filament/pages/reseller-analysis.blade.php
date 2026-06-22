<x-filament-panels::page>
    <style>
        .reseller-tabs {
            display: flex;
            margin-bottom: 12px;
            border-bottom: 1px solid #e5e7eb;
            position: relative;
        }

        .reseller-tab {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 500;
            color: #6b7280;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .reseller-tab.active {
            color: #2563eb;
            border-bottom-color: #2563eb;
            font-weight: 600;
        }

        .reseller-tab:hover:not(.active) {
            color: #4b5563;
            border-bottom-color: #d1d5db;
        }

        .reseller-tab-content {
            display: none;
        }

        .reseller-tab-content.active {
            display: block;
        }

        .reseller-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .reseller-header h3 {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }

        .reseller-header .date-range {
            font-size: 13px;
            color: #6b7280;
            background: #f3f4f6;
            padding: 4px 12px;
            border-radius: 6px;
        }

        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            background: #059669;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .export-btn:hover {
            background: #047857;
        }

        .export-btn svg {
            width: 16px;
            height: 16px;
        }

        .reseller-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .reseller-table thead th {
            background: #1a56db;
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #ffffff;
            border-bottom: none;
        }

        .reseller-table thead th:nth-child(1) {
            width: 60px;
            text-align: center;
        }

        .reseller-table thead th:nth-child(2) {
            text-align: center;
            width: 90px;
        }

        .reseller-table thead th:nth-child(4) {
            text-align: center;
            width: 180px;
        }

        .reseller-table thead th:nth-child(5) {
            text-align: center;
            width: 80px;
        }

        .reseller-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background 0.15s;
        }

        .reseller-table tbody tr:hover {
            background: #f9fafb;
        }

        .reseller-table tbody td {
            padding: 10px 16px;
            font-size: 14px;
            color: #1f2937;
        }

        .reseller-table tbody td:nth-child(1) {
            text-align: center;
            font-weight: 600;
            color: #6b7280;
        }

        .reseller-table tbody td:nth-child(2) {
            text-align: center;
        }

        .reseller-table tbody td:nth-child(4) {
            text-align: center;
        }

        /* NOTE: positional :nth-child selectors. If you add or remove a column,
           renumber every rule below and above; this is exactly how the previous
           "empty band on the right" bug crept in. */
        .reseller-table thead th:nth-child(6),
        .reseller-table tbody td:nth-child(6) {
            width: 1%;
            white-space: nowrap;
            text-align: right;
        }

        .reseller-table tbody td:nth-child(5) {
            text-align: center;
        }

        /* Year-month group header (Tier 1) — mirrors Termination Analysis's collapsible rows. */
        .reseller-chevron { transition: transform 0.2s ease; }
        .reseller-chevron.open { transform: rotate(90deg); }
        /* Reseller row chevron (Tier 2 → inline clients) + tinted clients-row band. */
        .reseller-row-chevron { transition: transform 0.2s ease; }
        .reseller-row-chevron.open { transform: rotate(90deg); }
        .reseller-table tbody tr.reseller-clients-row > td {
            /* padding-left aligns the inline client list with the reseller-name TEXT column 3 text-start position.
               Sum of: #-col (60) + Account-col (90) + cell padding-left (16) + chevron width (14) + chevron margin-right (8) = 188.
               If any of those four fixed widths change, recompute this constant. */
            padding-left: 188px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .reseller-table tbody td.reseller-name { user-select: none; }
        .reseller-table tbody tr.reseller-group-row {
            background: #f8fafc;
            cursor: pointer;
            border-bottom: 1px solid #e5e7eb;
        }
        .reseller-table tbody tr.reseller-group-row:hover {
            background: #f1f5f9;
        }
        .reseller-table tbody tr.reseller-group-row > td {
            padding: 14px 20px;
        }

        .account-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
        }

        .account-icon.has-account {
            background: #16a34a;
        }

        .account-icon.no-account {
            background: #dc2626;
        }

        .account-filter {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-left: 16px;
        }

        .account-filter-btn {
            padding: 5px 14px;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .account-filter-btn:hover {
            background: #f3f4f6;
        }

        .account-filter-btn.active {
            background: #2563eb;
            color: white;
            border-color: #2563eb;
        }

        .reseller-name {
            font-weight: 600;
            text-transform: uppercase;
        }

        .end-user-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 80px;
            padding: 6px 16px;
            background: #dbeafe;
            color: #1d4ed8;
            font-weight: 700;
            font-size: 14px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .end-user-count:hover {
            background: #1d4ed8;
            color: white;
            transform: scale(1.05);
        }

        .total-row td {
            font-weight: 700 !important;
            background: #f3f4f6 !important;
            border-top: 2px solid #d1d5db;
        }

        .total-row .end-user-count {
            background: #1d4ed8;
            color: white;
        }

        /* Drawer / Slide-over */
        .drawer-overlay {
            position: fixed;
            inset: 0;
            z-index: 200;
            display: flex;
            justify-content: flex-end;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(2px);
        }

        .drawer-panel {
            width: 100%;
            max-width: 520px;
            height: 100%;
            background: white;
            box-shadow: -4px 0 20px rgba(0,0,0,0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .drawer-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .drawer-header h2 {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }

        .drawer-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .drawer-close {
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            background: none;
            border: none;
            line-height: 1;
        }

        .drawer-close:hover {
            color: #111827;
        }

        .drawer-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px 20px;
        }

        .client-card {
            padding: 12px 16px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 8px;
            transition: border-color 0.15s;
        }

        .client-card:hover {
            border-color: #93c5fd;
        }

        .client-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 6px;
        }

        .client-card-name {
            font-weight: 600;
            font-size: 13px;
            color: #111827;
            flex: 1;
        }

        .client-card-name a {
            color: #2563eb;
            text-decoration: none;
        }

        .client-card-name a:hover {
            text-decoration: underline;
        }

        .client-card-users {
            font-weight: 700;
            font-size: 13px;
            color: #1d4ed8;
            background: #dbeafe;
            padding: 2px 10px;
            border-radius: 12px;
            margin-left: 8px;
            white-space: nowrap;
        }

        .client-card-meta {
            display: flex;
            gap: 12px;
            font-size: 12px;
            color: #6b7280;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-new { background: #dbeafe; color: #1d4ed8; }
        .status-pending_confirmation { background: #fef3c7; color: #92400e; }
        .status-pending_payment { background: #fee2e2; color: #991b1b; }
        .status-completed_renewal { background: #d1fae5; color: #065f46; }
        .status-completed_reseller_portal { background: #d1fae5; color: #065f46; }
        .status-no_record { background: #f3f4f6; color: #6b7280; }
        .status-terminated { background: #fecaca; color: #7f1d1d; }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }

        .loading-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #e5e7eb;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .drawer-export-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 6px 12px;
            background: #059669;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .drawer-export-btn:hover {
            background: #047857;
        }

        .drawer-export-btn svg {
            width: 14px;
            height: 14px;
        }

        .drawer-export-btn.pricing-export-btn {
            background: #7c3aed;
        }

        .drawer-export-btn.pricing-export-btn:hover {
            background: #6d28d9;
        }

        .export-btn.pricing-summary-btn {
            background: #7c3aed;
        }

        .export-btn.pricing-summary-btn:hover {
            background: #6d28d9;
        }

        .export-dropdown {
            position: relative;
            display: inline-block;
        }

        .export-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 4px;
            min-width: 250px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            z-index: 50;
            padding: 4px;
        }

        .export-dropdown-item {
            display: flex;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 8px 12px;
            background: transparent;
            border: none;
            border-radius: 4px;
            color: #374151;
            font-size: 13px;
            font-weight: 500;
            text-align: left;
            cursor: pointer;
            transition: background 0.15s;
        }

        .export-dropdown-item:hover {
            background: #f3f4f6;
        }

        .export-dropdown-item:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .export-dropdown-item svg {
            width: 16px;
            height: 16px;
            flex-shrink: 0;
            color: #6b7280;
        }

        /* Reseller Commission tab */
        .commission-layout {
            display: flex;
            gap: 16px;
            min-height: 600px;
            position: relative;
        }
        .commission-sidebar {
            width: 290px;
            flex-shrink: 0;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            height: fit-content;
        }
        .commission-toggle {
            display: flex;
            background: #f3f4f6;
            border-radius: 6px;
            padding: 3px;
            margin-bottom: 12px;
        }
        .commission-toggle-btn {
            flex: 1;
            padding: 6px 10px;
            border: none;
            background: transparent;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.15s;
        }
        .commission-toggle-btn.active {
            background: #ffffff;
            color: #1f2937;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
        }
        .commission-section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #6b7280;
            padding: 8px 6px 6px;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 6px;
        }
        .analysis-section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
            padding: 14px 16px;
            margin-bottom: 8px;
            background: transparent;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #64748b;
            text-align: left;
            transition: all 0.3s ease;
        }
        .analysis-section-header:hover {
            background: #f1f5f9;
            color: #667eea;
            transform: translateX(4px);
        }
        .analysis-section-header.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .commission-bucket {
            border-bottom: 1px solid #f3f4f6;
        }
        .commission-bucket:last-child { border-bottom: none; }
        .commission-bucket-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 8px;
            background: transparent;
            border: none;
            width: 100%;
            cursor: pointer;
            font-size: 13px;
            color: #1f2937;
            font-weight: 500;
            transition: background 0.15s;
            text-align: left;
        }
        .commission-bucket-header:hover { background: #f9fafb; }
        .commission-bucket-header .label-group { display: flex; align-items: center; gap: 8px; }
        .commission-bucket-header .chev { transition: transform 0.2s; color: #9ca3af; }
        .commission-bucket-header .chev.open { transform: rotate(90deg); }
        .commission-bucket-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            padding: 1px 6px;
            font-size: 11px;
            font-weight: 600;
            color: #1d4ed8;
            background: #dbeafe;
            border-radius: 9999px;
        }
        .commission-bucket-list {
            list-style: none;
            margin: 0;
            padding: 4px 0 8px 16px;
        }
        .commission-bucket-list li {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            padding: 4px 6px;
            font-size: 12px;
            color: #4b5563;
            border-radius: 4px;
        }
        .commission-bucket-list li:hover { background: #f9fafb; }
        .commission-bucket-list .reseller-rate { color: #6b7280; font-variant-numeric: tabular-nums; }
        .commission-empty {
            padding: 8px;
            font-size: 12px;
            color: #9ca3af;
            font-style: italic;
        }
        .commission-main {
            flex: 1;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }
        .commission-main-title {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            margin: 0 0 4px;
        }
        .commission-main-subtitle {
            font-size: 13px;
            color: #6b7280;
            margin: 0 0 16px;
        }
        .commission-chart-wrap {
            position: relative;
            height: 460px;
        }

        .gross-view-btn {
            padding: 6px 14px;
            border: none;
            background: transparent;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            color: #4b5563;
            cursor: pointer;
            transition: all 0.15s;
        }
        .gross-view-btn.active {
            background: #ffffff;
            color: #111827;
            box-shadow: 0 1px 2px rgba(0,0,0,0.08);
        }

    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <div x-data="{
            activeTab: 'MYR',
            accountFilter: 'all',
            switchTab(tab) {
                this.activeTab = tab;
                localStorage.setItem('preferredResellerTab', tab);
            },
            initTabs() {
                const savedTab = localStorage.getItem('preferredResellerTab');
                if (savedTab) {
                    this.activeTab = savedTab;
                }
            }
        }" x-init="initTabs()">

        <!-- Tab Navigation -->
        <div class="reseller-tabs">
            <div @click="switchTab('MYR')"
                :class="{'reseller-tab': true, 'active': activeTab === 'MYR'}">
                Reseller MYR
            </div>
            <div @click="switchTab('USD')"
                :class="{'reseller-tab': true, 'active': activeTab === 'USD'}">
                Reseller USD
            </div>
            <div @click="switchTab('ANALYSIS')"
                :class="{'reseller-tab': true, 'active': activeTab === 'ANALYSIS'}">
                Reseller Analysis
            </div>
        </div>

        <!-- MYR Tab Content -->
        <div :class="{'reseller-tab-content': true, 'active': activeTab === 'MYR'}">
            @php
                $myrData = $this->myrData;
                $myrTotalEndUsers = array_sum(array_column($myrData, 'total_end_users'));
                [$myrStartDate, $myrEndDate] = $this->getDateRange();
                $myrRegistered = count(array_filter($myrData, fn($r) => $r['has_account'] ?? false));
                $myrUnregistered = count($myrData) - $myrRegistered;
            @endphp

            <div class="reseller-header">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <div class="account-filter">
                        <button @click="accountFilter = 'all'" :class="{'account-filter-btn': true, 'active': accountFilter === 'all'}">All ({{ count($myrData) }})</button>
                        <button @click="accountFilter = 'registered'" :class="{'account-filter-btn': true, 'active': accountFilter === 'registered'}">Registered ({{ $myrRegistered }})</button>
                        <button @click="accountFilter = 'unregistered'" :class="{'account-filter-btn': true, 'active': accountFilter === 'unregistered'}">Unregistered ({{ $myrUnregistered }})</button>
                    </div>
                </div>
                {{-- Inline date-range mode selector — replaces the Filter dropdown --}}
                <div style="display:flex; align-items:center; gap:8px; margin-left:auto; margin-right:8px;">
                    <div style="display:flex; align-items:center; gap:4px; background:#f1f5f9; border-radius:8px; padding:2px;">
                        @foreach (['all' => 'All', 'year' => 'Year', 'month' => 'Month', 'range' => 'Range'] as $mode => $label)
                            <button wire:click="$set('filterMode', '{{ $mode }}')"
                                style="padding:6px 12px; border-radius:6px; border:none; font-size:0.75rem; font-weight:600; cursor:pointer;
                                {{ $filterMode === $mode ? 'background:#1a56db; color:#fff;' : 'background:transparent; color:#64748b;' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    @if ($filterMode === 'year')
                        <select wire:model.change="selectedYear" style="padding:6px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:0.75rem; background:#fff;">
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    @elseif ($filterMode === 'month')
                        <input type="month" wire:model.change="selectedMonthYear"
                            style="padding:6px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:0.75rem; background:#fff;">
                    @elseif ($filterMode === 'range')
                        <div style="display:flex; align-items:center; gap:4px;">
                            <input type="date" wire:model.change="startDate" style="padding:6px 8px; border:1px solid #d1d5db; border-radius:8px; font-size:0.7rem;">
                            <span style="color:#9ca3af; font-size:0.7rem;">to</span>
                            <input type="date" wire:model.change="endDate" style="padding:6px 8px; border:1px solid #d1d5db; border-radius:8px; font-size:0.7rem;">
                        </div>
                    @endif
                </div>
                <span style="display:inline-flex; align-items:center; gap:6px; font-size:0.8rem; color:#1a56db; font-weight:700; white-space:nowrap; background:#eff6ff; border:1px solid #bfdbfe; padding:5px 10px; border-radius:8px; margin-right:8px;">
                    <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ \Carbon\Carbon::parse($myrStartDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($myrEndDate)->format('d M Y') }}
                </span>
                <button wire:click="generateForecastCost('MYR')"
                    style="display:flex; align-items:center; gap:6px; padding:7px 12px; border:none; border-radius:8px; background:#2563eb; cursor:pointer; font-size:0.8rem; color:#fff; font-weight:600; white-space:nowrap; margin-right:8px;"
                    onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px; height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="2" width="16" height="20" rx="2"></rect>
                        <line x1="8" y1="6" x2="16" y2="6"></line>
                        <line x1="8" y1="10" x2="10" y2="10"></line>
                        <line x1="12" y1="10" x2="16" y2="10"></line>
                        <line x1="8" y1="14" x2="10" y2="14"></line>
                        <line x1="12" y1="14" x2="16" y2="14"></line>
                        <line x1="8" y1="18" x2="16" y2="18"></line>
                    </svg>
                    Generate Forecast Cost
                </button>
                <div class="export-dropdown" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="export-btn" @click="open = !open"
                        wire:loading.attr="disabled" wire:target="exportToExcel('MYR'),exportPricingSummaryToExcel('MYR'),exportPricingSummaryAllToExcel('MYR')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="exportToExcel('MYR'),exportPricingSummaryToExcel('MYR'),exportPricingSummaryAllToExcel('MYR')">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="loading-spinner" wire:loading wire:target="exportToExcel('MYR'),exportPricingSummaryToExcel('MYR'),exportPricingSummaryAllToExcel('MYR')"></span>
                        <span wire:loading.remove wire:target="exportToExcel('MYR'),exportPricingSummaryToExcel('MYR'),exportPricingSummaryAllToExcel('MYR')">Export</span>
                        <span wire:loading wire:target="exportToExcel('MYR'),exportPricingSummaryToExcel('MYR'),exportPricingSummaryAllToExcel('MYR')">Exporting...</span>
                        <svg wire:loading.remove wire:target="exportToExcel('MYR'),exportPricingSummaryToExcel('MYR'),exportPricingSummaryAllToExcel('MYR')" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak x-transition.opacity class="export-dropdown-menu">
                        <button type="button" class="export-dropdown-item"
                            wire:click="exportToExcel('MYR')" @click="open = false"
                            title="Customer Analysis">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Customer Summary
                        </button>
                        <button type="button" class="export-dropdown-item"
                            wire:click="exportPricingSummaryAllToExcel('MYR')" @click="open = false"
                            title="Pricing Summary All (single sheet, all resellers combined)">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                            Pricing Summary All
                        </button>
                        <button type="button" class="export-dropdown-item"
                            wire:click="exportPricingSummaryToExcel('MYR')" @click="open = false"
                            title="Pricing Summary Individual (one tab per reseller)">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Pricing Summary Individual
                        </button>
                    </div>
                </div>
            </div>

            {{-- Summary cards — mirror Termination Analysis (date+totals / reseller type / 4 HR modules) --}}
            @php
                $moduleColors = ['TA' => '#3b82f6', 'TL' => '#8b5cf6', 'TC' => '#f59e0b', 'TP' => '#10b981'];
                $moduleLabels = ['TA' => 'Attendance', 'TL' => 'Leave', 'TC' => 'Claim', 'TP' => 'Payroll'];
            @endphp
            <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:16px;">
                {{-- Card 1: Date range + totals --}}
                <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px 24px; flex:1.2; min-width:240px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <p style="font-size:0.7rem; color:#9ca3af; margin:0;">
                        {{ \Carbon\Carbon::parse($myrStartDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($myrEndDate)->format('d M Y') }}
                    </p>
                    <div style="display:flex; align-items:baseline; gap:20px; margin:6px 0 0; flex-wrap:wrap;">
                        <p style="font-size:1.85rem; font-weight:700; color:#dc2626; margin:0;">
                            {{ number_format(count($myrData)) }}
                            <span style="font-size:0.8rem; font-weight:600; color:#6b7280;">resellers</span>
                        </p>
                        <p style="font-size:1.85rem; font-weight:700; color:#f59e0b; margin:0;">
                            {{ number_format($myrTotalEndUsers) }}
                            <span style="font-size:0.8rem; font-weight:600; color:#6b7280;">clients</span>
                        </p>
                        <p style="font-size:1.85rem; font-weight:700; color:#2563eb; margin:0;">
                            {{ number_format(array_sum(array_column($myrData, 'total_hc'))) }}
                            <span style="font-size:0.8rem; font-weight:600; color:#6b7280;">headcount</span>
                        </p>
                    </div>
                </div>

                {{-- Card 2: Reseller type breakdown --}}
                <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px 24px; flex:0.8; min-width:240px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <p style="font-size:0.8rem; color:#6b7280; margin:0; font-weight:600;">{{ number_format(count($myrData)) }} Reseller{{ count($myrData) === 1 ? '' : 's' }}</p>
                    <div style="display:flex; align-items:center; margin-top:8px; justify-content:space-around;">
                        <div style="text-align:center; flex:1;">
                            <span style="font-size:1.85rem; font-weight:700; color:#10b981;">{{ $myrSummary['categories']['end_user'] ?? 0 }}</span>
                            <p style="font-size:0.7rem; color:#475569; margin:2px 0 0;">End User</p>
                        </div>
                        <div style="text-align:center; flex:1;">
                            <span style="font-size:1.85rem; font-weight:700; color:#2563eb;">{{ $myrSummary['categories']['dealer'] ?? 0 }}</span>
                            <p style="font-size:0.7rem; color:#475569; margin:2px 0 0;">Dealer</p>
                        </div>
                        <div style="text-align:center; flex:1;">
                            <span style="font-size:1.85rem; font-weight:700; color:#7c3aed;">{{ $myrSummary['categories']['distributor'] ?? 0 }}</span>
                            <p style="font-size:0.7rem; color:#475569; margin:2px 0 0;">Distributor</p>
                        </div>
                    </div>
                </div>

                {{-- Cards 3-6: HR module penetration across client portfolio --}}
                @foreach(['TA', 'TL', 'TC', 'TP'] as $mod)
                    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px 20px; min-width:140px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <p style="font-size:0.75rem; color:#6b7280; margin:0; font-weight:600;">{{ $moduleLabels[$mod] }}</p>
                        <p style="font-size:0.8rem; color:#9ca3af; margin:2px 0 0;">{{ number_format($myrSummary['modules'][$mod]['headcount'] ?? 0) }} headcount</p>
                        <div style="display:flex; align-items:baseline; gap:6px; margin-top:4px;">
                            <span style="font-size:1.85rem; font-weight:700; color:{{ $moduleColors[$mod] }};">{{ $myrSummary['modules'][$mod]['companies'] ?? 0 }}</span>
                            <span style="font-size:0.8rem; font-weight:600; color:#6b7280;">company</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <table class="reseller-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Account</th>
                        <th>Reseller Name</th>
                        <th>Total Forecast Cost</th>
                        <th>Total Clients</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php $myrGrouped = $this->groupByYearMonth($myrData); @endphp
                    @forelse ($myrGrouped as $yearMonth => $group)
                        {{-- Tier 1: Year-month header --}}
                        <tr class="reseller-group-row" wire:click="toggleYearMonth('{{ $yearMonth }}')">
                            <td colspan="6">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <svg class="reseller-chevron {{ in_array($yearMonth, $expandedYearMonths) ? 'open' : '' }}" style="width:18px; height:18px; color:#475569; flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span style="font-weight:700; font-size:0.95rem; color:#1a56db;">{{ $group['label'] }}</span>
                                    <span style="margin-left:auto; background:#fef2f2; color:#dc2626; padding:2px 10px; border-radius:9999px; font-size:0.75rem; font-weight:600;">
                                        {{ $group['count'] }} {{ $group['count'] === 1 ? 'reseller' : 'resellers' }}
                                    </span>
                                    <span style="font-size:0.8rem; font-weight:600; color:#475569;">
                                        {{ number_format($group['total_hc']) }} HC
                                    </span>
                                </div>
                            </td>
                        </tr>
                        {{-- Tier 2: Reseller rows in this month --}}
                        @if (in_array($yearMonth, $expandedYearMonths))
                            @foreach ($group['resellers'] as $localIndex => $reseller)
                                <tr x-show="accountFilter === 'all' || (accountFilter === 'registered' && {{ ($reseller['has_account'] ?? false) ? 'true' : 'false' }}) || (accountFilter === 'unregistered' && !{{ ($reseller['has_account'] ?? false) ? 'true' : 'false' }})">
                                    <td>{{ $localIndex + 1 }}</td>
                                    <td>
                                        @if ($reseller['has_account'] ?? false)
                                            <span class="account-icon has-account" title="Registered in Reseller Portal">✓</span>
                                        @else
                                            <span class="account-icon no-account" title="Not registered in Reseller Portal">✗</span>
                                        @endif
                                    </td>
                                    <td class="reseller-name" wire:click="toggleResellerExpansion('{{ addslashes($reseller['reseller_name']) }}', 'MYR')" style="cursor:pointer;">
                                        <svg class="reseller-row-chevron {{ in_array($reseller['reseller_name'], $expandedResellersMyr) ? 'open' : '' }}" style="display:inline-block; width:14px; height:14px; color:#6b7280; vertical-align:middle; margin-right:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        {{ strtoupper($reseller['reseller_name']) }}
                                    </td>
                                    <td>
                                        <span style="display:inline-flex; align-items:center; gap:4px; font-weight:600; color:#16a34a;">
                                            <svg style="width:12px; height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            RM {{ number_format($reseller['total_forecast_cost'] ?? 0) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="end-user-count"
                                            wire:click="openResellerDrawer('{{ addslashes($reseller['reseller_name']) }}', 'MYR')"
                                            title="Click to view clients">
                                            {{ number_format($reseller['total_end_users']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: inline-flex; gap: 6px; align-items: center;">
                                            <button wire:click="exportResellerToExcel('{{ addslashes($reseller['reseller_name']) }}', 'MYR')"
                                                class="drawer-export-btn"
                                                title="Customer Analysis"
                                                wire:loading.attr="disabled"
                                                wire:target="exportResellerToExcel('{{ addslashes($reseller['reseller_name']) }}', 'MYR')">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="exportResellerToExcel('{{ addslashes($reseller['reseller_name']) }}', 'MYR')">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <span wire:loading wire:target="exportResellerToExcel('{{ addslashes($reseller['reseller_name']) }}', 'MYR')">...</span>
                                            </button>
                                            <button wire:click="exportResellerPricingToExcel('{{ addslashes($reseller['reseller_name']) }}', 'MYR')"
                                                class="drawer-export-btn pricing-export-btn"
                                                title="Pricing Analysis"
                                                wire:loading.attr="disabled"
                                                wire:target="exportResellerPricingToExcel('{{ addslashes($reseller['reseller_name']) }}', 'MYR')">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="exportResellerPricingToExcel('{{ addslashes($reseller['reseller_name']) }}', 'MYR')">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <span wire:loading wire:target="exportResellerPricingToExcel('{{ addslashes($reseller['reseller_name']) }}', 'MYR')">...</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @if (in_array($reseller['reseller_name'], $expandedResellersMyr))
                                    <tr class="reseller-clients-row">
                                        <td colspan="6" style="background:#fafbfc; padding-top:12px; padding-right:16px; padding-bottom:12px;">
                                            @php $clients = $this->getResellerClientsCompact($reseller['reseller_name'], 'MYR'); @endphp
                                            @forelse ($clients as $client)
                                                <div style="display:flex; align-items:center; gap:12px; padding:6px 0; border-bottom:1px solid #f3f4f6;">
                                                    <span style="flex:1; font-size:0.8rem; color:#1f2937; font-weight:500;">
                                                        {{ strtoupper($client['company_name']) }}
                                                    </span>
                                                    <span style="font-size:0.7rem; background:#dbeafe; color:#1e40af; padding:2px 10px; border-radius:9999px; font-weight:600; white-space:nowrap;">
                                                        HC {{ number_format($client['total_hc']) }}
                                                    </span>
                                                    <span style="font-size:0.7rem; background:#dcfce7; color:#166534; padding:2px 10px; border-radius:9999px; font-weight:600; white-space:nowrap;">
                                                        RM {{ number_format($client['total_forecast_cost']) }}
                                                    </span>
                                                </div>
                                            @empty
                                                <div style="font-size:0.8rem; color:#9ca3af; padding:6px 0;">No clients in the current window.</div>
                                            @endforelse
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:20px; color:#9ca3af;">No reseller data found</td>
                        </tr>
                    @endforelse
                    @if (!empty($myrData))
                        <tr class="total-row">
                            <td></td>
                            <td></td>
                            <td class="reseller-name">TOTAL</td>
                            <td>
                                <span class="end-user-count">{{ number_format($myrTotalEndUsers) }}</span>
                            </td>
                            <td></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- USD Tab Content -->
        <div :class="{'reseller-tab-content': true, 'active': activeTab === 'USD'}">
            @php
                $usdData = $this->usdData;
                $usdTotalEndUsers = array_sum(array_column($usdData, 'total_end_users'));
                [$usdStartDate, $usdEndDate] = $this->getDateRange();
                $usdRegistered = count(array_filter($usdData, fn($r) => $r['has_account'] ?? false));
                $usdUnregistered = count($usdData) - $usdRegistered;
            @endphp

            <div class="reseller-header">
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <div class="account-filter">
                        <button @click="accountFilter = 'all'" :class="{'account-filter-btn': true, 'active': accountFilter === 'all'}">All ({{ count($usdData) }})</button>
                        <button @click="accountFilter = 'registered'" :class="{'account-filter-btn': true, 'active': accountFilter === 'registered'}">Registered ({{ $usdRegistered }})</button>
                        <button @click="accountFilter = 'unregistered'" :class="{'account-filter-btn': true, 'active': accountFilter === 'unregistered'}">Unregistered ({{ $usdUnregistered }})</button>
                    </div>
                </div>
                {{-- Inline date-range mode selector — replaces the Filter dropdown --}}
                <div style="display:flex; align-items:center; gap:8px; margin-left:auto; margin-right:8px;">
                    <div style="display:flex; align-items:center; gap:4px; background:#f1f5f9; border-radius:8px; padding:2px;">
                        @foreach (['all' => 'All', 'year' => 'Year', 'month' => 'Month', 'range' => 'Range'] as $mode => $label)
                            <button wire:click="$set('filterMode', '{{ $mode }}')"
                                style="padding:6px 12px; border-radius:6px; border:none; font-size:0.75rem; font-weight:600; cursor:pointer;
                                {{ $filterMode === $mode ? 'background:#1a56db; color:#fff;' : 'background:transparent; color:#64748b;' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    @if ($filterMode === 'year')
                        <select wire:model.change="selectedYear" style="padding:6px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:0.75rem; background:#fff;">
                            @foreach ($availableYears as $year)
                                <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                    @elseif ($filterMode === 'month')
                        <input type="month" wire:model.change="selectedMonthYear"
                            style="padding:6px 10px; border:1px solid #d1d5db; border-radius:8px; font-size:0.75rem; background:#fff;">
                    @elseif ($filterMode === 'range')
                        <div style="display:flex; align-items:center; gap:4px;">
                            <input type="date" wire:model.change="startDate" style="padding:6px 8px; border:1px solid #d1d5db; border-radius:8px; font-size:0.7rem;">
                            <span style="color:#9ca3af; font-size:0.7rem;">to</span>
                            <input type="date" wire:model.change="endDate" style="padding:6px 8px; border:1px solid #d1d5db; border-radius:8px; font-size:0.7rem;">
                        </div>
                    @endif
                </div>
                <span style="display:inline-flex; align-items:center; gap:6px; font-size:0.8rem; color:#1a56db; font-weight:700; white-space:nowrap; background:#eff6ff; border:1px solid #bfdbfe; padding:5px 10px; border-radius:8px; margin-right:8px;">
                    <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    {{ \Carbon\Carbon::parse($usdStartDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($usdEndDate)->format('d M Y') }}
                </span>
                <button wire:click="generateForecastCost('USD')"
                    style="display:flex; align-items:center; gap:6px; padding:7px 12px; border:none; border-radius:8px; background:#2563eb; cursor:pointer; font-size:0.8rem; color:#fff; font-weight:600; white-space:nowrap; margin-right:8px;"
                    onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px; height:16px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="2" width="16" height="20" rx="2"></rect>
                        <line x1="8" y1="6" x2="16" y2="6"></line>
                        <line x1="8" y1="10" x2="10" y2="10"></line>
                        <line x1="12" y1="10" x2="16" y2="10"></line>
                        <line x1="8" y1="14" x2="10" y2="14"></line>
                        <line x1="12" y1="14" x2="16" y2="14"></line>
                        <line x1="8" y1="18" x2="16" y2="18"></line>
                    </svg>
                    Generate Forecast Cost
                </button>
                <div class="export-dropdown" x-data="{ open: false }" @click.outside="open = false">
                    <button type="button" class="export-btn" @click="open = !open"
                        wire:loading.attr="disabled" wire:target="exportToExcel('USD'),exportPricingSummaryToExcel('USD'),exportPricingSummaryAllToExcel('USD')">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="exportToExcel('USD'),exportPricingSummaryToExcel('USD'),exportPricingSummaryAllToExcel('USD')">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="loading-spinner" wire:loading wire:target="exportToExcel('USD'),exportPricingSummaryToExcel('USD'),exportPricingSummaryAllToExcel('USD')"></span>
                        <span wire:loading.remove wire:target="exportToExcel('USD'),exportPricingSummaryToExcel('USD'),exportPricingSummaryAllToExcel('USD')">Export</span>
                        <span wire:loading wire:target="exportToExcel('USD'),exportPricingSummaryToExcel('USD'),exportPricingSummaryAllToExcel('USD')">Exporting...</span>
                        <svg wire:loading.remove wire:target="exportToExcel('USD'),exportPricingSummaryToExcel('USD'),exportPricingSummaryAllToExcel('USD')" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:14px;height:14px;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-cloak x-transition.opacity class="export-dropdown-menu">
                        <button type="button" class="export-dropdown-item"
                            wire:click="exportToExcel('USD')" @click="open = false"
                            title="Customer Analysis">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Customer Summary
                        </button>
                        <button type="button" class="export-dropdown-item"
                            wire:click="exportPricingSummaryAllToExcel('USD')" @click="open = false"
                            title="Pricing Summary All (single sheet, all resellers combined)">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                            Pricing Summary All
                        </button>
                        <button type="button" class="export-dropdown-item"
                            wire:click="exportPricingSummaryToExcel('USD')" @click="open = false"
                            title="Pricing Summary Individual (one tab per reseller)">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Pricing Summary Individual
                        </button>
                    </div>
                </div>
            </div>

            {{-- Summary cards — mirror Termination Analysis (date+totals / reseller type / 4 HR modules) --}}
            @php
                $moduleColors = ['TA' => '#3b82f6', 'TL' => '#8b5cf6', 'TC' => '#f59e0b', 'TP' => '#10b981'];
                $moduleLabels = ['TA' => 'Attendance', 'TL' => 'Leave', 'TC' => 'Claim', 'TP' => 'Payroll'];
            @endphp
            <div style="display:flex; gap:16px; flex-wrap:wrap; margin-bottom:16px;">
                {{-- Card 1: Date range + totals --}}
                <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px 24px; flex:1.2; min-width:240px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <p style="font-size:0.7rem; color:#9ca3af; margin:0;">
                        {{ \Carbon\Carbon::parse($usdStartDate)->format('d M Y') }} – {{ \Carbon\Carbon::parse($usdEndDate)->format('d M Y') }}
                    </p>
                    <div style="display:flex; align-items:baseline; gap:20px; margin:6px 0 0; flex-wrap:wrap;">
                        <p style="font-size:1.85rem; font-weight:700; color:#dc2626; margin:0;">
                            {{ number_format(count($usdData)) }}
                            <span style="font-size:0.8rem; font-weight:600; color:#6b7280;">resellers</span>
                        </p>
                        <p style="font-size:1.85rem; font-weight:700; color:#f59e0b; margin:0;">
                            {{ number_format($usdTotalEndUsers) }}
                            <span style="font-size:0.8rem; font-weight:600; color:#6b7280;">clients</span>
                        </p>
                        <p style="font-size:1.85rem; font-weight:700; color:#2563eb; margin:0;">
                            {{ number_format(array_sum(array_column($usdData, 'total_hc'))) }}
                            <span style="font-size:0.8rem; font-weight:600; color:#6b7280;">headcount</span>
                        </p>
                    </div>
                </div>

                {{-- Card 2: Reseller type breakdown --}}
                <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px 24px; flex:0.8; min-width:240px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <p style="font-size:0.8rem; color:#6b7280; margin:0; font-weight:600;">{{ number_format(count($usdData)) }} Reseller{{ count($usdData) === 1 ? '' : 's' }}</p>
                    <div style="display:flex; align-items:center; margin-top:8px; justify-content:space-around;">
                        <div style="text-align:center; flex:1;">
                            <span style="font-size:1.85rem; font-weight:700; color:#10b981;">{{ $usdSummary['categories']['end_user'] ?? 0 }}</span>
                            <p style="font-size:0.7rem; color:#475569; margin:2px 0 0;">End User</p>
                        </div>
                        <div style="text-align:center; flex:1;">
                            <span style="font-size:1.85rem; font-weight:700; color:#2563eb;">{{ $usdSummary['categories']['dealer'] ?? 0 }}</span>
                            <p style="font-size:0.7rem; color:#475569; margin:2px 0 0;">Dealer</p>
                        </div>
                        <div style="text-align:center; flex:1;">
                            <span style="font-size:1.85rem; font-weight:700; color:#7c3aed;">{{ $usdSummary['categories']['distributor'] ?? 0 }}</span>
                            <p style="font-size:0.7rem; color:#475569; margin:2px 0 0;">Distributor</p>
                        </div>
                    </div>
                </div>

                {{-- Cards 3-6: HR module penetration across client portfolio --}}
                @foreach(['TA', 'TL', 'TC', 'TP'] as $mod)
                    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px 20px; min-width:140px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                        <p style="font-size:0.75rem; color:#6b7280; margin:0; font-weight:600;">{{ $moduleLabels[$mod] }}</p>
                        <p style="font-size:0.8rem; color:#9ca3af; margin:2px 0 0;">{{ number_format($usdSummary['modules'][$mod]['headcount'] ?? 0) }} headcount</p>
                        <div style="display:flex; align-items:baseline; gap:6px; margin-top:4px;">
                            <span style="font-size:1.85rem; font-weight:700; color:{{ $moduleColors[$mod] }};">{{ $usdSummary['modules'][$mod]['companies'] ?? 0 }}</span>
                            <span style="font-size:0.8rem; font-weight:600; color:#6b7280;">company</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <table class="reseller-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Account</th>
                        <th>Reseller Name</th>
                        <th>Total Forecast Cost</th>
                        <th>Total Clients</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @php $usdGrouped = $this->groupByYearMonth($usdData); @endphp
                    @forelse ($usdGrouped as $yearMonth => $group)
                        {{-- Tier 1: Year-month header --}}
                        <tr class="reseller-group-row" wire:click="toggleYearMonth('{{ $yearMonth }}')">
                            <td colspan="6">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <svg class="reseller-chevron {{ in_array($yearMonth, $expandedYearMonths) ? 'open' : '' }}" style="width:18px; height:18px; color:#475569; flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span style="font-weight:700; font-size:0.95rem; color:#1a56db;">{{ $group['label'] }}</span>
                                    <span style="margin-left:auto; background:#fef2f2; color:#dc2626; padding:2px 10px; border-radius:9999px; font-size:0.75rem; font-weight:600;">
                                        {{ $group['count'] }} {{ $group['count'] === 1 ? 'reseller' : 'resellers' }}
                                    </span>
                                    <span style="font-size:0.8rem; font-weight:600; color:#475569;">
                                        {{ number_format($group['total_hc']) }} HC
                                    </span>
                                </div>
                            </td>
                        </tr>
                        {{-- Tier 2: Reseller rows in this month --}}
                        @if (in_array($yearMonth, $expandedYearMonths))
                            @foreach ($group['resellers'] as $localIndex => $reseller)
                                <tr x-show="accountFilter === 'all' || (accountFilter === 'registered' && {{ ($reseller['has_account'] ?? false) ? 'true' : 'false' }}) || (accountFilter === 'unregistered' && !{{ ($reseller['has_account'] ?? false) ? 'true' : 'false' }})">
                                    <td>{{ $localIndex + 1 }}</td>
                                    <td>
                                        @if ($reseller['has_account'] ?? false)
                                            <span class="account-icon has-account" title="Registered in Reseller Portal">✓</span>
                                        @else
                                            <span class="account-icon no-account" title="Not registered in Reseller Portal">✗</span>
                                        @endif
                                    </td>
                                    <td class="reseller-name" wire:click="toggleResellerExpansion('{{ addslashes($reseller['reseller_name']) }}', 'USD')" style="cursor:pointer;">
                                        <svg class="reseller-row-chevron {{ in_array($reseller['reseller_name'], $expandedResellersUsd) ? 'open' : '' }}" style="display:inline-block; width:14px; height:14px; color:#6b7280; vertical-align:middle; margin-right:8px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                        {{ strtoupper($reseller['reseller_name']) }}
                                    </td>
                                    <td>
                                        <span style="display:inline-flex; align-items:center; gap:4px; font-weight:600; color:#16a34a;">
                                            <svg style="width:12px; height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            USD {{ number_format($reseller['total_forecast_cost'] ?? 0) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="end-user-count"
                                            wire:click="openResellerDrawer('{{ addslashes($reseller['reseller_name']) }}', 'USD')"
                                            title="Click to view clients">
                                            {{ number_format($reseller['total_end_users']) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: inline-flex; gap: 6px; align-items: center;">
                                            <button wire:click="exportResellerToExcel('{{ addslashes($reseller['reseller_name']) }}', 'USD')"
                                                class="drawer-export-btn"
                                                title="Customer Analysis"
                                                wire:loading.attr="disabled"
                                                wire:target="exportResellerToExcel('{{ addslashes($reseller['reseller_name']) }}', 'USD')">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="exportResellerToExcel('{{ addslashes($reseller['reseller_name']) }}', 'USD')">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <span wire:loading wire:target="exportResellerToExcel('{{ addslashes($reseller['reseller_name']) }}', 'USD')">...</span>
                                            </button>
                                            <button wire:click="exportResellerPricingToExcel('{{ addslashes($reseller['reseller_name']) }}', 'USD')"
                                                class="drawer-export-btn pricing-export-btn"
                                                title="Pricing Analysis"
                                                wire:loading.attr="disabled"
                                                wire:target="exportResellerPricingToExcel('{{ addslashes($reseller['reseller_name']) }}', 'USD')">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" wire:loading.remove wire:target="exportResellerPricingToExcel('{{ addslashes($reseller['reseller_name']) }}', 'USD')">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <span wire:loading wire:target="exportResellerPricingToExcel('{{ addslashes($reseller['reseller_name']) }}', 'USD')">...</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @if (in_array($reseller['reseller_name'], $expandedResellersUsd))
                                    <tr class="reseller-clients-row">
                                        <td colspan="6" style="background:#fafbfc; padding-top:12px; padding-right:16px; padding-bottom:12px;">
                                            @php $clients = $this->getResellerClientsCompact($reseller['reseller_name'], 'USD'); @endphp
                                            @forelse ($clients as $client)
                                                <div style="display:flex; align-items:center; gap:12px; padding:6px 0; border-bottom:1px solid #f3f4f6;">
                                                    <span style="flex:1; font-size:0.8rem; color:#1f2937; font-weight:500;">
                                                        {{ strtoupper($client['company_name']) }}
                                                    </span>
                                                    <span style="font-size:0.7rem; background:#dbeafe; color:#1e40af; padding:2px 10px; border-radius:9999px; font-weight:600; white-space:nowrap;">
                                                        HC {{ number_format($client['total_hc']) }}
                                                    </span>
                                                    <span style="font-size:0.7rem; background:#dcfce7; color:#166534; padding:2px 10px; border-radius:9999px; font-weight:600; white-space:nowrap;">
                                                        USD {{ number_format($client['total_forecast_cost']) }}
                                                    </span>
                                                </div>
                                            @empty
                                                <div style="font-size:0.8rem; color:#9ca3af; padding:6px 0;">No clients in the current window.</div>
                                            @endforelse
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center; padding:20px; color:#9ca3af;">No reseller data found</td>
                        </tr>
                    @endforelse
                    @if (!empty($usdData))
                        <tr class="total-row">
                            <td></td>
                            <td></td>
                            <td class="reseller-name">TOTAL</td>
                            <td>
                                <span class="end-user-count">{{ number_format($usdTotalEndUsers) }}</span>
                            </td>
                            <td></td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Reseller Analysis Tab Content -->
        <div :class="{'reseller-tab-content': true, 'active': activeTab === 'ANALYSIS'}"
             x-data="resellerCommissionTab(@js($commissionData), @js($commissionPiData), @js($grossPriceAboveData), @js($nettPriceBelowData))"
             x-init="init()"
             x-effect="if (activeTab === 'ANALYSIS') $nextTick(() => { if (activeSection === 'commission') renderChart(); if (activeSection === 'commissionPi') renderPiChart(); if (activeSection === 'grossPrice') renderGrossChart(); if (activeSection === 'nettPrice') renderNettChart(); })">
            <div class="commission-layout">
                <aside class="commission-sidebar">
                    {{-- MYR / USD toggle --}}
                    <div class="commission-toggle">
                        <button type="button" class="commission-toggle-btn"
                            :class="{ active: currency === 'MYR' }"
                            @click="setCurrency('MYR')">MYR</button>
                        <button type="button" class="commission-toggle-btn"
                            :class="{ active: currency === 'USD' }"
                            @click="setCurrency('USD')">USD</button>
                    </div>

                    <div class="commission-section-title">Analysis</div>

                    {{-- Section: Reseller Commission --}}
                    <button type="button" class="analysis-section-header"
                        :class="{ active: activeSection === 'commission' }"
                        @click="setSection('commission')">
                        <span>Reseller Commission (DB)</span>
                    </button>

                    {{-- Section: Reseller Commission (PI) --}}
                    <button type="button" class="analysis-section-header"
                        :class="{ active: activeSection === 'commissionPi' }"
                        @click="setSection('commissionPi')">
                        <span>Reseller Commission (PI)</span>
                    </button>

                    {{-- Section: Gross Price (Above) --}}
                    <button type="button" class="analysis-section-header"
                        :class="{ active: activeSection === 'grossPrice' }"
                        @click="setSection('grossPrice')">
                        <span>Gross Price</span>
                    </button>

                    {{-- Section: Nett Price (Below) --}}
                    <button type="button" class="analysis-section-header"
                        :class="{ active: activeSection === 'nettPrice' }"
                        @click="setSection('nettPrice')">
                        <span>Nett Price</span>
                    </button>
                </aside>

                <section class="commission-main">
                    <template x-if="activeSection === 'commission'">
                        <div>
                            <h3 class="commission-main-title">Based on Database
                            <div class="commission-chart-wrap">
                                <canvas id="commissionBarChart"></canvas>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeSection === 'commissionPi'">
                        <div>
                            <h3 class="commission-main-title">Based on Proforma Invoice</h3>

                            <div style="margin-bottom: 10px; font-size: 12px; color:#6b7280;">
                                Click a bar to view the PIs in that commission bucket.
                                Total <span x-text="totalPis()"></span> PIs.
                            </div>
                            <div style="position: relative; height: 540px; border: 1px solid #e5e7eb; border-radius: 6px; padding: 12px;">
                                <canvas id="piResellerBarChart"></canvas>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeSection === 'grossPrice'">
                        <div>
                            <h3 class="commission-main-title">
                                Gross Price <span x-text="currency === 'MYR' ? '(Above RM3)' : '(Above USD1)'"></span>
                            </h3>

                            {{-- View toggle: by reseller / by price --}}
                            <div style="margin-bottom: 12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                                <div style="display:inline-flex; background:#f3f4f6; border-radius:8px; padding:3px;">
                                    <button type="button"
                                        :class="grossView === 'reseller' ? 'gross-view-btn active' : 'gross-view-btn'"
                                        @click="setGrossView('reseller')">By Reseller</button>
                                    <button type="button"
                                        :class="grossView === 'price' ? 'gross-view-btn active' : 'gross-view-btn'"
                                        @click="setGrossView('price')">By Price</button>
                                </div>
                                <span style="font-size: 12px; color:#6b7280;">
                                    <span x-text="grossTotalPis()"></span> PIs across
                                    <span x-text="Object.keys(grossByReseller).length"></span> reseller(s).
                                </span>
                            </div>

                            <div style="max-height: 540px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <div :style="`position: relative; height: ${grossView === 'reseller' ? Math.max(540, (Object.keys(grossByReseller).length * 24) + 80) : 540}px; padding: 12px;`">
                                    <canvas id="grossPriceBarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </template>

                    <template x-if="activeSection === 'nettPrice'">
                        <div>
                            <h3 class="commission-main-title">
                                Nett Price <span x-text="currency === 'MYR' ? '(Below RM1)' : '(Below USD0.5)'"></span>
                            </h3>

                            <div style="margin-bottom: 12px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                                <div style="display:inline-flex; background:#f3f4f6; border-radius:8px; padding:3px;">
                                    <button type="button"
                                        :class="nettView === 'reseller' ? 'gross-view-btn active' : 'gross-view-btn'"
                                        @click="setNettView('reseller')">By Reseller</button>
                                    <button type="button"
                                        :class="nettView === 'price' ? 'gross-view-btn active' : 'gross-view-btn'"
                                        @click="setNettView('price')">By Price</button>
                                </div>
                                <span style="font-size: 12px; color:#6b7280;">
                                    <span x-text="nettTotalPis()"></span> PIs across
                                    <span x-text="Object.keys(nettByReseller).length"></span> reseller(s).
                                </span>
                            </div>

                            <div style="max-height: 540px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px;">
                                <div :style="`position: relative; height: ${nettView === 'reseller' ? Math.max(540, (Object.keys(nettByReseller).length * 24) + 80) : 540}px; padding: 12px;`">
                                    <canvas id="nettPriceBarChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </template>
                </section>
            </div>

            {{-- Bar-chart bucket drill-down Slide-Over --}}
            <div x-show="bucketDrill" x-cloak style="position: fixed; inset: 0; z-index: 9999;">
                <div @click="bucketDrill = null" style="position: absolute; inset: 0; background: rgba(0,0,0,0.4);"></div>
                <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 460px; background: white; box-shadow: -4px 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column;" @click.stop>
                    <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 1rem; font-weight: 600;" x-text="bucketDrill?.label"></h3>
                            <p style="margin: 2px 0 0; font-size: 12px; color: #6b7280;">
                                <span x-text="currency"></span> · <span x-text="bucketDrill?.count"></span> reseller(s)
                            </p>
                        </div>
                        <button @click="bucketDrill = null" style="background: none; border: none; cursor: pointer; padding: 4px; color: #6b7280;">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div style="padding: 12px 20px 0;">
                        <input type="text" x-model="bucketDrillSearch" placeholder="Search reseller name"
                            style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                    </div>
                    <div style="flex: 1; overflow-y: auto; padding: 12px 20px;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <thead>
                                <tr>
                                    <th style="text-align: left; padding: 6px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-weight: 600;">Reseller</th>
                                    <th style="text-align: right; padding: 6px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-weight: 600;">Rate</th>
                                    <th style="text-align: right; padding: 6px 0; border-bottom: 1px solid #e5e7eb; color: #6b7280; font-weight: 600;">End Users</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="filteredBucketResellers().length === 0">
                                    <tr><td colspan="3" style="padding: 16px 0; text-align: center; color: #9ca3af;"
                                        x-text="bucketDrillSearch ? 'No resellers match your search.' : 'No resellers in this bucket.'"></td></tr>
                                </template>
                                <template x-for="r in filteredBucketResellers()" :key="r.reseller_name">
                                    <tr style="border-bottom: 1px solid #f3f4f6;">
                                        <td style="padding: 8px 0;" x-text="(r.reseller_name || '').toUpperCase()"></td>
                                        <td style="padding: 8px 0; text-align: right; font-variant-numeric: tabular-nums; color: #6b7280;" x-text="r.rate.toFixed(2) + '%'"></td>
                                        <td style="padding: 8px 0; text-align: right; font-variant-numeric: tabular-nums;">
                                            <button type="button"
                                                @click="bucketDrill = null; $wire.openResellerDrawer(r.reseller_name, currency)"
                                                style="background: none; border: none; padding: 0; color: #2563eb; font-weight: 600; cursor: pointer; text-decoration: underline; font-variant-numeric: tabular-nums;"
                                                x-text="r.total_end_users"></button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- PI bar-chart bucket drill-down (commission % bucket -> PI list) --}}
            <div x-show="piBucketDrill" x-cloak style="position: fixed; inset: 0; z-index: 9999;">
                <div @click="piBucketDrill = null" style="position: absolute; inset: 0; background: rgba(0,0,0,0.4);"></div>
                <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 640px; max-width: 95vw; background: white; box-shadow: -4px 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column;" @click.stop>
                    <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 1rem; font-weight: 600;" x-text="piBucketDrill?.label"></h3>
                            <p style="margin: 2px 0 0; font-size: 12px; color: #6b7280;">
                                <span x-text="currency"></span> · <span x-text="piBucketDrill?.count"></span> PI(s)
                            </p>
                        </div>
                        <button @click="piBucketDrill = null" style="background: none; border: none; cursor: pointer; padding: 4px; color: #6b7280;">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div style="padding: 12px 20px 0;">
                        <input type="text" x-model="piBucketDrillSearch" placeholder="Search by PI / Reseller / Subscriber"
                            style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                    </div>
                    <div style="flex: 1; overflow-y: auto; padding: 12px 20px;">
                        <template x-if="piBucketGroups().length === 0">
                            <div style="padding: 16px 0; text-align: center; color: #9ca3af; font-size: 13px;"
                                x-text="piBucketDrillSearch ? 'No PIs match your search.' : 'No PIs in this bucket.'"></div>
                        </template>
                        <template x-for="group in piBucketGroups()" :key="group.name">
                            <div style="border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 8px; overflow: hidden;">
                                <button type="button" @click="togglePiBucketGroup(group.name)"
                                    style="display:flex; align-items:center; justify-content:space-between; width:100%; padding: 10px 12px; background:#f9fafb; border:none; cursor:pointer; text-align:left; gap: 8px;">
                                    <span style="display:flex; align-items:center; gap:8px;">
                                        <svg :style="(piBucketDrillExpanded[group.name] ? 'transform: rotate(90deg);' : '') + ' transition: transform .15s; color:#6b7280;'"
                                             width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M9 18l6-6-6-6"/>
                                        </svg>
                                        <span style="font-weight:600; font-size:13px; color:#111827;" x-text="group.name"></span>
                                    </span>
                                    <span style="display:inline-flex; align-items:center; justify-content:center; min-width:28px; padding:1px 8px; font-size:11px; font-weight:600; color:#1d4ed8; background:#dbeafe; border-radius:9999px;"
                                        x-text="group.count"></span>
                                </button>
                                <div x-show="piBucketDrillExpanded[group.name]" x-cloak style="border-top: 1px solid #f3f4f6;">
                                    <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
                                        <thead>
                                            <tr>
                                                <th style="text-align:left; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">TT PI Number</th>
                                                <th style="text-align:left; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">Subscriber</th>
                                                <th style="text-align:right; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">Comm %</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="row in group.rows" :key="row.invoice_no">
                                                <tr style="border-bottom: 1px solid #f9fafb;">
                                                    <td style="padding: 8px 12px;">
                                                        <template x-if="row.external_url">
                                                            <a :href="row.external_url" target="_blank" rel="noopener"
                                                                style="color:#2563eb; font-weight:600; text-decoration:underline;"
                                                                x-text="row.invoice_no"></a>
                                                        </template>
                                                        <template x-if="!row.external_url">
                                                            <span style="color:#6b7280; font-weight:600;" x-text="row.invoice_no"></span>
                                                        </template>
                                                    </td>
                                                    <td style="padding: 8px 12px;" x-text="row.subscriber_name"></td>
                                                    <td style="padding: 8px 12px; text-align:right; font-variant-numeric:tabular-nums; color:#6b7280;"
                                                        x-text="row.discount_dealer !== null ? row.discount_dealer.toFixed(2) + '%' : '-'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Gross Price drill-down (price bucket -> PIs grouped by reseller, collapsible) --}}
            <div x-show="grossDrill" x-cloak style="position: fixed; inset: 0; z-index: 9999;">
                <div @click="grossDrill = null" style="position: absolute; inset: 0; background: rgba(0,0,0,0.4);"></div>
                <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 760px; max-width: 95vw; background: white; box-shadow: -4px 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column;" @click.stop>
                    <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 1rem; font-weight: 600;" x-text="grossDrill?.label"></h3>
                            <p style="margin: 2px 0 0; font-size: 12px; color: #6b7280;">
                                <span x-text="currency"></span> · <span x-text="grossDrill?.count"></span> PI(s)
                            </p>
                        </div>
                        <button @click="grossDrill = null" style="background: none; border: none; cursor: pointer; padding: 4px; color: #6b7280;">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div style="padding: 12px 20px 0;">
                        <input type="text" x-model="grossDrillSearch" placeholder="Search by PI / Reseller / Subscriber"
                            style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                    </div>
                    <div style="flex: 1; overflow-y: auto; padding: 12px 20px;">
                        <template x-if="grossDrillGroups().length === 0">
                            <div style="padding: 16px 0; text-align: center; color: #9ca3af; font-size: 13px;"
                                x-text="grossDrillSearch ? 'No PIs match your search.' : 'No PIs in this bucket.'"></div>
                        </template>
                        <template x-for="group in grossDrillGroups()" :key="group.name">
                            <div style="border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 8px; overflow: hidden;">
                                <button type="button" @click="toggleGrossDrillGroup(group.name)"
                                    style="display:flex; align-items:center; justify-content:space-between; width:100%; padding: 10px 12px; background:#f9fafb; border:none; cursor:pointer; text-align:left; gap: 8px;">
                                    <span style="display:flex; align-items:center; gap:8px;">
                                        <svg :style="(grossDrillExpanded[group.name] ? 'transform: rotate(90deg);' : '') + ' transition: transform .15s; color:#6b7280;'"
                                             width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M9 18l6-6-6-6"/>
                                        </svg>
                                        <span style="font-weight:600; font-size:13px; color:#111827;" x-text="group.name"></span>
                                    </span>
                                    <span style="display:inline-flex; align-items:center; justify-content:center; min-width:28px; padding:1px 8px; font-size:11px; font-weight:600; color:#6d28d9; background:#ede9fe; border-radius:9999px;"
                                        x-text="group.count"></span>
                                </button>
                                <div x-show="grossDrillExpanded[group.name]" x-cloak style="border-top: 1px solid #f3f4f6;">
                                    <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
                                        <thead>
                                            <tr>
                                                <th style="text-align:left; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">TT PI Number</th>
                                                <th style="text-align:left; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">Subscriber</th>
                                                <th style="text-align:right; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">TA</th>
                                                <th style="text-align:right; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">TL</th>
                                                <th style="text-align:right; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">TC</th>
                                                <th style="text-align:right; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">TP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="row in group.rows" :key="row.invoice_no">
                                                <tr style="border-bottom: 1px solid #f9fafb;">
                                                    <td style="padding: 8px 12px;">
                                                        <template x-if="row.external_url">
                                                            <a :href="row.external_url" target="_blank" rel="noopener"
                                                                style="color:#2563eb; font-weight:600; text-decoration:underline;"
                                                                x-text="row.invoice_no"></a>
                                                        </template>
                                                        <template x-if="!row.external_url">
                                                            <span style="color:#6b7280; font-weight:600;" x-text="row.invoice_no"></span>
                                                        </template>
                                                    </td>
                                                    <td style="padding: 8px 12px;" x-text="row.subscriber_name"></td>
                                                    <template x-for="mod in ['TA','TL','TC','TP']" :key="mod">
                                                        <td style="padding: 8px 12px; text-align:right; font-variant-numeric:tabular-nums;"
                                                            x-text="row.prices[mod] !== null ? Number(row.prices[mod]).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '-'"
                                                            :style="row.prices[mod] !== null ? 'color:#b91c1c; font-weight:600;' : 'color:#d1d5db;'"></td>
                                                    </template>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Nett Price drill-down (price/reseller -> PIs grouped by reseller, collapsible) --}}
            <div x-show="nettDrill" x-cloak style="position: fixed; inset: 0; z-index: 9999;">
                <div @click="nettDrill = null" style="position: absolute; inset: 0; background: rgba(0,0,0,0.4);"></div>
                <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 760px; max-width: 95vw; background: white; box-shadow: -4px 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column;" @click.stop>
                    <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <h3 style="margin: 0; font-size: 1rem; font-weight: 600;" x-text="nettDrill?.label"></h3>
                            <p style="margin: 2px 0 0; font-size: 12px; color: #6b7280;">
                                <span x-text="currency"></span> · <span x-text="nettDrill?.count"></span> PI(s)
                            </p>
                        </div>
                        <button @click="nettDrill = null" style="background: none; border: none; cursor: pointer; padding: 4px; color: #6b7280;">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div style="padding: 12px 20px 0;">
                        <input type="text" x-model="nettDrillSearch" placeholder="Search by PI / Reseller / Subscriber"
                            style="width: 100%; padding: 8px 12px; font-size: 13px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                    </div>
                    <div style="flex: 1; overflow-y: auto; padding: 12px 20px;">
                        <template x-if="nettDrillGroups().length === 0">
                            <div style="padding: 16px 0; text-align: center; color: #9ca3af; font-size: 13px;"
                                x-text="nettDrillSearch ? 'No PIs match your search.' : 'No PIs in this bucket.'"></div>
                        </template>
                        <template x-for="group in nettDrillGroups()" :key="group.name">
                            <div style="border: 1px solid #e5e7eb; border-radius: 6px; margin-bottom: 8px; overflow: hidden;">
                                <button type="button" @click="toggleNettDrillGroup(group.name)"
                                    style="display:flex; align-items:center; justify-content:space-between; width:100%; padding: 10px 12px; background:#f9fafb; border:none; cursor:pointer; text-align:left; gap: 8px;">
                                    <span style="display:flex; align-items:center; gap:8px;">
                                        <svg :style="(nettDrillExpanded[group.name] ? 'transform: rotate(90deg);' : '') + ' transition: transform .15s; color:#6b7280;'"
                                             width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <path d="M9 18l6-6-6-6"/>
                                        </svg>
                                        <span style="font-weight:600; font-size:13px; color:#111827;" x-text="group.name"></span>
                                    </span>
                                    <span style="display:inline-flex; align-items:center; justify-content:center; min-width:28px; padding:1px 8px; font-size:11px; font-weight:600; color:#6d28d9; background:#ede9fe; border-radius:9999px;"
                                        x-text="group.count"></span>
                                </button>
                                <div x-show="nettDrillExpanded[group.name]" x-cloak style="border-top: 1px solid #f3f4f6;">
                                    <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
                                        <thead>
                                            <tr>
                                                <th style="text-align:left; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">TT PI Number</th>
                                                <th style="text-align:left; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">Subscriber</th>
                                                <th style="text-align:right; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">Comm %</th>
                                                <th style="text-align:right; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">TA</th>
                                                <th style="text-align:right; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">TL</th>
                                                <th style="text-align:right; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">TC</th>
                                                <th style="text-align:right; padding: 6px 12px; border-bottom: 1px solid #f3f4f6; color:#6b7280; font-weight:600;">TP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="row in group.rows" :key="row.invoice_no">
                                                <tr style="border-bottom: 1px solid #f9fafb;">
                                                    <td style="padding: 8px 12px;">
                                                        <template x-if="row.external_url">
                                                            <a :href="row.external_url" target="_blank" rel="noopener"
                                                                style="color:#2563eb; font-weight:600; text-decoration:underline;"
                                                                x-text="row.invoice_no"></a>
                                                        </template>
                                                        <template x-if="!row.external_url">
                                                            <span style="color:#6b7280; font-weight:600;" x-text="row.invoice_no"></span>
                                                        </template>
                                                    </td>
                                                    <td style="padding: 8px 12px;" x-text="row.subscriber_name"></td>
                                                    <td style="padding: 8px 12px; text-align:right; font-variant-numeric:tabular-nums; color:#6b7280;"
                                                        x-text="row.commission_rate.toFixed(2) + '%'"></td>
                                                    <template x-for="mod in ['TA','TL','TC','TP']" :key="mod">
                                                        <td style="padding: 8px 12px; text-align:right; font-variant-numeric:tabular-nums;"
                                                            x-text="row.prices[mod] !== null ? Number(row.prices[mod]).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2}) : '-'"
                                                            :style="row.prices[mod] !== null ? 'color:#1d4ed8; font-weight:600;' : 'color:#d1d5db;'"></td>
                                                    </template>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- PI Detail Slide-Over --}}
            <div x-show="piDetail" x-cloak style="position: fixed; inset: 0; z-index: 9999;">
                <div @click="piDetail = null" style="position: absolute; inset: 0; background: rgba(0,0,0,0.4);"></div>
                <div style="position: absolute; top: 0; right: 0; bottom: 0; width: 420px; background: white; box-shadow: -4px 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column;" @click.stop>
                    <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                        <h3 style="margin: 0; font-size: 1rem; font-weight: 600;">PI Detail · <span x-text="piDetail?.invoice_no"></span></h3>
                        <button @click="piDetail = null" style="background: none; border: none; cursor: pointer; padding: 4px; color: #6b7280;">
                            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div style="padding: 20px; flex: 1; overflow-y: auto; font-size: 13px;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <tbody>
                                <tr><th style="text-align:left; padding: 8px 0; color:#6b7280; font-weight:500; width:40%;">Reseller</th><td style="padding: 8px 0;" x-text="piDetail?.reseller_name"></td></tr>
                                <tr><th style="text-align:left; padding: 8px 0; color:#6b7280; font-weight:500;">Subscriber</th><td style="padding: 8px 0;" x-text="piDetail?.subscriber_name"></td></tr>
                                <tr><th style="text-align:left; padding: 8px 0; color:#6b7280; font-weight:500;">Currency</th><td style="padding: 8px 0;" x-text="piDetail?.currency"></td></tr>
                                <tr><th style="text-align:left; padding: 8px 0; color:#6b7280; font-weight:500;">Total Amount</th><td style="padding: 8px 0;" x-text="piDetail ? piDetail.total_amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : ''"></td></tr>
                                <tr><th style="text-align:left; padding: 8px 0; color:#6b7280; font-weight:500;">Dealer Commission</th><td style="padding: 8px 0;" x-text="piDetail ? piDetail.dealer_commission.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : ''"></td></tr>
                                <tr><th style="text-align:left; padding: 8px 0; color:#6b7280; font-weight:500;">Comm Rate</th><td style="padding: 8px 0;" x-text="piDetail && piDetail.discount_dealer !== null ? piDetail.discount_dealer.toFixed(2) + '%' : '-'"></td></tr>
                                <tr><th style="text-align:left; padding: 8px 0; color:#6b7280; font-weight:500;">Status</th><td style="padding: 8px 0;" x-text="piDetail?.status === 1 ? 'Paid' : 'Pending'"></td></tr>
                                <tr><th style="text-align:left; padding: 8px 0; color:#6b7280; font-weight:500;">Created</th><td style="padding: 8px 0;" x-text="piDetail?.created_time"></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Drawer / Slide-over -->
    <div
        x-data="{ open: @entangle('showDrawer') }"
        x-show="open"
        x-cloak
        @keydown.window.escape="open = false"
        class="drawer-overlay"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <div class="drawer-panel" @click.away="open = false">
            <!-- Header -->
            <div class="drawer-header">
                <h2>{{ $drawerTitle }}</h2>
                <button @click="open = false" class="drawer-close">&times;</button>
            </div>

            <!-- Content -->
            <div class="drawer-content">
                @if (empty($drawerClients))
                    <div class="empty-state">
                        <p>No clients found for this reseller.</p>
                    </div>
                @else
                    <div style="margin-bottom: 12px; font-size: 13px; color: #6b7280;">
                        {{ count($drawerClients) }} client(s) found
                    </div>

                    @foreach ($drawerClients as $client)
                        <div class="client-card">
                            <div class="client-card-header">
                                <div class="client-card-name">
                                    @if (!empty($client['lead_id']))
                                        <a href="{{ url('admin/leads/' . \App\Classes\Encryptor::encrypt($client['lead_id'])) }}"
                                            target="_blank">
                                            {{ $client['company_name'] }}
                                            <svg xmlns="http://www.w3.org/2000/svg" style="display:inline; width:12px; height:12px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                    @else
                                        {{ $client['company_name'] }}
                                    @endif
                                </div>
                            </div>
                            <div class="client-card-meta">
                                @php
                                    $statusLabels = [
                                        'new' => 'New',
                                        'pending_confirmation' => 'Pending Confirmation',
                                        'pending_payment' => 'Pending Payment',
                                        'completed_renewal' => 'Completed',
                                        'completed_reseller_portal' => 'Completed(Reseller Portal)',
                                        'terminated' => 'Terminated',
                                        'no_record' => 'No Record',
                                    ];
                                @endphp
                                <span class="status-badge status-{{ $client['status'] }}">
                                    {{ $statusLabels[$client['status']] ?? ucfirst(str_replace('_', ' ', $client['status'])) }}
                                </span>
                                <span>Expires: {{ \Carbon\Carbon::parse($client['earliest_expiry'])->format('d M Y') }}</span>
                                <span style="display:inline-flex; align-items:center; gap:4px; font-weight:600; color:#1a56db;">
                                    <svg style="width:12px; height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 3a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    HC: {{ number_format($client['total_hc'] ?? 0) }}
                                </span>
                                <span style="display:inline-flex; align-items:center; gap:4px; font-weight:600; color:#16a34a;">
                                    <svg style="width:12px; height:12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Forecast: {{ $drawerCurrency }} {{ number_format(($client['total_hc'] ?? 0) * 12) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <script>
        function resellerCommissionTab(initialData, initialPiData, initialGrossData, initialNettData) {
            return {
                allData: initialData || { MYR: {}, USD: {} },
                allPi: initialPiData || { MYR: { rows: [], byReseller: {} }, USD: { rows: [], byReseller: {} } },
                allGross: initialGrossData || { MYR: { rows: [], byReseller: {}, threshold: 3 }, USD: { rows: [], byReseller: {}, threshold: 1 } },
                allNett: initialNettData || { MYR: { rows: [], byReseller: {}, threshold: 1 }, USD: { rows: [], byReseller: {}, threshold: 0.5 } },
                currency: 'MYR',
                activeSection: 'commission',
                sidebarOpen: true,
                expanded: {},
                chart: null,

                // PI section state
                piDetail: null,

                // Bar-chart drill-down slide-over
                bucketDrill: null,
                bucketDrillSearch: '',

                // PI bar chart drill-down slide-over (by commission %)
                piBucketDrill: null,
                piBucketDrillSearch: '',
                piBucketDrillExpanded: {},
                piChart: null,

                // Gross Price (Above) drill-down slide-over
                grossDrill: null,
                grossDrillSearch: '',
                grossDrillExpanded: {},
                grossChart: null,
                // 'reseller' (default) or 'price' — toggled via the segmented control above the chart.
                grossView: 'reseller',

                // Nett Price (Below) — analogous state.
                nettDrill: null,
                nettDrillSearch: '',
                nettDrillExpanded: {},
                nettChart: null,
                nettView: 'reseller',

                init() {
                    this.refreshExpanded();
                    if (this.$root.activeTab === 'ANALYSIS') {
                        this.$nextTick(() => this.renderChart());
                    }
                },

                setSection(key) {
                    this.activeSection = key;
                    if (key === 'commission') {
                        this.$nextTick(() => this.renderChart());
                    } else if (key === 'commissionPi') {
                        this.$nextTick(() => this.renderPiChart());
                    } else if (key === 'grossPrice') {
                        this.$nextTick(() => this.renderGrossChart());
                    } else if (key === 'nettPrice') {
                        this.$nextTick(() => this.renderNettChart());
                    }
                },

                // Match the sidebar's active-menu gradient: blue (low) -> purple (high).
                //   #667eea (rgb 102,126,234) at t=0  ->  #764ba2 (rgb 118,75,162) at t=1
                gradientColor(t) {
                    const tc = Math.min(1, Math.max(0, isFinite(t) ? t : 0));
                    const r = Math.round(102 + tc * (118 - 102));
                    const g = Math.round(126 + tc * (75  - 126));
                    const b = Math.round(234 + tc * (162 - 234));
                    return `rgb(${r}, ${g}, ${b})`;
                },
                gradientColors(values) {
                    if (!values || values.length === 0) return [];
                    const min = Math.min(...values);
                    const max = Math.max(...values);
                    if (max === min) return values.map(() => this.gradientColor(0.5));
                    return values.map(v => this.gradientColor((v - min) / (max - min)));
                },

                refreshExpanded() {
                    this.expanded = Object.fromEntries(this.bucketKeys().map(k => [k, false]));
                },

                bucketKeys() {
                    return Object.keys(this.allData[this.currency] || {});
                },

                get buckets() {
                    const data = this.allData[this.currency] || {};
                    return Object.entries(data).map(([key, b]) => ({
                        key,
                        label: b.label,
                        count: b.count || 0,
                        resellers: b.resellers || [],
                    }));
                },

                totalResellers() {
                    return this.buckets.reduce((sum, b) => sum + b.count, 0);
                },

                setCurrency(c) {
                    this.currency = c;
                    this.refreshExpanded();
                    // Tear down all charts so the next render rebuilds them with fresh onClick closures.
                    if (this.chart) { this.chart.destroy(); this.chart = null; }
                    if (this.piChart) { this.piChart.destroy(); this.piChart = null; }
                    if (this.grossChart) { this.grossChart.destroy(); this.grossChart = null; }
                    if (this.nettChart) { this.nettChart.destroy(); this.nettChart = null; }
                    this.$nextTick(() => {
                        if (this.activeSection === 'commission') this.renderChart();
                        if (this.activeSection === 'commissionPi') this.renderPiChart();
                        if (this.activeSection === 'grossPrice') this.renderGrossChart();
                        if (this.activeSection === 'nettPrice') this.renderNettChart();
                    });
                },

                toggleBucket(key) {
                    this.expanded[key] = !this.expanded[key];
                },

                // PI section helpers
                get piRows() {
                    return (this.allPi[this.currency] || { rows: [] }).rows || [];
                },

                get piByReseller() {
                    return (this.allPi[this.currency] || { byReseller: {} }).byReseller || {};
                },

                totalPis() {
                    return this.piRows.length;
                },

                openPiDetail(row) {
                    this.piDetail = row;
                },

                // Bucket all PI rows for the current currency by commission %.
                // Same brackets as the first analysis: 70-80, 60-69, ..., 0-9.
                piPercentBuckets() {
                    const buckets = [
                        { key: 'b50', label: '50% – 100%', rows: [] },
                        { key: 'b30', label: '30% – 49%',  rows: [] },
                        { key: 'b0',  label: '0% – 29%',   rows: [] },
                    ];
                    const pickKey = (rate) => {
                        if (rate >= 50) return 'b50';
                        if (rate >= 30) return 'b30';
                        return 'b0';
                    };
                    const byKey = Object.fromEntries(buckets.map(b => [b.key, b]));
                    this.piRows.forEach(row => {
                        const rate = row.discount_dealer !== null ? Number(row.discount_dealer) : 0;
                        byKey[pickKey(rate)].rows.push(row);
                    });
                    return buckets.map(b => ({ ...b, count: b.rows.length }));
                },

                openPiBucketDrill(bucket) {
                    this.piBucketDrill = bucket;
                    this.piBucketDrillSearch = '';
                    this.piBucketDrillExpanded = {}; // collapsed by default
                },

                filteredPiBucketRows() {
                    if (!this.piBucketDrill) return [];
                    const q = (this.piBucketDrillSearch || '').trim().toLowerCase();
                    if (!q) return this.piBucketDrill.rows;
                    return this.piBucketDrill.rows.filter(r =>
                        (r.invoice_no || '').toLowerCase().includes(q)
                        || (r.reseller_name || '').toLowerCase().includes(q)
                        || (r.subscriber_name || '').toLowerCase().includes(q)
                    );
                },

                // Group the filtered bucket rows by reseller for the slide-over.
                piBucketGroups() {
                    const map = {};
                    for (const row of this.filteredPiBucketRows()) {
                        const key = row.reseller_name || '(Unknown)';
                        if (!map[key]) map[key] = [];
                        map[key].push(row);
                    }
                    return Object.entries(map)
                        .map(([name, rows]) => ({ name, rows, count: rows.length }))
                        .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name));
                },

                togglePiBucketGroup(name) {
                    this.piBucketDrillExpanded[name] = !this.piBucketDrillExpanded[name];
                },

                // Gross Price (Above) helpers — bucketed by per-user price.
                get grossRows() { return (this.allGross[this.currency] || { rows: [] }).rows || []; },
                get grossByReseller() { return (this.allGross[this.currency] || { byReseller: {} }).byReseller || {}; },
                grossTotalPis() { return this.grossRows.length; },

                // Fixed buckets per currency.
                //   MYR: RM3.01–RM3.99, RM4.00–RM4.99, RM5.00–RM5.99
                //   USD: USD1.01–USD1.99, USD2.00–USD2.99, USD3.00–USD5.99
                grossPriceBuckets() {
                    const prefix = this.currency === 'MYR' ? 'RM' : 'USD';
                    const fmt = (v) => prefix + v.toFixed(2);
                    const ranges = this.currency === 'MYR'
                        ? [[3.01, 3.99], [4.00, 4.99], [5.00, 5.99]]
                        : [[1.01, 1.99], [2.00, 2.99], [3.00, 5.99]];
                    const buckets = ranges.map(([min, max], i) => ({
                        key: 'b' + i,
                        min,
                        max,
                        label: `${fmt(min)} – ${fmt(max)}`,
                        price: min,
                        rows: [],
                    }));
                    for (const row of this.grossRows) {
                        const price = Number(row.max_price || 0);
                        for (const b of buckets) {
                            if (price >= b.min && price <= b.max) { b.rows.push(row); break; }
                        }
                    }
                    return buckets.map(b => ({ ...b, count: b.rows.length }));
                },

                openGrossDrill(bucket) {
                    this.grossDrill = bucket;
                    this.grossDrillSearch = '';
                    this.grossDrillExpanded = {}; // collapsed by default
                },

                filteredGrossDrillRows() {
                    if (!this.grossDrill) return [];
                    const q = (this.grossDrillSearch || '').trim().toLowerCase();
                    if (!q) return this.grossDrill.rows;
                    return this.grossDrill.rows.filter(r =>
                        (r.invoice_no || '').toLowerCase().includes(q)
                        || (r.reseller_name || '').toLowerCase().includes(q)
                        || (r.subscriber_name || '').toLowerCase().includes(q)
                    );
                },

                // Group rows in the drill-down by reseller for the slide-over.
                grossDrillGroups() {
                    const map = {};
                    for (const row of this.filteredGrossDrillRows()) {
                        const key = row.reseller_name || '(Unknown)';
                        if (!map[key]) map[key] = [];
                        map[key].push(row);
                    }
                    return Object.entries(map)
                        .map(([name, rows]) => ({ name, rows, count: rows.length }))
                        .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name));
                },

                toggleGrossDrillGroup(name) {
                    this.grossDrillExpanded[name] = !this.grossDrillExpanded[name];
                },

                setGrossView(view) {
                    if (this.grossView === view) return;
                    this.grossView = view;
                    if (this.grossChart) { this.grossChart.destroy(); this.grossChart = null; }
                    this.$nextTick(() => this.renderGrossChart());
                },

                renderGrossChart() {
                    const canvas = document.getElementById('grossPriceBarChart');
                    if (!canvas) return;
                    const self = this;

                    let labels, data, colors, indexAxis, onClickHandler, tooltipFmt;

                    if (this.grossView === 'price') {
                        // Vertical bar chart, x-axis = distinct prices ascending — gradient by price.
                        const buckets = this.grossPriceBuckets();
                        labels = buckets.map(b => b.label);
                        data = buckets.map(b => b.count);
                        colors = this.gradientColors(buckets.map(b => b.price));
                        indexAxis = 'y';
                        tooltipFmt = (ctx) => `${ctx.parsed.x} PI(s) — click for details`;
                        onClickHandler = (idx) => {
                            const b = buckets[idx];
                            if (b && b.count > 0) self.openGrossDrill({ kind: 'price', label: b.label, count: b.count, rows: b.rows });
                        };
                    } else {
                        // Horizontal bar chart, y-axis = reseller names — gradient by PI count.
                        const entries = Object.entries(this.grossByReseller);
                        labels = entries.map(([n]) => n);
                        data = entries.map(([, c]) => c);
                        colors = this.gradientColors(data);
                        indexAxis = 'y';
                        tooltipFmt = (ctx) => `${ctx.parsed.x} PI(s) — click for details`;
                        onClickHandler = (idx) => {
                            const name = labels[idx];
                            if (!name) return;
                            const rows = self.grossRows.filter(r => r.reseller_name === name);
                            self.openGrossDrill({ kind: 'reseller', label: name, count: rows.length, rows });
                        };
                    }

                    if (this.grossChart && this.grossChart.canvas !== canvas) {
                        this.grossChart.destroy();
                        this.grossChart = null;
                    }
                    if (this.grossChart) {
                        this.grossChart.data.labels = labels;
                        this.grossChart.data.datasets[0].data = data;
                        this.grossChart.data.datasets[0].backgroundColor = colors;
                        this.grossChart.update();
                        return;
                    }

                    this.grossChart = new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'PIs',
                                data,
                                backgroundColor: colors,
                                borderRadius: 6,
                                maxBarThickness: this.grossView === 'price' ? 64 : 38,
                            }],
                        },
                        options: {
                            indexAxis,
                            responsive: true,
                            maintainAspectRatio: false,
                            onClick: (evt, elements) => {
                                if (!elements || elements.length === 0) return;
                                onClickHandler(elements[0].index);
                            },
                            onHover: (evt, elements) => {
                                if (evt.native && evt.native.target) {
                                    evt.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: tooltipFmt } },
                            },
                            scales: this.grossView === 'price'
                                ? {
                                    x: { beginAtZero: true, ticks: { precision: 0, maxTicksLimit: 8 }, grid: { color: '#f3f4f6' } },
                                    y: { grid: { display: false }, ticks: { font: { weight: '600', size: 11 } } },
                                  }
                                : {
                                    x: { beginAtZero: true, ticks: { precision: 0, maxTicksLimit: 10 }, grid: { color: '#f3f4f6' } },
                                    y: { grid: { display: false }, ticks: { font: { size: 11 } } },
                                  },
                        },
                    });
                },

                // Nett Price (Below) helpers — analogous to gross.
                get nettRows() { return (this.allNett[this.currency] || { rows: [] }).rows || []; },
                get nettByReseller() { return (this.allNett[this.currency] || { byReseller: {} }).byReseller || {}; },
                nettTotalPis() { return this.nettRows.length; },

                nettPriceBuckets() {
                    const prefix = this.currency === 'MYR' ? 'RM' : 'USD';
                    const groups = {};
                    for (const row of this.nettRows) {
                        const price = Number(row.min_price || 0);
                        const key = price.toFixed(2);
                        if (!groups[key]) groups[key] = { key, price, label: prefix + price.toFixed(2), rows: [] };
                        groups[key].rows.push(row);
                    }
                    return Object.values(groups)
                        .sort((a, b) => a.price - b.price)
                        .map(b => ({ ...b, count: b.rows.length }));
                },

                openNettDrill(payload) {
                    this.nettDrill = payload;
                    this.nettDrillSearch = '';
                    this.nettDrillExpanded = {};
                },

                filteredNettDrillRows() {
                    if (!this.nettDrill) return [];
                    const q = (this.nettDrillSearch || '').trim().toLowerCase();
                    if (!q) return this.nettDrill.rows;
                    return this.nettDrill.rows.filter(r =>
                        (r.invoice_no || '').toLowerCase().includes(q)
                        || (r.reseller_name || '').toLowerCase().includes(q)
                        || (r.subscriber_name || '').toLowerCase().includes(q)
                    );
                },

                nettDrillGroups() {
                    const map = {};
                    for (const row of this.filteredNettDrillRows()) {
                        const key = row.reseller_name || '(Unknown)';
                        if (!map[key]) map[key] = [];
                        map[key].push(row);
                    }
                    return Object.entries(map)
                        .map(([name, rows]) => ({ name, rows, count: rows.length }))
                        .sort((a, b) => b.count - a.count || a.name.localeCompare(b.name));
                },

                toggleNettDrillGroup(name) {
                    this.nettDrillExpanded[name] = !this.nettDrillExpanded[name];
                },

                setNettView(view) {
                    if (this.nettView === view) return;
                    this.nettView = view;
                    if (this.nettChart) { this.nettChart.destroy(); this.nettChart = null; }
                    this.$nextTick(() => this.renderNettChart());
                },

                renderNettChart() {
                    const canvas = document.getElementById('nettPriceBarChart');
                    if (!canvas) return;
                    const self = this;

                    let labels, data, colors, indexAxis, onClickHandler, tooltipFmt;

                    if (this.nettView === 'price') {
                        const buckets = this.nettPriceBuckets();
                        labels = buckets.map(b => b.label);
                        data = buckets.map(b => b.count);
                        colors = this.gradientColors(buckets.map(b => b.price));
                        indexAxis = 'x';
                        tooltipFmt = (ctx) => `${ctx.parsed.y} PI(s) — click for details`;
                        onClickHandler = (idx) => {
                            const b = buckets[idx];
                            if (b && b.count > 0) self.openNettDrill({ kind: 'price', label: b.label, count: b.count, rows: b.rows });
                        };
                    } else {
                        const entries = Object.entries(this.nettByReseller);
                        labels = entries.map(([n]) => n);
                        data = entries.map(([, c]) => c);
                        colors = this.gradientColors(data);
                        indexAxis = 'y';
                        tooltipFmt = (ctx) => `${ctx.parsed.x} PI(s) — click for details`;
                        onClickHandler = (idx) => {
                            const name = labels[idx];
                            if (!name) return;
                            const rows = self.nettRows.filter(r => r.reseller_name === name);
                            self.openNettDrill({ kind: 'reseller', label: name, count: rows.length, rows });
                        };
                    }

                    if (this.nettChart && this.nettChart.canvas !== canvas) {
                        this.nettChart.destroy();
                        this.nettChart = null;
                    }
                    if (this.nettChart) {
                        this.nettChart.data.labels = labels;
                        this.nettChart.data.datasets[0].data = data;
                        this.nettChart.data.datasets[0].backgroundColor = colors;
                        this.nettChart.update();
                        return;
                    }

                    this.nettChart = new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'PIs',
                                data,
                                backgroundColor: colors,
                                borderRadius: 6,
                                maxBarThickness: this.nettView === 'price' ? 64 : 38,
                            }],
                        },
                        options: {
                            indexAxis,
                            responsive: true,
                            maintainAspectRatio: false,
                            onClick: (evt, elements) => {
                                if (!elements || elements.length === 0) return;
                                onClickHandler(elements[0].index);
                            },
                            onHover: (evt, elements) => {
                                if (evt.native && evt.native.target) {
                                    evt.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: tooltipFmt } },
                            },
                            scales: this.nettView === 'price'
                                ? {
                                    x: { grid: { display: false }, ticks: { font: { weight: '600', size: 11 } } },
                                    y: { beginAtZero: true, ticks: { precision: 0, maxTicksLimit: 8 }, grid: { color: '#f3f4f6' } },
                                  }
                                : {
                                    x: { beginAtZero: true, ticks: { precision: 0, maxTicksLimit: 10 }, grid: { color: '#f3f4f6' } },
                                    y: { grid: { display: false }, ticks: { font: { size: 11 } } },
                                  },
                        },
                    });
                },

                renderPiChart() {
                    const canvas = document.getElementById('piResellerBarChart');
                    if (!canvas) return;

                    const buckets = this.piPercentBuckets();
                    const labels = buckets.map(b => b.label);
                    const data = buckets.map(b => b.count);
                    const bracketLow = (k) => k === 'b0' ? 0 : parseInt(k.slice(1), 10);
                    const colors = this.gradientColors(buckets.map(b => bracketLow(b.key)));
                    const self = this;

                    if (this.piChart && this.piChart.canvas !== canvas) {
                        this.piChart.destroy();
                        this.piChart = null;
                    }

                    if (this.piChart) {
                        this.piChart.data.labels = labels;
                        this.piChart.data.datasets[0].data = data;
                        this.piChart.data.datasets[0].backgroundColor = colors.slice(0, labels.length);
                        this.piChart.update();
                        return;
                    }

                    this.piChart = new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'PIs',
                                data,
                                backgroundColor: colors.slice(0, labels.length),
                                borderRadius: 6,
                                maxBarThickness: 64,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            onClick: (evt, elements) => {
                                if (!elements || elements.length === 0) return;
                                const idx = elements[0].index;
                                const bucket = self.piPercentBuckets()[idx];
                                if (bucket && bucket.count > 0) self.openPiBucketDrill(bucket);
                            },
                            onHover: (evt, elements) => {
                                if (evt.native && evt.native.target) {
                                    evt.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                                }
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: { callbacks: { label: (ctx) => `${ctx.parsed.x} PI(s) — click for details` } },
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    ticks: { precision: 0, stepSize: 1 },
                                    grid: { color: '#f3f4f6' },
                                },
                                y: { grid: { display: false }, ticks: { font: { weight: '600' } } },
                            },
                        },
                    });
                },

                // Bar-chart drill-down: open slide-over with resellers in clicked bucket
                openBucketDrill(bucket) {
                    this.bucketDrill = bucket;
                    this.bucketDrillSearch = '';
                },

                filteredBucketResellers() {
                    if (!this.bucketDrill) return [];
                    const q = (this.bucketDrillSearch || '').trim().toLowerCase();
                    if (!q) return this.bucketDrill.resellers;
                    return this.bucketDrill.resellers.filter(r =>
                        (r.reseller_name || '').toLowerCase().includes(q)
                    );
                },

                renderChart() {
                    const canvas = document.getElementById('commissionBarChart');
                    if (!canvas) return;

                    const labels = this.buckets.map(b => b.label);
                    const data = this.buckets.map(b => b.count);
                    // Color by % bracket lower bound (low blue -> high red).
                    const bracketLow = (k) => k === 'b0' ? 0 : parseInt(k.slice(1), 10);
                    const colors = this.gradientColors(this.buckets.map(b => bracketLow(b.key)));
                    const self = this;

                    // If we have a chart but its canvas was removed from the DOM
                    // (e.g. user navigated to another section and back), tear it down.
                    if (this.chart && this.chart.canvas !== canvas) {
                        this.chart.destroy();
                        this.chart = null;
                    }

                    if (this.chart) {
                        this.chart.data.labels = labels;
                        this.chart.data.datasets[0].data = data;
                        this.chart.data.datasets[0].backgroundColor = colors.slice(0, labels.length);
                        this.chart.update();
                        return;
                    }

                    this.chart = new Chart(canvas.getContext('2d'), {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [{
                                label: 'Resellers',
                                data,
                                backgroundColor: colors.slice(0, labels.length),
                                borderRadius: 6,
                                maxBarThickness: 64,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            onClick: (evt, elements) => {
                                if (!elements || elements.length === 0) return;
                                const idx = elements[0].index;
                                const bucket = self.buckets[idx];
                                if (bucket && bucket.count > 0) self.openBucketDrill(bucket);
                            },
                            onHover: (evt, elements) => {
                                evt.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                            },
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        label: (ctx) => `${ctx.parsed.x} reseller(s) — click for details`,
                                    },
                                },
                            },
                            scales: {
                                x: {
                                    beginAtZero: true,
                                    ticks: { precision: 0, stepSize: 1 },
                                    grid: { color: '#f3f4f6' },
                                },
                                y: { grid: { display: false }, ticks: { font: { weight: '600' } } },
                            },
                        },
                    });
                },
            };
        }
    </script>

    {{-- Forecast Cost Modal — mirrors Termination Analysis (HC × 1 × 12), currency-aware. --}}
    @if($showForecastModal)
    <div wire:click="closeForecastModal"
         style="position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; display:flex; align-items:center; justify-content:center;">
        <div wire:click.stop
             style="background:#fff; border-radius:12px; width:min(460px,92vw); padding:24px; box-shadow:0 20px 50px rgba(0,0,0,0.25);">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:16px;">
                <div>
                    <h3 style="font-size:1rem; font-weight:700; color:#1a56db; margin:0;">
                        Forecast Cost ({{ $forecastData['currency'] ?? 'MYR' }})
                    </h3>
                    <p style="margin:4px 0 0; font-size:0.7rem; color:#9ca3af;">
                        {{ $forecastData['date_from'] ?? '' }} – {{ $forecastData['date_to'] ?? '' }}
                    </p>
                </div>
                <button wire:click="closeForecastModal" style="border:none; background:transparent; cursor:pointer; color:#94a3b8; font-size:1.5rem; line-height:1;">&times;</button>
            </div>

            <div style="display:flex; flex-direction:column; gap:10px; font-size:0.9rem;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#6b7280;">Total Headcount</span>
                    <span style="font-weight:600; color:#1f2937;">{{ number_format($forecastData['headcount'] ?? 0) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#6b7280;">Cost per Headcount</span>
                    <span style="font-weight:600; color:#1f2937;">{{ $forecastData['symbol'] ?? 'RM' }} {{ number_format($forecastData['rate'] ?? 0, 2) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#6b7280;">Months</span>
                    <span style="font-weight:600; color:#1f2937;">{{ $forecastData['months'] ?? 0 }}</span>
                </div>

                {{-- By Module breakdown (donut chart + legend) --}}
                @php
                    $forecastModuleLabels = ['TA' => 'Attendance', 'TL' => 'Leave', 'TC' => 'Claim', 'TP' => 'Payroll'];
                    $forecastModuleColors = ['TA' => '#3b82f6', 'TL' => '#8b5cf6', 'TC' => '#f59e0b', 'TP' => '#10b981'];
                    $forecastSymbol  = $forecastData['symbol'] ?? 'RM';
                    $forecastTotalHc = max(0, (int) ($forecastData['headcount'] ?? 0));
                    $forecastCirc    = 2 * M_PI * 50;
                    $forecastCumulative = 0;
                    $forecastSegments = [];
                    foreach (['TA','TL','TC','TP'] as $mod) {
                        $hc  = (int) ($forecastData['modules'][$mod]['headcount'] ?? 0);
                        $pct = $forecastTotalHc > 0 ? $hc / $forecastTotalHc : 0;
                        $dash = $pct * $forecastCirc;
                        $forecastSegments[$mod] = [
                            'dash'   => $dash,
                            'offset' => -$forecastCumulative,
                            'pct'    => $pct,
                        ];
                        $forecastCumulative += $dash;
                    }
                @endphp
                <div style="margin-top:6px; padding-top:14px; border-top:1px dashed #e5e7eb;">
                    <div style="font-size:0.7rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:12px;">
                        By Module
                    </div>

                    <div style="display:flex; align-items:center; gap:18px;">
                        {{-- Donut chart --}}
                        <div style="position:relative; flex-shrink:0; width:128px; height:128px;">
                            <svg width="128" height="128" viewBox="0 0 120 120" style="display:block;">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#f1f5f9" stroke-width="18"/>
                                <g transform="rotate(-90 60 60)">
                                    @foreach(['TA','TL','TC','TP'] as $mod)
                                        @if($forecastSegments[$mod]['pct'] > 0)
                                            <circle cx="60" cy="60" r="50" fill="none"
                                                stroke="{{ $forecastModuleColors[$mod] }}"
                                                stroke-width="18"
                                                stroke-dasharray="{{ $forecastSegments[$mod]['dash'] }} {{ $forecastCirc - $forecastSegments[$mod]['dash'] }}"
                                                stroke-dashoffset="{{ $forecastSegments[$mod]['offset'] }}"/>
                                        @endif
                                    @endforeach
                                </g>
                            </svg>
                            <div style="position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none;">
                                <span style="font-size:0.55rem; font-weight:700; color:#9ca3af; letter-spacing:0.1em;">HEADCOUNT</span>
                                <span style="font-size:1.1rem; font-weight:800; color:#1f2937; line-height:1.1; margin-top:2px;">{{ number_format($forecastTotalHc) }}</span>
                            </div>
                        </div>

                        {{-- Legend --}}
                        <div style="display:flex; flex-direction:column; gap:9px; flex:1; min-width:0;">
                            @foreach(['TA','TL','TC','TP'] as $mod)
                                <div style="display:flex; align-items:flex-start; gap:8px; font-size:0.8rem;">
                                    <span style="width:9px; height:9px; border-radius:9999px; background:{{ $forecastModuleColors[$mod] }}; margin-top:5px; flex-shrink:0;"></span>
                                    <div style="flex:1; min-width:0;">
                                        <div style="display:flex; justify-content:space-between; align-items:baseline; gap:6px;">
                                            <span style="font-weight:600; color:#1f2937;">{{ $forecastModuleLabels[$mod] }}</span>
                                            <span style="font-weight:600; color:#1f2937; white-space:nowrap;">{{ $forecastSymbol }} {{ number_format($forecastData['modules'][$mod]['cost'] ?? 0, 2) }}</span>
                                        </div>
                                        <div style="font-size:0.7rem; color:#9ca3af; margin-top:1px;">
                                            {{ number_format($forecastData['modules'][$mod]['headcount'] ?? 0) }} HC
                                            @if($forecastTotalHc > 0)
                                                <span style="color:#cbd5e1;">·</span> {{ number_format($forecastSegments[$mod]['pct'] * 100, 1) }}%
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <hr style="border:none; border-top:1px solid #e5e7eb; margin:6px 0;">
                <div style="display:flex; justify-content:space-between; font-size:1rem;">
                    <span style="font-weight:700; color:#1f2937;">Total Forecast Cost</span>
                    <span style="font-weight:700; color:#2563eb;">{{ $forecastSymbol }} {{ number_format($forecastData['total'] ?? 0, 2) }}</span>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                <button wire:click="closeForecastModal"
                    style="padding:8px 16px; border:none; border-radius:8px; background:#1f2937; color:#fff; font-size:0.85rem; font-weight:600; cursor:pointer;">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif
</x-filament-panels::page>
