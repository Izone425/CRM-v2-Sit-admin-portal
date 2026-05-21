<div>
    <style>
        .search-wrapper {
            position: relative;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-input-container {
            position: relative;
            flex: 1;
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
            overflow-x: auto;
            overflow-y: visible;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table thead {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            position: relative;
            overflow: visible;
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
            position: relative;
            overflow: visible;
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

        .custom-table td {
            padding: 1rem 1.5rem;
            font-size: 0.875rem;
            color: #1f2937;
        }

        .fb-id {
            font-weight: 600;
            color: #667eea;
        }

        .subscriber-name {
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

        .status-new {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .status-pending-quotation-confirmation {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #991b1b;
            border: 1px solid #f87171;
        }

        .status-pending-invoice {
            background: linear-gradient(135deg, #fed7aa 0%, #fdba74 100%);
            color: #9a3412;
            border: 1px solid #fb923c;
        }

        .status-pending-invoice-confirmation {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #991b1b;
            border: 1px solid #f87171;
        }

        .status-pending-reseller-payment {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #991b1b;
            border: 1px solid #f87171;
        }

        .status-pending-timetec-license {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #3730a3;
            border: 1px solid #a5b4fc;
        }

        .status-pending-timetec-invoice {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #3730a3;
            border: 1px solid #a5b4fc;
        }

        .status-pending-timetec-finance {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #3730a3;
            border: 1px solid #a5b4fc;
        }

        .status-completed {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .status-inactive {
            background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
            color: #4b5563;
            border: 1px solid #d1d5db;
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

        .filter-column {
            text-align: center;
            width: 80px;
        }

        .filter-container {
            position: relative;
            display: inline-block;
        }

        .filter-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
            color: #64748b;
            white-space: nowrap;
        }

        .filter-button:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .filter-button.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .filter-icon {
            width: 1rem;
            height: 1rem;
        }

        .filter-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 0.5rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 0;
            min-width: 260px;
            z-index: 9999;
            display: none;
        }

        .filter-dropdown.show {
            display: block;
        }

        .filter-section {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .filter-section:last-child {
            border-bottom: none;
        }

        .filter-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.5rem;
        }

        .filter-option {
            display: flex;
            align-items: center;
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .filter-option:hover {
            background: #f3f4f6;
        }

        .filter-option input[type="checkbox"],
        .filter-option input[type="radio"] {
            margin-right: 0.5rem;
            cursor: pointer;
        }

        .filter-option label {
            cursor: pointer;
            font-size: 0.875rem;
            color: #374151;
            flex: 1;
        }

        .filter-select-wrapper {
            padding: 0.75rem;
        }

        .filter-select {
            width: 100%;
            padding: 0.625rem 2.5rem 0.625rem 0.75rem;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
            color: #374151;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1.25rem;
        }

        .filter-select:hover {
            border-color: #667eea;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
    </style>

    <!-- Search Input -->
    <div class="search-wrapper">
        <div class="search-input-container">
            <div class="search-icon">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>
            <input
                type="text"
                wire:model.live="search"
                class="search-input"
                placeholder="Search by company name"
            >
        </div>

        <div class="filter-container" x-data="{ open: false }" @click.away="open = false">
            <button @click="open = !open" class="filter-button" :class="{ 'active': {{ (!empty($statusFilter) || $activeFilter !== 'all') ? 'true' : 'false' }} }">
                <svg class="filter-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                <span>Filter</span>
            </button>
            <div class="filter-dropdown" :class="{ 'show': open }" @click.stop>
                <div class="filter-section">
                    <div class="filter-section-title">Status</div>
                    <div class="filter-select-wrapper">
                        <select wire:model.live="statusFilter" class="filter-select">
                            <option value="">All Status</option>
                            <option value="new">New</option>
                            <option value="pending_quotation_confirmation">Pending Quotation Confirmation</option>
                            <option value="pending_invoice_confirmation">Pending Invoice Confirmation</option>
                            <option value="pending_reseller_payment">Pending Reseller Payment</option>
                            <option value="pending_timetec_license">Pending TimeTec License</option>
                            <option value="pending_timetec_invoice">Pending TimeTec Invoice</option>
                            <option value="pending_timetec_finance">Pending TimeTec Finance</option>
                            <option value="completed">Completed</option>
                            <option value="inactive">InActive</option>
                        </select>
                    </div>
                </div>
                <div class="filter-section">
                    <div class="filter-section-title">Subscriber Status</div>
                    <div class="filter-select-wrapper">
                        <select wire:model.live="activeFilter" class="filter-select">
                            <option value="all">All</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">InActive Only</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-container">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>
                        <button wire:click="sortBy('id')">
                            ID
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'id')
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
                        <button wire:click="sortBy('subscriber_name')">
                            Subscriber Name
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'subscriber_name')
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
                        <button wire:click="sortBy('updated_at')">
                            Last Modified
                            <svg class="sort-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($sortField === 'updated_at')
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
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($handovers as $handover)
                    <tr>
                        <td class="fb-id">
                            <a wire:click="openFilesModal({{ $handover->id }})" style="color: #3b82f6; font-weight: 600; cursor: pointer; text-decoration: none;"
                               onmouseover="this.style.textDecoration='underline'"
                               onmouseout="this.style.textDecoration='none'">
                                {{ $handover->fb_id }}
                            </a>
                        </td>
                        <td class="subscriber-name">
                            {{ $handover->subscriber_name }}
                        </td>
                        <td class="date-cell">
                            {{ $handover->updated_at->format('d M Y, H:i') }}
                        </td>
                        <td>
                            @php
                                $statusClass = 'status-' . str_replace('_', '-', $handover->status);
                                $statusLabel = str_replace('Timetec', 'TimeTec', ucwords(str_replace('_', ' ', $handover->status)));
                                if ($handover->status === 'inactive') {
                                    $statusLabel = 'InActive';
                                }
                            @endphp
                            <span class="status-badge {{ $statusClass }}">
                                {{ $statusLabel }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="empty-state">
                            No handovers found
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('components.handover-files-modal')
</div>
