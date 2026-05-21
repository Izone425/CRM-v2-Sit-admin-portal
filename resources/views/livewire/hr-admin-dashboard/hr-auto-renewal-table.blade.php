<div class="p-4 bg-white rounded-lg shadow-lg auto-renewal-compact" style="height: auto;">
    <style>
        .auto-renewal-compact .fi-ta-table {
            table-layout: fixed;
            width: 100%;
        }
        .auto-renewal-compact .fi-ta-cell,
        .auto-renewal-compact .fi-ta-header-cell {
            padding: 0.35rem 0.4rem !important;
            font-size: 0.75rem !important;
        }
        .auto-renewal-compact .fi-ta-header-cell-label {
            font-size: 0.7rem !important;
        }
        .auto-renewal-compact .fi-ta-text-item,
        .auto-renewal-compact .fi-ta-text-item span {
            font-size: 0.75rem !important;
        }
        .auto-renewal-compact .fi-badge {
            font-size: 0.65rem !important;
            padding: 0.15rem 0.4rem !important;
        }
    </style>
    {{ $this->table }}
    @if ($this->getTableRecords()->total() > 0 && $this->getTableRecords()->lastPage() > 1)
        <div class="mt-4 text-sm text-center text-gray-600">
            Page {{ $this->getTableRecords()->currentPage() }} of {{ $this->getTableRecords()->lastPage() }}
        </div>
    @endif
</div>
