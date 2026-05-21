<x-filament::page>
    <head>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <style>
            .wrapper-container {
                background-color: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
                width: 100%;
                margin-bottom: 20px;
            }

            .chart-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }

            .chart-grid .full-width-chart {
                grid-column: span 2;
            }

            @media (max-width: 768px) {
                .chart-grid {
                    grid-template-columns: 1fr;
                }
                .chart-grid .full-width-chart {
                    grid-column: span 1;
                }
            }

            .chart-container {
                background: white;
                border-radius: 10px;
                padding: 20px;
                box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            }

            .chart-title {
                font-size: 1rem;
                font-weight: 600;
                color: #374151;
                margin-bottom: 16px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            /* Donut Chart Styles */
            .donut-chart-wrapper {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 20px;
                width: 100%;
                padding: 10px;
            }

            .donut-chart {
                position: relative;
                width: 180px;
                height: 180px;
                flex-shrink: 0;
            }

            .donut-legend {
                display: flex;
                flex-direction: column;
                gap: 8px;
                flex: 1;
                max-height: 220px;
                overflow-y: auto;
            }

            .legend-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 6px 10px;
                border-radius: 6px;
                cursor: pointer;
                transition: background-color 0.2s;
            }

            .legend-item:hover {
                background-color: #F3F4F6;
            }

            .legend-color {
                width: 12px;
                height: 12px;
                border-radius: 3px;
            }

            .legend-text {
                font-size: 0.875rem;
                color: #374151;
                flex: 1;
            }

            .legend-count {
                font-weight: 600;
                color: #1F2937;
                margin-left: auto;
            }

            .legend-count {
                font-weight: 600;
                margin-left: auto;
            }

            /* Bar Chart Styles */
            .bar-chart {
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .bar-item {
                cursor: pointer;
                transition: transform 0.2s;
            }

            .bar-item:hover {
                transform: translateX(4px);
            }

            .bar-label {
                display: flex;
                justify-content: space-between;
                font-size: 0.875rem;
                color: #374151;
                margin-bottom: 4px;
            }

            .bar-wrapper {
                height: 24px;
                background: #E5E7EB;
                border-radius: 6px;
                overflow: hidden;
            }

            .bar-fill {
                height: 100%;
                border-radius: 6px;
                transition: width 0.5s ease-in-out;
            }

            /* Stacked Bar Styles */
            .bar-item-stacked {
                margin-bottom: 12px;
            }

            .stacked-bar {
                display: flex;
                cursor: pointer;
            }

            .bar-segment {
                height: 100%;
                transition: all 0.3s ease;
            }

            .bar-segment:first-child {
                border-radius: 6px 0 0 6px;
            }

            .bar-segment:last-child {
                border-radius: 0 6px 6px 0;
            }

            .bar-segment:only-child {
                border-radius: 6px;
            }

            .bar-segment:hover {
                opacity: 0.8;
                transform: scaleY(1.1);
            }

            .priority-breakdown {
                margin-top: 8px;
                margin-left: 24px;
                padding: 8px 12px;
                background: #F9FAFB;
                border-radius: 6px;
                font-size: 0.8rem;
            }

            .breakdown-item {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 4px 0;
            }

            .breakdown-color {
                width: 12px;
                height: 12px;
                border-radius: 3px;
                flex-shrink: 0;
            }

            .breakdown-name {
                flex: 1;
                color: #374151;
            }

            .breakdown-count {
                font-weight: 600;
                color: #1F2937;
            }

            .priority-legend {
                font-size: 0.75rem;
            }

            /* Priority List Styles (By Priority section) */
            .priority-list {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .priority-item {
                border: 1px solid #E5E7EB;
                border-radius: 8px;
                overflow: hidden;
            }

            .priority-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 16px;
                background: #F9FAFB;
                transition: background-color 0.2s;
            }

            .priority-header:hover {
                background: #F3F4F6;
            }

            .module-breakdown {
                border-top: 1px solid #E5E7EB;
                background: white;
            }

            .module-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 16px 10px 40px;
                border-bottom: 1px solid #F3F4F6;
            }

            .module-item:last-child {
                border-bottom: none;
            }

            /* Line Chart Styles */
            .line-chart-container {
                width: 100%;
                padding: 20px 0;
            }

            .line-chart-svg {
                width: 100%;
                height: 200px;
            }

            /* Full Width Chart */
            .full-width-chart {
                grid-column: span 2;
            }

            /* Slide Over Styles */
            .slide-over-item {
                display: block;
                padding: 12px 16px;
                margin-bottom: 8px;
                background: #F9FAFB;
                border-radius: 8px;
                border: 1px solid #E5E7EB;
                transition: all 0.2s;
            }

            .slide-over-item:hover {
                background: #EFF6FF;
                border-color: #3B82F6;
            }

            .ticket-id {
                font-weight: 600;
                color: #3B82F6;
            }

            .ticket-title {
                font-size: 0.875rem;
                color: #374151;
                margin-top: 4px;
            }

            .ticket-meta {
                font-size: 0.75rem;
                color: #6B7280;
                margin-top: 4px;
            }

            @media (max-width: 768px) {
                .summary-grid {
                    grid-template-columns: repeat(2, 1fr);
                }
                .chart-grid {
                    grid-template-columns: 1fr;
                }
                .full-width-chart {
                    grid-column: span 1;
                }
            }

            /* Filter Mode Toggle */
            .filter-toggle {
                display: flex;
                align-items: center;
                border-radius: 8px;
                background-color: #EFF6FF;
                border: 1px solid #BFDBFE;
                overflow: hidden;
            }

            .filter-toggle-btn {
                padding: 6px 12px;
                font-size: 0.875rem;
                font-weight: 500;
                cursor: pointer;
                border: none;
                background: transparent;
                color: #2563EB;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .filter-toggle-btn:hover {
                background-color: #DBEAFE;
            }

            .filter-toggle-btn.active {
                background-color: #3B82F6;
                color: #ffffff;
            }

            .filter-toggle-btn.active:hover {
                background-color: #2563EB;
            }

            .filter-disabled {
                opacity: 0.4;
                pointer-events: none;
            }

            /* Tab Styles */
            .analysis-tab-container {
                display: flex;
                justify-content: flex-start;
                gap: 8px;
                margin-bottom: 20px;
            }

            .analysis-tab-btn {
                display: inline-flex;
                align-items: center;
                padding: 8px 18px;
                font-size: 0.875rem;
                font-weight: 600;
                border: 2px solid transparent;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.2s;
            }

            .analysis-tab-btn.active {
                background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
                border-color: #2563eb;
                color: white;
                box-shadow: 0 4px 12px rgba(59,130,246,0.3);
            }

            .analysis-tab-btn.inactive {
                background: white;
                border-color: #d1d5db;
                color: #374151;
            }

            .analysis-tab-btn.inactive:hover {
                background: #f9fafb;
                border-color: #9ca3af;
            }

            /* Analysis Table Styles */
            .analysis-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 0.875rem;
            }

            .analysis-table th {
                background: #f3f4f6;
                padding: 10px 12px;
                text-align: left;
                font-weight: 600;
                color: #374151;
                border-bottom: 2px solid #e5e7eb;
            }

            .analysis-table th.count-cell {
                text-align: center;
            }

            .analysis-table td {
                padding: 8px 12px;
                border-bottom: 1px solid #e5e7eb;
                color: #4b5563;
            }

            .analysis-table tr:hover {
                background-color: #f9fafb;
            }

            .analysis-table .count-cell {
                text-align: center;
                font-weight: 500;
            }

            .analysis-table .count-cell.has-value {
                color: #1f2937;
            }

            .analysis-table .count-cell.zero {
                color: #d1d5db;
            }

            .analysis-table tfoot td {
                font-weight: 700;
                background: #f3f4f6;
                border-top: 2px solid #e5e7eb;
            }

            /* Status-colored table headers */
            .analysis-table th.status-new {
                background: #EFF6FF;
                color: #2563EB;
                border-bottom-color: #93C5FD;
            }
            .analysis-table th.status-inprogress {
                background: #FFFBEB;
                color: #D97706;
                border-bottom-color: #FCD34D;
            }
            .analysis-table th.status-completed {
                background: #ECFEFF;
                color: #0891B2;
                border-bottom-color: #67E8F9;
            }
            .analysis-table th.status-closed {
                background: #ECFDF5;
                color: #059669;
                border-bottom-color: #6EE7B7;
            }
            .analysis-table th.status-closedconfig {
                background: #F5F3FF;
                color: #7C3AED;
                border-bottom-color: #C4B5FD;
            }
            .analysis-table th.status-closedcr {
                background: #EEF2FF;
                color: #4F46E5;
                border-bottom-color: #A5B4FC;
            }

            /* Status-colored cell values */
            .status-new-cell { color: #2563EB !important; font-weight: 600; }
            .status-inprogress-cell { color: #D97706 !important; font-weight: 600; }
            .status-completed-cell { color: #0891B2 !important; font-weight: 600; }
            .status-closed-cell { color: #059669 !important; font-weight: 600; }
            .status-closedconfig-cell { color: #7C3AED !important; font-weight: 600; }
            .status-closedcr-cell { color: #4F46E5 !important; font-weight: 600; }

            .status-summary-badge {
                font-size: 0.7rem;
                font-weight: 600;
                padding: 2px 8px;
                border-radius: 9999px;
                white-space: nowrap;
            }

            /* Assignee Section Styles */
            .assignee-section {
                margin-bottom: 16px;
            }

            .assignee-header-bar {
                background: #eef2ff;
                padding: 12px 16px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                cursor: pointer;
                transition: background 0.2s;
            }

            .assignee-header-bar:hover {
                background: #e0e7ff;
            }

            .assignee-header-bar h3 {
                font-weight: 700;
                color: #3730a3;
                font-size: 1rem;
                margin: 0;
            }

            .assignee-badge {
                background: #c7d2fe;
                color: #3730a3;
                padding: 2px 10px;
                border-radius: 999px;
                font-size: 0.8rem;
                font-weight: 600;
            }
        </style>
    </head>

    <!-- Header with Filters -->
    <div class="flex flex-col items-center justify-between mb-6 md:flex-row">
        <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white sm:text-3xl">Ticket Analysis</h1>

        <div class="flex flex-wrap items-center gap-3 mt-4 md:mt-0">
            <!-- All Tickets Toggle -->
            <label class="flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                <input type="checkbox" wire:model="showAllTickets" wire:change="applyFilters" class="text-blue-600 border-gray-300 rounded shadow-sm focus:ring-blue-500">
                <span class="text-sm font-medium text-gray-600">All Tickets</span>
            </label>

            <!-- Divider -->
            <div class="hidden w-px h-8 bg-gray-300 md:block"></div>

            <!-- Filter Mode Toggle -->
            <div class="filter-toggle {{ $showAllTickets ? 'filter-disabled' : '' }}">
                <button
                    wire:click="$set('filterMode', 'month')"
                    class="filter-toggle-btn {{ $filterMode === 'month' ? 'active' : '' }}"
                    {{ $showAllTickets ? 'disabled' : '' }}
                >
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Month
                </button>
                <button
                    wire:click="$set('filterMode', 'range')"
                    class="filter-toggle-btn {{ $filterMode === 'range' ? 'active' : '' }}"
                    {{ $showAllTickets ? 'disabled' : '' }}
                >
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12M8 12h8m-8 5h4M4 7h.01M4 12h.01M4 17h.01"/></svg>
                    Range
                </button>
            </div>

            @if($filterMode === 'month')
                <!-- Month/Year Filter -->
                <div class="flex items-center gap-2 {{ $showAllTickets ? 'opacity-40 pointer-events-none' : '' }}">
                    <select wire:model="selectedMonth" wire:change="applyFilters" class="py-1.5 text-sm font-medium border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" {{ $showAllTickets ? 'disabled' : '' }}>
                        @foreach($availableMonths as $month)
                            <option value="{{ $month['value'] }}">{{ $month['label'] }}</option>
                        @endforeach
                    </select>
                    <select wire:model="selectedYear" wire:change="applyFilters" class="py-1.5 text-sm font-medium border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" {{ $showAllTickets ? 'disabled' : '' }}>
                        @foreach($availableYears as $y)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <!-- Date Range Filters -->
                <div class="flex items-center gap-2 {{ $showAllTickets ? 'opacity-40 pointer-events-none' : '' }}">
                    <label class="text-sm font-medium text-gray-500">From</label>
                    <input type="date" wire:model="startDate" wire:change="applyFilters" class="py-1.5 text-sm border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" {{ $showAllTickets ? 'disabled' : '' }}>
                </div>
                <div class="flex items-center gap-2 {{ $showAllTickets ? 'opacity-40 pointer-events-none' : '' }}">
                    <label class="text-sm font-medium text-gray-500">To</label>
                    <input type="date" wire:model="endDate" wire:change="applyFilters" class="py-1.5 text-sm border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500" {{ $showAllTickets ? 'disabled' : '' }}>
                </div>
            @endif

            <!-- Divider -->
            <div class="hidden w-px h-8 bg-gray-300 md:block"></div>

            <!-- Product Filter -->
            <select wire:model="selectedProduct" wire:change="applyFilters" class="py-1.5 text-sm font-medium border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="v1">Version 1</option>
                <option value="v2">Version 2</option>
            </select>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="analysis-tab-container">
        <button
            wire:click="switchTab('all')"
            class="analysis-tab-btn {{ $activeTab === 'all' ? 'active' : 'inactive' }}"
        >
            <i class="mr-2 fa fa-chart-pie"></i> All
        </button>
        <button
            wire:click="switchTab('rnd')"
            class="analysis-tab-btn {{ $activeTab === 'rnd' ? 'active' : 'inactive' }}"
        >
            P1 & P2 Rnd
        </button>
        <button
            wire:click="switchTab('fe')"
            class="analysis-tab-btn {{ $activeTab === 'fe' ? 'active' : 'inactive' }}"
        >
            P1 & P2 FE
        </button>
    </div>

    @if($activeTab === 'all')
    <!-- Charts Grid -->
    <div class="chart-grid">
        <!-- Priority Distribution (Donut Chart) -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="text-gray-500 fa fa-chart-pie"></i>
                <span>By Priority</span>
            </div>

            @if(count($priorityData) > 0)
                <div class="donut-chart-wrapper">
                    <!-- SVG Donut Chart -->
                    <div class="donut-chart">
                        <svg viewBox="0 0 36 36" width="180" height="180">
                            @php
                                $colors = ['#EF4444', '#F59E0B', '#3B82F6', '#10B981', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6'];
                                $offset = 0;
                                $total = collect($priorityData)->sum('count');
                            @endphp

                            <!-- Background circle -->
                            <circle cx="18" cy="18" r="14" fill="none" stroke="#E5E7EB" stroke-width="5"></circle>

                            @foreach($priorityData as $index => $item)
                                @php
                                    $percentage = $total > 0 ? ($item['count'] / $total) * 100 : 0;
                                    $dashArray = ($percentage * 88) / 100;
                                    $color = $colors[$index % count($colors)];
                                @endphp
                                <circle
                                    cx="18" cy="18" r="14"
                                    fill="none"
                                    stroke="{{ $color }}"
                                    stroke-width="5"
                                    stroke-dasharray="{{ $dashArray }} {{ 88 - $dashArray }}"
                                    stroke-dashoffset="{{ -$offset }}"
                                    transform="rotate(-90 18 18)"
                                    class="cursor-pointer hover:opacity-80"
                                    wire:click="openPrioritySlideOver({{ $item['id'] }})"
                                >
                                    <title>{{ $item['name'] }}: {{ $item['count'] }} ({{ number_format($percentage, 1) }}%)</title>
                                </circle>
                                @php $offset += $dashArray; @endphp
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-xl font-bold text-gray-700">{{ $totalTickets }}</span>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="donut-legend">
                        @foreach($priorityData as $index => $item)
                            @php
                                $color = $colors[$index % count($colors)];
                                $legendPercentage = $total > 0 ? ($item['count'] / $total) * 100 : 0;
                            @endphp
                            <div class="legend-item" wire:click="openPrioritySlideOver({{ $item['id'] }})" title="{{ $item['name'] }}: {{ $item['count'] }} ({{ number_format($legendPercentage, 1) }}%)">
                                <span class="legend-color" style="background-color: {{ $color }};"></span>
                                <span class="legend-text">{{ \Illuminate\Support\Str::limit($item['name'], 20) }}</span>
                                <span class="legend-count">{{ $item['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex items-center justify-center h-48 text-gray-500">
                    No priority data available
                </div>
            @endif
        </div>

        <!-- Module Distribution (Donut Chart) -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="text-gray-500 fa fa-chart-pie"></i>
                <span>By Module</span>
            </div>

            @if(count($moduleData) > 0)
                <div class="donut-chart-wrapper">
                    <!-- SVG Donut Chart -->
                    <div class="donut-chart">
                        <svg viewBox="0 0 36 36" width="180" height="180">
                            @php
                                $moduleColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#06B6D4'];
                                $moduleOffset = 0;
                                $moduleTotal = collect($moduleData)->sum('count');
                            @endphp

                            <!-- Background circle -->
                            <circle cx="18" cy="18" r="14" fill="none" stroke="#E5E7EB" stroke-width="5"></circle>

                            @foreach($moduleData as $index => $item)
                                @php
                                    $modulePercentage = $moduleTotal > 0 ? ($item['count'] / $moduleTotal) * 100 : 0;
                                    $moduleDashArray = ($modulePercentage * 88) / 100;
                                    $moduleColor = $moduleColors[$index % count($moduleColors)];
                                @endphp
                                <circle
                                    cx="18" cy="18" r="14"
                                    fill="none"
                                    stroke="{{ $moduleColor }}"
                                    stroke-width="5"
                                    stroke-dasharray="{{ $moduleDashArray }} {{ 88 - $moduleDashArray }}"
                                    stroke-dashoffset="{{ -$moduleOffset }}"
                                    transform="rotate(-90 18 18)"
                                    class="cursor-pointer hover:opacity-80"
                                    wire:click="openModuleSlideOver({{ $item['id'] }})"
                                >
                                    <title>{{ $item['name'] }}: {{ $item['count'] }} ({{ number_format($modulePercentage, 1) }}%)</title>
                                </circle>
                                @php $moduleOffset += $moduleDashArray; @endphp
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span class="text-xl font-bold text-gray-700">{{ $moduleTotal }}</span>
                        </div>
                    </div>

                    <!-- Legend -->
                    <div class="donut-legend">
                        @foreach($moduleData as $index => $item)
                            @php
                                $moduleColor = $moduleColors[$index % count($moduleColors)];
                                $moduleLegendPercentage = $moduleTotal > 0 ? ($item['count'] / $moduleTotal) * 100 : 0;
                            @endphp
                            <div class="legend-item" wire:click="openModuleSlideOver({{ $item['id'] }})" title="{{ $item['name'] }}: {{ $item['count'] }} ({{ number_format($moduleLegendPercentage, 1) }}%)">
                                <span class="legend-color" style="background-color: {{ $moduleColor }};"></span>
                                <span class="legend-text">{{ \Illuminate\Support\Str::limit($item['name'], 18) }}</span>
                                <span class="legend-count">{{ $item['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex items-center justify-center h-48 text-gray-500">
                    No module data available
                </div>
            @endif
        </div>

        <!-- Priority Distribution with Module Breakdown -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="text-gray-500 fa fa-list-alt"></i>
                <span>By Priority</span>
            </div>

            @if(count($priorityModuleData) > 0)
                @php
                    $priorityColors = ['#EF4444', '#F59E0B', '#3B82F6', '#10B981', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6'];
                @endphp
                <div class="priority-list">
                    @foreach($priorityModuleData as $index => $item)
                        @php $priorityColor = $priorityColors[$index % count($priorityColors)]; @endphp
                        <div class="priority-item" x-data="{ showBreakdown: false }" style="border-left: 4px solid {{ $priorityColor }};">
                            <div class="cursor-pointer priority-header" @click="showBreakdown = !showBreakdown">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="showBreakdown ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span class="font-medium text-gray-700">{{ $item['name'] }}</span>
                                </span>
                                <span class="font-semibold text-gray-900">{{ $item['count'] }}</span>
                            </div>
                            <!-- Module Breakdown Details (expandable) -->
                            <div x-show="showBreakdown" x-collapse class="module-breakdown">
                                @if(!empty($item['breakdown']))
                                    @foreach($item['breakdown'] as $module)
                                        <div class="transition-colors cursor-pointer module-item hover:bg-gray-100"
                                             wire:click="openPriorityBarSlideOver({{ $item['id'] }}, {{ $module['module_id'] }})">
                                            <span class="flex items-center gap-2">
                                                <span class="flex-shrink-0 w-3 h-3 rounded-sm" style="background-color: {{ $module['color'] }};"></span>
                                                <span class="text-gray-600">{{ $module['name'] }}</span>
                                            </span>
                                            <span class="flex items-center gap-2">
                                                <span class="font-semibold text-gray-800">{{ $module['count'] }}</span>
                                                <span class="text-sm text-gray-500">({{ $module['percentage'] }}%)</span>
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

            @else
                <div class="flex items-center justify-center h-48 text-gray-500">
                    No priority data available
                </div>
            @endif
        </div>

        <!-- Module Distribution with Priority Breakdown (List) -->
        <div class="chart-container">
            <div class="chart-title">
                <i class="text-gray-500 fa fa-list-alt"></i>
                <span>By Module</span>
            </div>

            @if(count($moduleData) > 0)
                @php
                    $moduleColors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#06B6D4'];
                @endphp
                <div class="priority-list">
                    @foreach($moduleData as $index => $item)
                        @php $moduleColor = $moduleColors[$index % count($moduleColors)]; @endphp
                        <div class="priority-item" x-data="{ showBreakdown: false }" style="border-left: 4px solid {{ $moduleColor }};">
                            <div class="cursor-pointer priority-header" @click="showBreakdown = !showBreakdown">
                                <span class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-gray-400 transition-transform" :class="showBreakdown ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                    <span class="font-medium text-gray-700">{{ $item['name'] }}</span>
                                </span>
                                <span class="font-semibold text-gray-900">{{ $item['count'] }}</span>
                            </div>
                            <!-- Priority Breakdown Details (expandable) -->
                            <div x-show="showBreakdown" x-collapse class="module-breakdown">
                                @if(!empty($item['breakdown']))
                                    @foreach($item['breakdown'] as $priority)
                                        <div class="transition-colors cursor-pointer module-item hover:bg-gray-100"
                                             wire:click="openModuleBarSlideOver({{ $item['id'] }}, {{ $priority['priority_id'] }})">
                                            <span class="flex items-center gap-2">
                                                <span class="flex-shrink-0 w-3 h-3 rounded-sm" style="background-color: {{ $priority['color'] }};"></span>
                                                <span class="text-gray-600">{{ $priority['name'] }}</span>
                                            </span>
                                            <span class="flex items-center gap-2">
                                                <span class="font-semibold text-gray-800">{{ $priority['count'] }}</span>
                                                <span class="text-sm text-gray-500">({{ $priority['percentage'] }}%)</span>
                                            </span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex items-center justify-center h-48 text-gray-500">
                    No module data available
                </div>
            @endif
        </div>
    </div>

    {{-- Frontend Tickets Section - HIDDEN
    <!-- Frontend Tickets Section -->
    <div class="mt-6 wrapper-container">
        <div class="flex items-center mb-4 space-x-2">
            <i class="text-lg text-blue-500 fa fa-globe"></i>
            <h2 class="text-lg font-bold text-gray-800">Frontend Submitted Tickets</h2>
            <span class="px-3 py-1 text-sm font-medium text-blue-800 bg-blue-100 rounded-full">
                {{ number_format($frontendTotalTickets) }} total
            </span>
        </div>

        @if($frontendTotalTickets > 0)
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- Left: By User -->
                <div class="p-4 rounded-lg bg-gray-50">
                    <h3 class="flex items-center gap-2 mb-4 font-semibold text-gray-700">
                        <i class="text-gray-400 fa fa-user"></i>
                        By User (Top 10)
                    </h3>
                    <div class="space-y-3">
                        @foreach($frontendUserData as $user)
                            @php
                                $percentage = $frontendTotalTickets > 0 ? ($user['count'] / $frontendTotalTickets) * 100 : 0;
                                $isSelected = $selectedFrontendUserId === $user['id'];
                            @endphp
                            <div class="cursor-pointer rounded p-2 transition-colors {{ $isSelected ? 'bg-indigo-100 ring-2 ring-indigo-400' : 'hover:bg-white' }}" wire:click="selectFrontendUser({{ $user['id'] }})">
                                <div class="flex justify-between mb-1 text-sm">
                                    <span class="{{ $isSelected ? 'text-indigo-700 font-semibold' : 'text-gray-600' }}">{{ \Illuminate\Support\Str::limit($user['name'], 20) }}</span>
                                    <span class="font-semibold">{{ $user['count'] }} ({{ round($percentage, 1) }}%)</span>
                                </div>
                                <div class="h-2 overflow-hidden bg-gray-200 rounded-full">
                                    <div class="h-full bg-indigo-500 rounded-full" style="width: {{ $percentage }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Right: Selected User's Module Chart -->
                <div class="p-4 rounded-lg bg-gray-50">
                    @if($selectedFrontendUserId && count($selectedFrontendUserModuleData) > 0)
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="flex items-center gap-2 font-semibold text-gray-700">
                                <i class="text-gray-400 fa fa-chart-bar"></i>
                                {{ $selectedFrontendUserName }} - By Module
                                <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2 py-0.5 rounded-full">
                                    {{ array_sum(array_column($selectedFrontendUserModuleData, 'count')) }}
                                </span>
                            </h3>
                            <button wire:click="clearFrontendUserChart" class="text-gray-400 transition-colors hover:text-gray-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <div class="bar-chart">
                            @foreach($selectedFrontendUserModuleData as $index => $item)
                                <div class="bar-item-stacked" x-data="{ showBreakdown: false }">
                                    <div class="cursor-pointer bar-label" @click="showBreakdown = !showBreakdown">
                                        <span class="flex items-center gap-2">
                                            <svg class="w-4 h-4 transition-transform" :class="showBreakdown ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                            {{ \Illuminate\Support\Str::limit($item['name'], 25) }}
                                        </span>
                                        <span class="font-semibold">{{ $item['count'] }}</span>
                                    </div>
                                    <!-- Stacked Bar -->
                                    <div class="bar-wrapper stacked-bar">
                                        @if(!empty($item['breakdown']))
                                            @foreach($item['breakdown'] as $priority)
                                                <div class="bar-segment"
                                                     wire:click="openFrontendUserModuleSlideOver({{ $item['id'] }})"
                                                     style="width: {{ ($priority['count'] / $item['count']) * $item['percentage'] }}%; background-color: {{ $priority['color'] }};"
                                                     title="{{ $priority['name'] }}: {{ $priority['count'] }} ({{ $priority['percentage'] }}%)">
                                                </div>
                                            @endforeach
                                        @else
                                            <div class="bar-fill" wire:click="openFrontendUserModuleSlideOver({{ $item['id'] }})" style="width: {{ $item['percentage'] }}%; background-color: #6B7280;"></div>
                                        @endif
                                    </div>
                                    <!-- Priority Breakdown Details (expandable) -->
                                    <div x-show="showBreakdown" x-collapse class="priority-breakdown">
                                        @if(!empty($item['breakdown']))
                                            @foreach($item['breakdown'] as $priority)
                                                <div class="breakdown-item">
                                                    <span class="breakdown-color" style="background-color: {{ $priority['color'] }};"></span>
                                                    <span class="breakdown-name">{{ $priority['name'] }}</span>
                                                    <span class="breakdown-count">{{ $priority['count'] }} ({{ $priority['percentage'] }}%)</span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Priority Legend -->
                        <div class="pt-3 mt-3 border-t priority-legend">
                            <div class="flex flex-wrap gap-2">
                                <div class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded" style="background-color: #EF4444;"></span>
                                    <span class="text-xs text-gray-500">Bugs</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded" style="background-color: #F59E0B;"></span>
                                    <span class="text-xs text-gray-500">Backend</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded" style="background-color: #8B5CF6;"></span>
                                    <span class="text-xs text-gray-500">Critical</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded" style="background-color: #10B981;"></span>
                                    <span class="text-xs text-gray-500">Non-Critical</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <span class="w-2 h-2 rounded" style="background-color: #3B82F6;"></span>
                                    <span class="text-xs text-gray-500">Paid</span>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center h-full min-h-[200px] text-gray-400">
                            <i class="mb-3 text-4xl fa fa-hand-pointer"></i>
                            <p class="text-sm">Select a user to view their ticket breakdown</p>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="py-8 text-center text-gray-500">
                No frontend tickets found for the selected period
            </div>
        @endif
    </div>
    --}}
    @endif

    @if($activeTab === 'rnd')
    <div style="display: flex; gap: 16px;">
    <!-- P1 (Software Bugs) Table -->
    <div class="wrapper-container" style="flex: 1; margin-bottom: 0;">
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-lg font-bold text-gray-800">P1 (Software Bugs) by Module</h2>
            @if(count($rndTableData) > 0)
                <span class="px-3 py-1 text-sm font-medium text-red-800 bg-red-100 rounded-full">
                    {{ array_sum(array_column($rndTableData, 'total')) }} total
                </span>
            @endif
        </div>

        @if(count($rndTableData) > 0)
            <div class="overflow-x-auto">
                <table class="analysis-table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th class="count-cell status-new">New</th>
                            <th class="count-cell status-inprogress">In Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rndTableData as $row)
                            @if($row['New'] > 0 || $row['In Progress'] > 0)
                            <tr class="cursor-pointer hover:bg-blue-50" wire:click="openRndModuleSlideOver({{ $row['module_id'] }}, {{ $p1PriorityId }})">
                                <td class="font-medium">{{ $row['module_name'] }}</td>
                                <td class="count-cell {{ $row['New'] > 0 ? 'status-new-cell' : 'zero' }}">{{ $row['New'] }}</td>
                                <td class="count-cell {{ $row['In Progress'] > 0 ? 'status-inprogress-cell' : 'zero' }}">{{ $row['In Progress'] }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="font-bold">Total</td>
                            <td class="font-bold count-cell status-new-cell">{{ array_sum(array_column($rndTableData, 'New')) }}</td>
                            <td class="font-bold count-cell status-inprogress-cell">{{ array_sum(array_column($rndTableData, 'In Progress')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="py-8 text-center text-gray-500">No P1 (Software Bugs) tickets found for the selected period</div>
        @endif
    </div>

    <!-- P2 (Back End Assistance) Table -->
    <div class="wrapper-container" style="flex: 1; margin-bottom: 0;">
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-lg font-bold text-gray-800">P2 (Back End Assistance) by Module</h2>
            @if(count($rndP2TableData) > 0)
                <span class="px-3 py-1 text-sm font-medium rounded-full text-amber-800 bg-amber-100">
                    {{ array_sum(array_column($rndP2TableData, 'total')) }} total
                </span>
            @endif
        </div>

        @if(count($rndP2TableData) > 0)
            <div class="overflow-x-auto">
                <table class="analysis-table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th class="count-cell status-new">New</th>
                            <th class="count-cell status-inprogress">In Progress</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rndP2TableData as $row)
                            @if($row['New'] > 0 || $row['In Progress'] > 0)
                            <tr class="cursor-pointer hover:bg-blue-50" wire:click="openRndModuleSlideOver({{ $row['module_id'] }}, {{ $p2PriorityId }})">
                                <td class="font-medium">{{ $row['module_name'] }}</td>
                                <td class="count-cell {{ $row['New'] > 0 ? 'status-new-cell' : 'zero' }}">{{ $row['New'] }}</td>
                                <td class="count-cell {{ $row['In Progress'] > 0 ? 'status-inprogress-cell' : 'zero' }}">{{ $row['In Progress'] }}</td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="font-bold">Total</td>
                            <td class="font-bold count-cell status-new-cell">{{ array_sum(array_column($rndP2TableData, 'New')) }}</td>
                            <td class="font-bold count-cell status-inprogress-cell">{{ array_sum(array_column($rndP2TableData, 'In Progress')) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="py-8 text-center text-gray-500">No P2 (Back End Assistance) tickets found for the selected period</div>
        @endif
    </div>
    </div>
    @endif

    @if($activeTab === 'fe')
    <div style="display: flex; gap: 16px;">
    <!-- P1 (Software Bugs) Requestor List -->
    <div class="wrapper-container" style="flex: 1; margin-bottom: 0;">
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-lg font-bold text-gray-800">P1 (Software Bugs) by FE</h2>
        </div>
        @if(count($feTableData) > 0)
            @foreach($feTableData as $requestor)
                @if($requestor['status_totals']['New'] > 0 || $requestor['status_totals']['In Progress'] > 0)
                <div class="assignee-section">
                    <div class="assignee-header-bar" wire:click="openFeRequestorSlideOver({{ $requestor['requestor_id'] }}, {{ $p1PriorityId }})">
                        <div class="flex items-center gap-3">
                            <h3>{{ $requestor['requestor_name'] }}</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($requestor['status_totals']['New'] > 0)
                                <span class="status-summary-badge" style="background:#DC2626;color:#FFFFFF;">
                                    New: {{ $requestor['status_totals']['New'] }}
                                </span>
                            @endif
                            @if($requestor['status_totals']['In Progress'] > 0)
                                <span class="status-summary-badge" style="background:#FFFBEB;color:#D97706;">
                                    In Progress: {{ $requestor['status_totals']['In Progress'] }}
                                </span>
                            @endif
                            <span class="assignee-badge">{{ $requestor['status_totals']['New'] + $requestor['status_totals']['In Progress'] }} tickets</span>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        @else
            <div class="py-8 text-center text-gray-500">No P1 (Software Bugs) tickets found for the selected period</div>
        @endif
    </div>

    <!-- P2 (Back End Assistance) Requestor List -->
    <div class="wrapper-container" style="flex: 1; margin-bottom: 0;">
        <div class="flex items-center gap-3 mb-4">
            <h2 class="text-lg font-bold text-gray-800">P2 (Back End Assistance) by FE</h2>
        </div>
        @if(count($feP2TableData) > 0)
            @foreach($feP2TableData as $requestor)
                @if($requestor['status_totals']['New'] > 0 || $requestor['status_totals']['In Progress'] > 0)
                <div class="assignee-section">
                    <div class="assignee-header-bar" wire:click="openFeRequestorSlideOver({{ $requestor['requestor_id'] }}, {{ $p2PriorityId }})">
                        <div class="flex items-center gap-3">
                            <h3>{{ $requestor['requestor_name'] }}</h3>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($requestor['status_totals']['New'] > 0)
                                <span class="status-summary-badge" style="background:#DC2626;color:#FFFFFF;">
                                    New: {{ $requestor['status_totals']['New'] }}
                                </span>
                            @endif
                            @if($requestor['status_totals']['In Progress'] > 0)
                                <span class="status-summary-badge" style="background:#FFFBEB;color:#D97706;">
                                    In Progress: {{ $requestor['status_totals']['In Progress'] }}
                                </span>
                            @endif
                            <span class="assignee-badge">{{ $requestor['status_totals']['New'] + $requestor['status_totals']['In Progress'] }} tickets</span>
                        </div>
                    </div>
                </div>
                @endif
            @endforeach
        @else
            <div class="py-8 text-center text-gray-500">No P2 (Back End Assistance) tickets found for the selected period</div>
        @endif
    </div>
    </div>
    @endif

    <!-- Slide Over Modal -->
    <div
        x-data="{ open: @entangle('showSlideOver') }"
        x-show="open"
        @keydown.window.escape="open = false"
        class="fixed top-0 right-0 bottom-0 left-0 z-[9999] flex justify-end bg-black/40 backdrop-blur-sm transition-opacity duration-200"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        style="display: none;"
    >
        <div
            class="flex flex-col w-full max-w-md ml-auto bg-white shadow-xl"
            style="margin-top: 64px; height: calc(100vh - 64px); position: fixed; right: 0; top: 0;"
            @click.away="open = false"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <!-- Header -->
            <div class="flex-shrink-0 p-4 bg-white border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-gray-800">{{ $slideOverTitle }}</h2>
                    <button @click="open = false" wire:click="closeSlideOver" class="text-2xl text-gray-500 hover:text-gray-700">&times;</button>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <p class="text-sm text-gray-500">{{ count($ticketList) }} tickets found</p>
                    @if(count($ticketsByPriority) > 0)
                        <button
                            wire:click="exportToExcel"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-black bg-green-600 rounded-lg hover:bg-green-700 transition-colors"
                        >
                            <svg wire:loading.remove wire:target="exportToExcel" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <svg wire:loading wire:target="exportToExcel" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="exportToExcel">Export to Excel</span>
                            <span wire:loading wire:target="exportToExcel">Exporting...</span>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 p-4 overflow-y-auto" id="slide-over-content">
                @if(count($ticketsByPriority) > 0)
                    {{-- Grouped by Priority --}}
                    @foreach($ticketsByPriority as $index => $priorityGroup)
                        @php
                            // Auto-expand only if this is the focused priority, or expand all if no focus
                            $isFocused = $focusPriorityId && $priorityGroup['id'] == $focusPriorityId;
                            $shouldExpand = $focusPriorityId ? $isFocused : true;
                        @endphp
                        <div
                            class="mb-4 priority-group {{ $isFocused ? 'focused-priority' : '' }}"
                            id="priority-group-{{ $priorityGroup['id'] }}"
                            x-data="{ expanded: {{ $shouldExpand ? 'true' : 'false' }} }"
                            @if($isFocused)
                            x-init="$nextTick(() => { $el.scrollIntoView({ behavior: 'smooth', block: 'start' }) })"
                            @endif
                        >
                            {{-- Priority Header (collapsible) --}}
                            <div
                                class="flex items-center justify-between p-3 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors {{ $isFocused ? 'bg-blue-50 ring-2 ring-blue-300' : 'bg-gray-50' }}"
                                @click="expanded = !expanded"
                            >
                                <div class="flex items-center gap-3">
                                    <span class="w-4 h-4 rounded" style="background-color: {{ $priorityGroup['color'] }};"></span>
                                    <span class="font-semibold text-gray-800">{{ $priorityGroup['name'] }}</span>
                                    <span class="text-sm text-gray-500 bg-white px-2 py-0.5 rounded-full border">{{ $priorityGroup['count'] }}</span>
                                </div>
                                <svg
                                    class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                    :class="expanded ? 'rotate-180' : ''"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>

                            {{-- Tickets in this priority group --}}
                            <div x-show="expanded" x-collapse class="pl-3 mt-2 ml-2 border-l-2" style="border-color: {{ $priorityGroup['color'] }};">
                                @if(!empty($priorityGroup['subgroups']))
                                    @foreach($priorityGroup['subgroups'] as $subgroup)
                                        <div class="mt-2 mb-3">
                                            <div class="px-2 py-1 mb-1 text-xs font-semibold tracking-wide text-gray-500 uppercase">{{ $subgroup['name'] }}</div>
                                            @foreach($subgroup['tickets'] as $ticket)
                                                <div
                                                    wire:click="viewTicket({{ $ticket['id'] }})"
                                                    class="slide-over-item"
                                                    style="cursor: pointer;"
                                                >
                                                    <div class="ticket-id">#{{ $ticket['ticket_id'] }}</div>
                                                    <div class="ticket-title">{{ \Illuminate\Support\Str::limit($ticket['title'] ?? 'No Title', 60) }}</div>
                                                    <div class="ticket-meta">
                                                        {{ $ticket['company_name'] ?? 'N/A' }} &bull;
                                                        {{ $ticket['status'] ?? 'N/A' }} &bull;
                                                        {{ isset($ticket['created_date']) ? \Carbon\Carbon::parse($ticket['created_date'])->format('d M Y') : 'N/A' }}
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                @else
                                    @foreach($priorityGroup['tickets'] as $ticket)
                                        <div
                                            wire:click="viewTicket({{ $ticket['id'] }})"
                                            class="slide-over-item"
                                            style="cursor: pointer;"
                                        >
                                            <div class="ticket-id">#{{ $ticket['ticket_id'] }}</div>
                                            <div class="ticket-title">{{ \Illuminate\Support\Str::limit($ticket['title'] ?? 'No Title', 60) }}</div>
                                            <div class="ticket-meta">
                                                {{ $ticket['company_name'] ?? 'N/A' }} &bull;
                                                {{ $ticket['status'] ?? 'N/A' }} &bull;
                                                {{ isset($ticket['created_date']) ? \Carbon\Carbon::parse($ticket['created_date'])->format('d M Y') : 'N/A' }}
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endforeach
                @elseif(count($ticketList) > 0)
                    {{-- Flat list fallback (for priority or status slide-overs) --}}
                    @foreach($ticketList as $ticket)
                        <div
                            wire:click="viewTicket({{ $ticket['id'] }})"
                            class="slide-over-item"
                            style="cursor: pointer;"
                        >
                            <div class="ticket-id">#{{ $ticket['ticket_id'] }}</div>
                            <div class="ticket-title">{{ \Illuminate\Support\Str::limit($ticket['title'] ?? 'No Title', 60) }}</div>
                            <div class="ticket-meta">
                                {{ $ticket['company_name'] ?? 'N/A' }} &bull;
                                {{ $ticket['status'] ?? 'N/A' }} &bull;
                                {{ $ticket['created_date'] ? \Carbon\Carbon::parse($ticket['created_date'])->format('d M Y') : 'N/A' }}
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="py-8 text-center text-gray-500">No tickets found</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Ticket Modal Component --}}
    <livewire:ticket-modal />
</x-filament::page>
