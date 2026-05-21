@php
    $record = $record ?? null;

    if (!$record) {
        echo 'No record found.';
        return;
    }
@endphp

<style>
    .reseller-details-container {
        padding: 1rem;
    }

    .reseller-details-container .top-section {
        margin-bottom: 1.5rem;
    }

    .reseller-details-container .field-row {
        padding-bottom: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .reseller-details-container .field-label {
        font-weight: 600;
        color: #111827;
        display: block;
        margin-bottom: 0.25rem;
        font-size: 0.875rem;
    }

    .reseller-details-container .field-value {
        color: #111827;
        font-size: 1rem;
    }

    .reseller-details-container .two-columns {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
        margin-bottom: 1.5rem;
    }

    .reseller-details-container .column-content {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    .reseller-details-container .field-inline {
        line-height: 1.5;
    }

    .dark .reseller-details-container .field-label,
    .dark .reseller-details-container .field-value {
        color: #f9fafb;
    }

    .dark .reseller-details-container .field-row {
        border-bottom-color: #4b5563;
    }
</style>

<div class="reseller-details-container">
    <!-- Company Name - Full Width -->
    <div class="field-row" style="margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb;">
        <span class="field-label">Company Name</span>
        <span class="field-value" style="font-size: 1.1rem;">{{ $record->company_name ?? 'N/A' }}</span>
    </div>

    <!-- Two Column Layout -->
    <div class="two-columns">
        <!-- Left Column -->
        <div class="column-content">
            <div class="field-inline">
                <span class="field-label">Created Date & Time</span>
                <span class="field-value">{{ $record->created_at ? $record->created_at->format('d M Y, H:i') : 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Login Email</span>
                <span class="field-value">{{ $record->email ?? 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Login Password</span>
                <span class="field-value">{{ $record->plain_password ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Right Column -->
        <div class="column-content">
            <div class="field-inline">
                <span class="field-label">Bind Reseller ID</span>
                <span class="field-value">{{ $record->reseller_id ?? 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">PIC Name</span>
                <span class="field-value">{{ $record->name ?? 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">PIC No Hp</span>
                <span class="field-value">{{ $record->phone ?? 'N/A' }}</span>
            </div>
        </div>
    </div>

    <hr class="my-4 border-gray-300">

    <!-- Bottom Two Column Layout -->
    <div class="two-columns">
        <!-- Left Column -->
        <div class="column-content">
            <div class="field-inline">
                <span class="field-label">SSM Number</span>
                <span class="field-value">{{ $record->ssm_number ?? 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Debtor Code</span>
                <span class="field-value">{{ $record->debtor_code ?? 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">SST Category</span>
                <span class="field-value">{{ $record->sst_category ?? 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Email Notification</span>
                <span class="field-value">{{ $record->email_notification ? 'Yes' : 'No' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Trial Account Feature</span>
                <span class="field-value">{{ $record->trial_account_feature === 'enable' ? 'Enable' : 'Disable' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Installation Payment Feature</span>
                <span class="field-value">{{ $record->installation_payment_feature === 'enable' ? 'Enable' : 'Disable' }}</span>
            </div>
        </div>

        <!-- Right Column -->
        <div class="column-content">
            <div class="field-inline">
                <span class="field-label">TIN Number</span>
                <span class="field-value">{{ $record->tax_identification_number ?? 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Creditor Code</span>
                <span class="field-value">{{ $record->creditor_code ?? 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Payment Type</span>
                <span class="field-value">{{ $record->payment_type ? ucwords(str_replace('_', ' ', $record->payment_type)) : 'N/A' }}</span>
            </div>

            <div class="field-inline">
                <span class="field-label">Commission Scheme</span>
                <span class="field-value">{{ $record->commission_rate ? number_format($record->commission_rate, 2) . '%' : 'N/A' }}</span>
            </div>
        </div>
    </div>
</div>
