<x-filament-panels::page>
    <style>
        .v2-tabs { display: flex; gap: 4px; padding: 4px; background: #f3f4f6; border-radius: 10px; margin-bottom: 24px; width: fit-content; }
        .v2-tab-btn {
            padding: 10px 28px; font-size: 0.875rem; font-weight: 600; border: none; cursor: pointer;
            border-radius: 8px; transition: all 0.2s ease; color: #6b7280; background: transparent;
        }
        .v2-tab-btn:hover { color: #374151; background: rgba(255,255,255,0.5); }
        .v2-tab-btn.active { color: #1a56db; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    </style>

    <div x-data="{ activeTab: @entangle('activeTab') }">
        {{-- Tab Navigation --}}
        <div class="v2-tabs">
            <button @click="$wire.switchTab('dashboard')" class="v2-tab-btn" :class="{ 'active': activeTab === 'dashboard' }">
                Dashboard
            </button>
            <button @click="$wire.switchTab('process_data')" class="v2-tab-btn" :class="{ 'active': activeTab === 'process_data' }">
                Process Data
            </button>
        </div>

        {{-- Tab 1: Dashboard --}}
        <div x-show="activeTab === 'dashboard'" x-transition>
            @include('filament.pages.adminrenewalv2')
        </div>

        {{-- Tab 2: Process Data --}}
        <div x-show="activeTab === 'process_data'" x-transition>
            {{-- Stats Boxes --}}
            <div style="display:flex; gap:16px; margin-bottom:24px; flex-wrap:wrap;">
                <div style="background:#fff; border:1px solid #e5e7eb; border-top:4px solid #3b82f6; border-radius:12px; padding:20px 24px; flex:1; min-width:180px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <p style="font-size:0.85rem; color:#6b7280; margin:0; font-weight:600;">New</p>
                    <p style="font-size:2rem; font-weight:700; color:#3b82f6; margin:4px 0;">{{ $newStats['total_companies'] ?? 0 }}</p>
                    <p style="font-size:0.8rem; color:#6b7280; margin:0;">RM {{ number_format($newStats['total_amount'] ?? 0, 2) }}</p>
                </div>

                <div style="background:#fff; border:1px solid #e5e7eb; border-top:4px solid #f59e0b; border-radius:12px; padding:20px 24px; flex:1; min-width:180px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <p style="font-size:0.85rem; color:#6b7280; margin:0; font-weight:600;">Pending Confirmation</p>
                    <p style="font-size:2rem; font-weight:700; color:#f59e0b; margin:4px 0;">{{ $pendingConfirmationStats['total_companies'] ?? 0 }}</p>
                    <p style="font-size:0.8rem; color:#6b7280; margin:0;">RM {{ number_format($pendingConfirmationStats['total_amount'] ?? 0, 2) }}</p>
                </div>

                <div style="background:#fff; border:1px solid #e5e7eb; border-top:4px solid #dc2626; border-radius:12px; padding:20px 24px; flex:1; min-width:180px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <p style="font-size:0.85rem; color:#6b7280; margin:0; font-weight:600;">Pending Payment</p>
                    <p style="font-size:2rem; font-weight:700; color:#dc2626; margin:4px 0;">{{ $pendingPaymentStats['total_companies'] ?? 0 }}</p>
                    <p style="font-size:0.8rem; color:#6b7280; margin:0;">RM {{ number_format($pendingPaymentStats['total_amount'] ?? 0, 2) }}</p>
                </div>

                <div style="background:#fff; border:1px solid #e5e7eb; border-top:4px solid #10b981; border-radius:12px; padding:20px 24px; flex:1; min-width:180px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <p style="font-size:0.85rem; color:#6b7280; margin:0; font-weight:600;">Completed Renewal</p>
                    <p style="font-size:2rem; font-weight:700; color:#10b981; margin:4px 0;">{{ $completedRenewalStats['total_companies'] ?? 0 }}</p>
                    <p style="font-size:0.8rem; color:#6b7280; margin:0;">RM {{ number_format($completedRenewalStats['total_amount'] ?? 0, 2) }}</p>
                </div>

                <div style="background:#fff; border:1px solid #e5e7eb; border-top:4px solid #8b5cf6; border-radius:12px; padding:20px 24px; flex:1; min-width:180px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                    <p style="font-size:0.85rem; color:#6b7280; margin:0; font-weight:600;">Renewal Forecast</p>
                    <p style="font-size:2rem; font-weight:700; color:#8b5cf6; margin:4px 0;">{{ $renewalForecastStats['total_companies'] ?? 0 }}</p>
                    <p style="font-size:0.8rem; color:#6b7280; margin:0;">RM {{ number_format($renewalForecastStats['total_amount'] ?? 0, 2) }}</p>
                </div>
            </div>

            {{-- Table --}}
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>
