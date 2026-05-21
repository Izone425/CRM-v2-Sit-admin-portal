<div>
    <style>
        .fi-ta-row.success {
            background-color: #d4edda !important;
        }
        .fi-ta-row.warning {
            background-color: #fff3cd !important;
        }
        /* Hover effects */
        .fi-ta-row.success:hover {
            background-color: #c3e6cb !important;
        }
        .fi-ta-row.warning:hover {
            background-color: #ffeaa7 !important;
        }
    </style>

    {{ $this->table }}

    <!-- Include Files Modal -->
    <x-handover-files-modal
        :showFilesModal="$showFilesModal"
        :selectedHandover="$selectedHandover"
        :handoverFiles="$handoverFiles"
        :showRemarkModal="$showRemarkModal"
        :showAdminRemarkModal="$showAdminRemarkModal"
    />
</div>
