{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/admin-renewal-process-data-myr.blade.php --}}
<x-filament-panels::page>
    <style>
        /* Force sticky headers for Filament tables */
        .fi-ta-table {
            position: relative;
        }

        .fi-ta-table thead {
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
        }

        .fi-ta-table thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
            background-color: rgb(250, 250, 250) !important;
            border-bottom: 2px solid #e5e7eb !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }

        /* Dark mode support */
        .dark .fi-ta-table thead th {
            background-color: rgb(17 24 39) !important;
            border-bottom: 2px solid rgb(55 65 81) !important;
            color: rgb(229 231 235) !important;
        }

        /* Table container with fixed height */
        .fi-ta-content {
            max-height: calc(100vh - 250px) !important;
            overflow: auto !important;
        }

        /* Ensure proper scrolling */
        .fi-ta-ctn {
            overflow: visible !important;
        }

        /* Fix for filter dropdowns to appear above sticky headers */
        [x-data*="dropdown"], .fi-dropdown-panel {
            z-index: 30 !important;
        }

        /* Renewal Dashboard Grid */
        .renewal-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .renewal-dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .renewal-dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .renewal-dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card styling */
        .renewal-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .renewal-card-content {
            padding: 1.25rem 0.75rem;
        }

        .renewal-card-layout {
            display: flex;
            align-items: center;
        }

        /* Icon container */
        .renewal-icon-container {
            flex-shrink: 0;
            padding: 0.75rem;
            border-radius: 0.375rem;
        }

        .renewal-icon-container.green {
            background-color: rgba(34, 197, 94, 0.1);
        }

        .renewal-icon-container.blue {
            background-color: rgba(37, 99, 235, 0.1);
        }

        .renewal-icon-container.purple {
            background-color: rgba(124, 58, 237, 0.1);
        }

        .renewal-icon-container.orange {
            background-color: rgba(249, 115, 22, 0.1);
        }

        .renewal-icon-container.red {
            background-color: rgba(220, 38, 38, 0.1);
        }

        .renewal-icon {
            width: 1.5rem;
            height: 1.5rem;
        }

        .renewal-icon-container.green .renewal-icon {
            color: rgba(34, 197, 94, 1);
        }

        .renewal-icon-container.blue .renewal-icon {
            color: rgba(37, 99, 235, 1);
        }

        .renewal-icon-container.purple .renewal-icon {
            color: rgba(124, 58, 237, 1);
        }

        .renewal-icon-container.orange .renewal-icon {
            color: rgba(249, 115, 22, 1);
        }

        .renewal-icon-container.red .renewal-icon {
            color: rgba(220, 38, 38, 1);
        }

        /* Renewal details */
        .renewal-details {
            flex: 1;
            width: 0;
            margin-left: 0.5rem;
        }

        .renewal-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: #111827;
        }

        .renewal-subtitle {
            font-size: 0.75rem;
            color: #6B7280;
        }

        .renewal-amount-label {
            font-size: 1rem;
            font-weight: 500;
            color: #111827;
        }

        .renewal-amount {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .renewal-amount.green {
            color: rgba(34, 197, 94, 1);
        }

        .renewal-amount.blue {
            color: rgba(37, 99, 235, 1);
        }

        .renewal-amount.purple {
            color: rgba(124, 58, 237, 1);
        }

        .renewal-amount.orange {
            color: rgba(249, 115, 22, 1);
        }

        .renewal-amount.red {
            color: rgba(220, 38, 38, 1);
        }

        .refresh-button-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        .refresh-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #3B82F6;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .refresh-button:hover {
            background-color: #2563EB;
        }

        .refresh-icon {
            width: 1rem;
            height: 1rem;
        }
    </style>

    <!-- Dashboard Cards -->
    @php($filteredCompanyIds = $this->getFilteredCompanyIds())
    <livewire:admin-renewal-dashboard.process-data-myr-stats-cards
        :company-ids="$filteredCompanyIds"
        :key="'stats-cards-' . md5(json_encode($filteredCompanyIds))"
        lazy
    />

    <!-- Filament Table -->
    {{ $this->table }}
</x-filament-panels::page>
