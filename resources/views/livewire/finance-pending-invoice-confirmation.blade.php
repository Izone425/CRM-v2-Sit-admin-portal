<div>
    <style>
        .fi-ta-row.success {
            background-color: #d4edda !important;
        }
        .fi-ta-row.success:hover {
            background-color: #c3e6cb !important;
        }
    </style>

    {{ $this->table }}

    @include('components.handover-files-modal')
</div>
