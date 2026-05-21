{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/ticket-dashboard.blade.php --}}
<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $softwareBugs = $data['softwareBugs'];
        $backendAssistance = $data['backendAssistance'];
        $enhancement = $data['enhancement'];
        $softwareBugsNewBreakdown = $data['softwareBugsNewBreakdown'];
        $softwareBugsInProgressBreakdown = $data['softwareBugsInProgressBreakdown'];
        $softwareBugsCompletedBreakdown = $data['softwareBugsCompletedBreakdown'];
        $softwareBugsClosedBreakdown = $data['softwareBugsClosedBreakdown'];
        $backendNewBreakdown = $data['backendNewBreakdown'];
        $backendInProgressBreakdown = $data['backendInProgressBreakdown'];
        $backendCompletedBreakdown = $data['backendCompletedBreakdown'];
        $backendClosedBreakdown = $data['backendClosedBreakdown'];
        $calendar = $data['calendar'];
        $currentMonth = $data['currentMonth'];
        $currentYear = $data['currentYear'];
    @endphp

    <style>
        select:not(.choices) {
            background-image: none !important;
        }

        /* Main Layout */
        .dashboard-wrapper {
            background: #F9FAFB;
            min-height: 100vh;
            padding: 0;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        /* ✅ Add page title styling */
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .filter-dropdowns {
            display: flex;
            gap: 12px;
        }

        .filter-select {
            padding: 8px 16px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            background: white;
            font-size: 14px;
            color: #374151;
            cursor: pointer;
            min-width: 180px;
            appearance: none; /* ✅ Remove default dropdown arrow */
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: none; /* ✅ Remove any background arrow */
        }

        .filter-select:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 570px 1fr;
            gap: 24px;
        }

        /* Left Column */
        .left-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Category Cards */
        .category-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #E5E7EB;
        }

        .category-card.red {
            border-left: 4px solid #DC2626;
        }

        .category-card.blue {
            border-left: 4px solid #2563EB;
        }

        .category-card.green {
            border-left: 4px solid #059669;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .category-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .red .category-icon {
            background: #FEE2E2;
        }

        .blue .category-icon {
            background: #DBEAFE;
        }

        .green .category-icon {
            background: #D1FAE5;
        }

        .category-title {
            flex: 1;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }

        .category-badge {
            background: #F3F4F6;
            color: #6B7280;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        /* Status Grid */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .status-grid.three-items {
            grid-template-columns: repeat(3, 1fr);
        }

        .status-box {
            background: #FAFAFA;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .status-box:hover {
            background: white;
            border-color: #D1D5DB;
            transform: translateY(-1px);
        }

        .status-box.active {
            background: #1F2937;
            border-color: #1F2937;
        }

        .status-number {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            line-height: 1;
            margin-bottom: 6px;
        }

        .status-box.active .status-number {
            color: white;
        }

        .status-text {
            font-size: 12px;
            font-weight: 500;
            color: #6B7280;
        }

        .status-box.active .status-text {
            color: #D1D5DB;
        }

        /* Enhancement Section */
        .enhancement-filters {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            justify-content: flex-end;
        }

        .filter-pill {
            padding: 6px 14px;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            background: white;
            font-size: 12px;
            color: #6B7280;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }

        .filter-pill:hover {
            border-color: #059669;
            color: #059669;
        }

        /* ✅ Active state for filter pills */
        .filter-pill.active {
            background: #059669;
            border-color: #059669;
            color: white;
            font-weight: 600;
        }

        .filter-pill.active:hover {
            background: #047857;
            border-color: #047857;
            color: white;
        }

        /* Calendar */
        .calendar-wrapper {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #E5E7EB;
            margin-top: 16px;
        }

        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .calendar-title {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .calendar-arrows {
            display: flex;
            gap: 4px;
        }

        .arrow-btn {
            background: transparent;
            border: none;
            color: #9CA3AF;
            cursor: pointer;
            padding: 4px;
            font-size: 18px;
        }

        .arrow-btn:hover {
            color: #374151;
        }

        .calendar-table {
            width: 100%;
            border-collapse: collapse;
        }

        .calendar-table th {
            font-size: 11px;
            font-weight: 600;
            color: #9CA3AF;
            text-transform: uppercase;
            padding: 8px 4px;
            text-align: center;
        }

        .calendar-table td {
            text-align: center;
            padding: 4px;
            font-size: 13px;
            color: #374151;
        }

        .calendar-day {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .calendar-day:hover {
            background: #F3F4F6;
        }

        .calendar-day.today {
            background: #6366F1;
            color: white;
            font-weight: 600;
        }

        .calendar-day.selected {
            background: #10B981;
            color: white;
            font-weight: 600;
        }

        .calendar-day.today.selected {
            background: #059669;
            color: white;
        }

        .calendar-day.other-month {
            color: #D1D5DB;
        }

        .calendar-day.other-month:hover {
            background: #F9FAFB;
        }

        /* Right Panel */
        .ticket-panel {
            background: white;
            border-radius: 12px;
            border: 1px solid #E5E7EB;
            display: flex;
            flex-direction: column;
            height: fit-content;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #E5E7EB;
        }

        .ticket-title {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }

        .ticket-filters {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .ticket-filter-select {
            padding: 6px 12px;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            font-size: 13px;
            background: white;
            appearance: none; /* ✅ Remove dropdown arrow */
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: none;
        }

        .close-badge {
            background: #F3F4F6;
            color: #6B7280;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 500;
        }

        .close-badge:hover {
            background: #E5E7EB;
        }

        .ticket-count {
            color: #9CA3AF;
            font-size: 13px;
        }

        .empty-tickets {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 16px;
            opacity: 0.3;
        }

        .empty-text {
            color: #9CA3AF;
            font-size: 14px;
        }

        .status-badge-wrapper:hover .status-tooltip {
            opacity: 1 !important;
        }

        /* Fast-response module tooltip */
        .module-cell {
            position: relative;
            overflow: visible;
        }

        .module-cell:hover {
            background: #F9FAFB;
            font-weight: 600;
        }

        .module-tooltip {
            position: fixed;
            padding: 10px 14px;
            background: #1F2937;
            color: white;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            z-index: 9999;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-width: 600px;
            min-width: 300px;
            white-space: normal;
            line-height: 1.5;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.15s ease-in-out, visibility 0.15s ease-in-out;
        }

        .module-cell:hover .module-tooltip {
            opacity: 1;
            visibility: visible;
        }

        /* Status breakdown tooltip */
        .status-breakdown-tooltip {
            position: fixed;
            padding: 14px 16px;
            background: #1F2937;
            color: white;
            font-size: 13px;
            font-weight: 500;
            border-radius: 8px;
            z-index: 9999;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.24);
            min-width: 250px;
            max-width: 400px;
            white-space: normal;
            line-height: 1.6;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease-in-out, visibility 0.2s ease-in-out;
        }

        .status-breakdown-tooltip .frontend-item {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .status-breakdown-tooltip .frontend-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .status-breakdown-tooltip .frontend-item:first-child {
            padding-top: 0;
        }

        .status-breakdown-tooltip .frontend-name {
            font-weight: 600;
            color: #60A5FA;
            margin-bottom: 4px;
            font-size: 13px;
        }

        .status-breakdown-tooltip .ticket-count {
            color: #D1D5DB;
            font-size: 12px;
            margin-bottom: 4px;
        }

        .status-breakdown-tooltip .ticket-list {
            color: #9CA3AF;
            font-size: 11px;
            margin-left: 8px;
            line-height: 1.4;
        }
    </style>

    <script>
        let tooltipEl = null;

        function showTooltip(event, title) {
            if (!title) return;

            // Create tooltip if it doesn't exist
            if (!tooltipEl) {
                tooltipEl = document.createElement('div');
                tooltipEl.className = 'module-tooltip';
                document.body.appendChild(tooltipEl);
            }

            // Set content and position
            tooltipEl.textContent = title;
            tooltipEl.style.opacity = '1';
            tooltipEl.style.visibility = 'visible';

            // Position below the cell
            const rect = event.currentTarget.getBoundingClientRect();
            tooltipEl.style.left = rect.left + 'px';
            tooltipEl.style.top = (rect.bottom + 8) + 'px';
        }

        function showTicketTooltip(event, module, company, title) {
            if (!module && !company && !title) return;

            // Create tooltip if it doesn't exist
            if (!tooltipEl) {
                tooltipEl = document.createElement('div');
                tooltipEl.className = 'module-tooltip';
                document.body.appendChild(tooltipEl);
            }

            // Build multi-line content
            let content = '';
            if (module) content += '<div style="margin-bottom: 4px;"><strong>Module:</strong> ' + module + '</div>';
            if (company) content += '<div style="margin-bottom: 4px;"><strong>Company:</strong> ' + company + '</div>';
            if (title) content += '<div><strong>Title:</strong> ' + title + '</div>';

            // Set content and position
            tooltipEl.innerHTML = content;
            tooltipEl.style.opacity = '1';
            tooltipEl.style.visibility = 'visible';

            // Position below the cell
            const rect = event.currentTarget.getBoundingClientRect();
            tooltipEl.style.left = rect.left + 'px';
            tooltipEl.style.top = (rect.bottom + 8) + 'px';
        }

        function hideTooltip(event) {
            if (tooltipEl) {
                tooltipEl.style.opacity = '0';
                tooltipEl.style.visibility = 'hidden';
            }
        }

        // Show status breakdown tooltip (for Software Bugs In Progress)
        function showBreakdownTooltip(event, breakdownData) {
            if (!breakdownData || Object.keys(breakdownData).length === 0) return;

            // Create tooltip if it doesn't exist
            if (!tooltipEl) {
                tooltipEl = document.createElement('div');
                tooltipEl.className = 'status-breakdown-tooltip';
                document.body.appendChild(tooltipEl);
            }

            // Build HTML content
            let content = '';
            for (const [frontend, data] of Object.entries(breakdownData)) {
                content += `
                    <div class="frontend-item">
                        <div class="frontend-name">${frontend} <span style="color: #9CA3AF;">(${data.count})</span></div>
                    </div>
                `;
            }

            // Set content and position
            tooltipEl.innerHTML = content;
            tooltipEl.className = 'status-breakdown-tooltip';
            tooltipEl.style.opacity = '1';
            tooltipEl.style.visibility = 'visible';

            // Position below the status box
            const rect = event.currentTarget.getBoundingClientRect();
            tooltipEl.style.left = rect.left + 'px';
            tooltipEl.style.top = (rect.bottom + 8) + 'px';
        }

    </script>

    <div class="dashboard-wrapper">
        <!-- ✅ Header with Title -->
        <div class="dashboard-header">
            <h1 class="page-title">Ticket Dashboard</h1>
        </div>

        <style>
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>

        <!-- Main Grid -->
        <div class="dashboard-grid">
            <!-- Left Column -->
            <div class="left-column">
                <!-- Software Bugs -->
                <div class="category-card red">
                    <div class="category-header">
                        <div class="category-icon">📋</div>
                        <div class="category-title">Software Bugs</div>
                        <div class="category-badge">{{ $softwareBugs['total'] }}</div>
                    </div>
                    <div class="status-grid">
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'New' ? 'active' : '' }}"
                            wire:click="selectCategory('softwareBugs', 'New')"
                            onmouseenter="showBreakdownTooltip(event, {{ json_encode($softwareBugsNewBreakdown) }})"
                            onmouseleave="hideTooltip(event)">
                            <div class="status-number">{{ $softwareBugs['new'] }}</div>
                            <div class="status-text">New</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'In Progress' ? 'active' : '' }}"
                            wire:click="selectCategory('softwareBugs', 'In Progress')"
                            onmouseenter="showBreakdownTooltip(event, {{ json_encode($softwareBugsInProgressBreakdown) }})"
                            onmouseleave="hideTooltip(event)">
                            <div class="status-number">{{ $softwareBugs['progress'] }}</div>
                            <div class="status-text">In Progress</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && ($selectedStatus === 'Completed' || $selectedStatus === 'Tickets: Live') ? 'active' : '' }}"
                            wire:click="selectCategory('softwareBugs', 'Completed')"
                            onmouseenter="showBreakdownTooltip(event, {{ json_encode($softwareBugsCompletedBreakdown) }})"
                            onmouseleave="hideTooltip(event)">
                            <div class="status-number">{{ $softwareBugs['completed'] }}</div>
                            <div class="status-text">KIV/Completed</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'Closed' ? 'active' : '' }}"
                            wire:click="selectCategory('softwareBugs', 'Closed')"
                            onmouseenter="showBreakdownTooltip(event, {{ json_encode($softwareBugsClosedBreakdown) }})"
                            onmouseleave="hideTooltip(event)">
                            <div class="status-number">{{ $softwareBugs['closed'] }}</div>
                            <div class="status-text">Closed</div>
                        </div>
                    </div>
                </div>

                <!-- Backend Assistance -->
                <div class="category-card blue">
                    <div class="category-header">
                        <div class="category-icon">💻</div>
                        <div class="category-title">Backend Assistance</div>
                        <div class="category-badge">{{ $backendAssistance['total'] }}</div>
                    </div>
                    <div class="status-grid">
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'New' ? 'active' : '' }}"
                            wire:click="selectCategory('backendAssistance', 'New')"
                            onmouseenter="showBreakdownTooltip(event, {{ json_encode($backendNewBreakdown) }})"
                            onmouseleave="hideTooltip(event)">
                            <div class="status-number">{{ $backendAssistance['new'] }}</div>
                            <div class="status-text">New</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'In Progress' ? 'active' : '' }}"
                            wire:click="selectCategory('backendAssistance', 'In Progress')"
                            onmouseenter="showBreakdownTooltip(event, {{ json_encode($backendInProgressBreakdown) }})"
                            onmouseleave="hideTooltip(event)">
                            <div class="status-number">{{ $backendAssistance['progress'] }}</div>
                            <div class="status-text">In Progress</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && ($selectedStatus === 'Completed' || $selectedStatus === 'Tickets: Live') ? 'active' : '' }}"
                            wire:click="selectCategory('backendAssistance', 'Completed')"
                            onmouseenter="showBreakdownTooltip(event, {{ json_encode($backendCompletedBreakdown) }})"
                            onmouseleave="hideTooltip(event)">
                            <div class="status-number">{{ $backendAssistance['completed'] }}</div>
                            <div class="status-text">KIV/Completed</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'Closed' ? 'active' : '' }}"
                            wire:click="selectCategory('backendAssistance', 'Closed')"
                            onmouseenter="showBreakdownTooltip(event, {{ json_encode($backendClosedBreakdown) }})"
                            onmouseleave="hideTooltip(event)">
                            <div class="status-number">{{ $backendAssistance['closed'] }}</div>
                            <div class="status-text">Closed</div>
                        </div>
                    </div>
                </div>

                <!-- Enhancement Workflow -->
                <div class="category-card green">
                    <div class="category-header">
                        <div class="category-icon">⭐</div>
                        <div class="category-title">Enhancement Workflow</div>
                        <div class="category-badge">{{ $enhancement['total'] }}</div>
                    </div>
                    <div class="enhancement-filters">
                        <div class="filter-pill {{ $selectedEnhancementType === 'critical' ? 'active' : '' }}"
                             wire:click="selectEnhancementType('critical')">
                            Critical
                        </div>
                        <div class="filter-pill {{ $selectedEnhancementType === 'paid' ? 'active' : '' }}"
                             wire:click="selectEnhancementType('paid')">
                            Paid
                        </div>
                        <div class="filter-pill {{ $selectedEnhancementType === 'non-critical' ? 'active' : '' }}"
                             wire:click="selectEnhancementType('non-critical')">
                            Non Critical
                        </div>
                    </div>
                    <div class="status-grid three-items">
                        <div class="status-box {{ $selectedCategory === 'enhancement' && $selectedEnhancementStatus === 'New' ? 'active' : '' }}"
                             wire:click="$set('selectedEnhancementStatus', '{{ $selectedEnhancementStatus === 'New' ? null : 'New' }}'); $set('selectedCategory', '{{ $selectedEnhancementStatus === 'New' ? null : 'enhancement' }}');">
                            <div class="status-number">{{ $enhancement['new'] }}</div>
                            <div class="status-text">New</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'enhancement' && $selectedEnhancementStatus === 'Pending Release' ? 'active' : '' }}"
                             wire:click="$set('selectedEnhancementStatus', '{{ $selectedEnhancementStatus === 'Pending Release' ? null : 'Pending Release' }}'); $set('selectedCategory', '{{ $selectedEnhancementStatus === 'Pending Release' ? null : 'enhancement' }}');">
                            <div class="status-number">{{ $enhancement['pending_release'] }}</div>
                            <div class="status-text">Pending Release</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'enhancement' && $selectedEnhancementStatus === 'System Go Live' ? 'active' : '' }}"
                             wire:click="$set('selectedEnhancementStatus', '{{ $selectedEnhancementStatus === 'System Go Live' ? null : 'System Go Live' }}'); $set('selectedCategory', '{{ $selectedEnhancementStatus === 'System Go Live' ? null : 'enhancement' }}');">
                            <div class="status-number">{{ $enhancement['system_go_live'] }}</div>
                            <div class="status-text">System Go Live</div>
                        </div>
                    </div>

                    <!-- Calendar -->
                    <div class="calendar-wrapper">
                        <div class="calendar-nav">
                            <button class="arrow-btn" wire:click="previousMonth">‹</button>
                            <div class="calendar-title">{{ $calendar['month'] }}</div>
                            <button class="arrow-btn" wire:click="nextMonth">›</button>
                        </div>
                        <table class="calendar-table">
                            <thead>
                                <tr>
                                    <th>MON</th>
                                    <th>TUE</th>
                                    <th>WED</th>
                                    <th>THU</th>
                                    <th>FRI</th>
                                    <th>SAT</th>
                                    <th>SUN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $firstDay = $calendar['first_day_of_week'];
                                    $daysInMonth = $calendar['days_in_month'];
                                    $currentDate = $calendar['current_date'];
                                    $adjustedFirstDay = $firstDay == 0 ? 6 : $firstDay - 1;

                                    $prevMonthDate = \Carbon\Carbon::create($currentYear, $currentMonth, 1)->subMonth();
                                    $prevMonthDays = $prevMonthDate->daysInMonth;

                                    $days = [];

                                    for ($i = $adjustedFirstDay - 1; $i >= 0; $i--) {
                                        $days[] = [
                                            'day' => $prevMonthDays - $i,
                                            'class' => 'other-month',
                                            'year' => $prevMonthDate->year,
                                            'month' => $prevMonthDate->month,
                                        ];
                                    }

                                    for ($day = 1; $day <= $daysInMonth; $day++) {
                                        $isToday = $day == $currentDate->day &&
                                                  $currentMonth == $currentDate->month &&
                                                  $currentYear == $currentDate->year;

                                        $dateString = \Carbon\Carbon::create($currentYear, $currentMonth, $day)->format('Y-m-d');
                                        $isSelected = $selectedDate === $dateString;

                                        $class = $isToday ? 'today' : '';
                                        if ($isSelected) {
                                            $class .= ' selected';
                                        }

                                        $days[] = [
                                            'day' => $day,
                                            'class' => trim($class),
                                            'year' => $currentYear,
                                            'month' => $currentMonth,
                                        ];
                                    }

                                    $totalCells = count($days);
                                    $remainingCells = (7 - ($totalCells % 7)) % 7;

                                    $nextMonthDate = \Carbon\Carbon::create($currentYear, $currentMonth, 1)->addMonth();

                                    for ($day = 1; $day <= $remainingCells; $day++) {
                                        $days[] = [
                                            'day' => $day,
                                            'class' => 'other-month',
                                            'year' => $nextMonthDate->year,
                                            'month' => $nextMonthDate->month,
                                        ];
                                    }

                                    $weeks = array_chunk($days, 7);
                                @endphp

                                @foreach($weeks as $week)
                                    <tr>
                                        @foreach($week as $dayData)
                                            <td>
                                                <div class="calendar-day {{ $dayData['class'] }}"
                                                     wire:click="selectDate({{ $dayData['year'] }}, {{ $dayData['month'] }}, {{ $dayData['day'] }})">
                                                    {{ $dayData['day'] }}
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Ticket Listing -->
            <div class="ticket-panel">
                {{-- Dashboard tabs --}}
                <div class="ticket-dashboard-tabs">
                    <button type="button"
                        wire:click="setDashboard(1)"
                        class="ticket-dashboard-tab {{ $activeDashboard === 1 ? 'active' : '' }}">
                        Dashboard 1
                    </button>
                    <button type="button"
                        wire:click="setDashboard(2)"
                        class="ticket-dashboard-tab {{ $activeDashboard === 2 ? 'active' : '' }}"
                        title="P1 / P2 · New & In Progress · Pending FE">
                        Dashboard 2
                    </button>
                </div>

                <style>
                    .ticket-dashboard-tabs {
                        display: flex;
                        gap: 4px;
                        margin-bottom: 12px;
                        border-bottom: 1px solid #e5e7eb;
                    }
                    .ticket-dashboard-tab {
                        padding: 8px 18px;
                        font-size: 13px;
                        font-weight: 600;
                        color: #6b7280;
                        background: transparent;
                        border: none;
                        border-bottom: 2px solid transparent;
                        cursor: pointer;
                        transition: all 0.15s;
                        margin-bottom: -1px;
                    }
                    .ticket-dashboard-tab:hover { color: #374151; }
                    .ticket-dashboard-tab.active {
                        color: #2563eb;
                        border-bottom-color: #2563eb;
                    }
                </style>

                {{-- Filament Table (with active filter badges in header) --}}
                {{ $this->table }}
            </div>

        </div>
    </div>

    {{-- Ticket Modal Component --}}
    <livewire:ticket-modal />

    <x-filament-actions::modals />
</x-filament-panels::page>
