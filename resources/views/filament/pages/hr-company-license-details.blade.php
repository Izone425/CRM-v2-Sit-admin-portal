<x-filament-panels::page>
    @if($handoverId || $softwareHandoverId || ($hrAccountId && $hrCompanyId))
        <livewire:hr-admin-dashboard.company-license-details-container
            :handover-id="$handoverId"
            :software-handover-id="$softwareHandoverId"
            :hr-account-id="$hrAccountId"
            :hr-company-id="$hrCompanyId"
            :tab="$tab"
        />
    @else
        <div style="padding: 2.5rem 1.5rem; text-align: center; background: linear-gradient(135deg, #ffffff 0%, #f9fafb 50%, #f3f4f6 100%); border-radius: 0.75rem; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1); border: 1px solid #e5e7eb;">
            <div style="width: 4rem; height: 4rem; margin: 0 auto; background-color: #f3f4f6; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 2rem; height: 2rem; color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 style="margin-top: 1.25rem; font-size: 1.125rem; line-height: 1.75rem; font-weight: 600; color: #111827;">No Company Selected</h3>
            <p style="margin-top: 0.5rem; font-size: 0.875rem; line-height: 1.5rem; color: #6b7280; max-width: 24rem; margin-left: auto; margin-right: auto;">Please select a company from the All Licenses page to view details.</p>
            <a href="{{ url('/admin/hr-license') }}" style="display: inline-flex; align-items: center; margin-top: 1.25rem; font-size: 0.875rem; line-height: 1.25rem; font-weight: 500; color: #7c3aed; text-decoration: none; padding: 0.5rem 1rem; border-radius: 0.375rem; background-color: #f5f3ff; border: 1px solid #ede9fe; transition: background-color 0.15s ease-in-out;">
                Go to All Licenses
                <svg style="width: 1rem; height: 1rem; margin-left: 0.375rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    @endif
</x-filament-panels::page>
