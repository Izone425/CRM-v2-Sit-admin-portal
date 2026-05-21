<x-filament-panels::page>
    <style>
        .debtor-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        @media (max-width: 1024px) {
            .debtor-dashboard-grid { grid-template-columns: repeat(3, 1fr); }
        }
        @media (max-width: 768px) {
            .debtor-dashboard-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .debtor-dashboard-grid { grid-template-columns: 1fr; }
        }
        .debtor-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        }
        .debtor-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .debtor-card.active.primary { border-color: rgba(79, 70, 229, 1); }
        .debtor-card.active.blue    { border-color: rgba(37, 99, 235, 1); }
        .debtor-card.active.purple  { border-color: rgba(124, 58, 237, 1); }
        .debtor-card.active.amber   { border-color: rgba(217, 119, 6, 1); }
        .debtor-card-content { padding: 1.25rem 1rem; }
        .debtor-card-layout { display: flex; align-items: center; }
        .debtor-icon-container {
            flex-shrink: 0;
            padding: 0.75rem;
            border-radius: 0.375rem;
        }
        .debtor-icon-container.primary { background-color: rgba(79, 70, 229, 0.1); }
        .debtor-icon-container.blue { background-color: rgba(37, 99, 235, 0.1); }
        .debtor-icon-container.purple { background-color: rgba(124, 58, 237, 0.1); }
        .debtor-icon-container.red { background-color: rgba(220, 38, 38, 0.1); }
        .debtor-icon-container.amber { background-color: rgba(217, 119, 6, 0.1); }
        .debtor-icon { width: 1.5rem; height: 1.5rem; }
        .debtor-icon-container.primary .debtor-icon { color: rgba(79, 70, 229, 1); }
        .debtor-icon-container.blue .debtor-icon { color: rgba(37, 99, 235, 1); }
        .debtor-icon-container.purple .debtor-icon { color: rgba(124, 58, 237, 1); }
        .debtor-icon-container.red .debtor-icon { color: rgba(220, 38, 38, 1); }
        .debtor-icon-container.amber .debtor-icon { color: rgba(217, 119, 6, 1); }
        .debtor-details { flex: 1; width: 0; margin-left: 1.25rem; }
        .debtor-title { font-size: 1rem; font-weight: 500; color: #111827; }
        .debtor-subtitle { font-size: 0.875rem; color: #6B7280; }
        .debtor-amount-label { font-size: 1rem; font-weight: 500; color: #111827; }
        .debtor-amount { font-size: 1.25rem; font-weight: 700; }
        .debtor-amount.primary { color: rgba(79, 70, 229, 1); }
        .debtor-amount.blue { color: rgba(37, 99, 235, 1); }
        .debtor-amount.purple { color: rgba(124, 58, 237, 1); }
        .debtor-amount.red { color: rgba(220, 38, 38, 1); }
        .debtor-amount.amber { color: rgba(217, 119, 6, 1); }
    </style>

    <!-- Dashboard Cards -->
    <div class="debtor-dashboard-grid">
        <!-- Box 1: All Debtor -->
        <div class="debtor-card primary {{ $activeAnalysisFilter === 'all' ? 'active' : '' }}"
             wire:click="setAnalysisFilter('all')">
            <div class="debtor-card-content">
                <div class="debtor-card-layout">
                    <div class="debtor-icon-container primary">
                        <svg class="debtor-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="debtor-details">
                        <dt class="debtor-title">All Debtor</dt>
                        <dd>
                            <div class="debtor-subtitle">Total Invoice: {{ $allDebtorStats['total_invoices'] }}</div>
                            <div class="debtor-amount-label">Total Amount:</div>
                            <div class="debtor-amount primary">{{ $allDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 2: HRDF Debtor -->
        <div class="debtor-card blue {{ $activeAnalysisFilter === 'hrdf' ? 'active' : '' }}"
             wire:click="setAnalysisFilter('hrdf')">
            <div class="debtor-card-content">
                <div class="debtor-card-layout">
                    <div class="debtor-icon-container blue">
                        <svg class="debtor-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <div class="debtor-details">
                        <dt class="debtor-title">HRDF Debtor</dt>
                        <dd>
                            <div class="debtor-subtitle">Total Invoice: {{ $hrdfDebtorStats['total_invoices'] }}</div>
                            <div class="debtor-amount-label">Total Amount:</div>
                            <div class="debtor-amount blue">{{ $hrdfDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 3: Product Debtor -->
        <div class="debtor-card purple {{ $activeAnalysisFilter === 'product' ? 'active' : '' }}"
             wire:click="setAnalysisFilter('product')">
            <div class="debtor-card-content">
                <div class="debtor-card-layout">
                    <div class="debtor-icon-container purple">
                        <svg class="debtor-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="debtor-details">
                        <dt class="debtor-title">Product Debtor</dt>
                        <dd>
                            <div class="debtor-subtitle">Total Invoice: {{ $productDebtorStats['total_invoices'] }}</div>
                            <div class="debtor-amount-label">Total Amount:</div>
                            <div class="debtor-amount purple">{{ $productDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Box 4: Reseller Debtor -->
        <div class="debtor-card amber {{ $activeAnalysisFilter === 'reseller' ? 'active' : '' }}"
             wire:click="setAnalysisFilter('reseller')">
            <div class="debtor-card-content">
                <div class="debtor-card-layout">
                    <div class="debtor-icon-container amber">
                        <svg class="debtor-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                    <div class="debtor-details">
                        <dt class="debtor-title">Reseller Debtor</dt>
                        <dd>
                            <div class="debtor-subtitle">Total Invoice: {{ $resellerDebtorStats['total_invoices'] }}</div>
                            <div class="debtor-amount-label">Total Amount:</div>
                            <div class="debtor-amount amber">{{ $resellerDebtorStats['formatted_amount'] }}</div>
                        </dd>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filament Table -->
    {{ $this->table }}
</x-filament-panels::page>
