<x-filament-panels::page>
    @php
        $rcStats = \Illuminate\Support\Facades\Cache::remember('rc_dashboard_stats', 300, function () {
            $rawCount = \App\Models\CrmInvoiceDetail::where('f_invoice_no', 'LIKE', 'AP%')
                ->where('f_created_time', '>=', '2026-01-01')
                ->whereRaw("(SELECT tt.f_status FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) IN (0, 1)")
                ->count();

            $pendingResellerCount = \App\Models\ResellerCommissionHandover::where('status', 'pending_reseller')->count();
            $pendingFinanceCount = \App\Models\ResellerCommissionHandover::where('status', 'pending_finance')->count();
            $completedCount = \App\Models\ResellerCommissionHandover::where('status', 'completed')->count();

            $fullPaymentInvNos = \App\Models\DebtorAging::where('outstanding', 0)
                ->pluck('invoice_number')
                ->toArray();

            $rcBaseQuery = \App\Models\CrmInvoiceDetail::where('f_invoice_no', 'LIKE', 'AP%')
                ->where('f_created_time', '>=', '2026-01-01')
                ->whereRaw("(SELECT tt.f_status FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) IN (0, 1)")
                ->whereRaw("(SELECT tt.f_auto_count_inv FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) IS NOT NULL")
                ->whereRaw("(SELECT tt.f_auto_count_inv FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) != ''");

            if (!empty($fullPaymentInvNos)) {
                $placeholders = implode(',', array_fill(0, count($fullPaymentInvNos), '?'));

                $invoiceUpdateCount = \Illuminate\Support\Facades\DB::connection('frontenddb')->query()->fromSub(
                    (clone $rcBaseQuery)
                        ->select('crm_invoice_details.f_id', \Illuminate\Support\Facades\DB::raw("TRIM(BOTH '\\r\\n' FROM (SELECT tt.f_auto_count_inv FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1)) as autocount_inv_no"))
                        ->havingRaw("autocount_inv_no NOT IN ({$placeholders})", $fullPaymentInvNos),
                    'sub'
                )->count();

                $paymentUpdateCount = \Illuminate\Support\Facades\DB::connection('frontenddb')->query()->fromSub(
                    (clone $rcBaseQuery)
                        ->select('crm_invoice_details.f_id', \Illuminate\Support\Facades\DB::raw("TRIM(BOTH '\\r\\n' FROM (SELECT tt.f_auto_count_inv FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1)) as autocount_inv_no"))
                        ->havingRaw("autocount_inv_no IN ({$placeholders})", $fullPaymentInvNos),
                    'sub'
                )->count();
            } else {
                $invoiceUpdateCount = (clone $rcBaseQuery)->count();
                $paymentUpdateCount = 0;
            }

            return compact('rawCount', 'pendingResellerCount', 'pendingFinanceCount', 'completedCount', 'invoiceUpdateCount', 'paymentUpdateCount');
        });

        $resellerCommissionCount = $rcStats['rawCount'];
        $rcPendingResellerCount  = $rcStats['pendingResellerCount'];
        $rcPendingFinanceCount   = $rcStats['pendingFinanceCount'];
        $rcCompletedCount        = $rcStats['completedCount'];
        $rcInvoiceUpdateCount    = $rcStats['invoiceUpdateCount'];
        $rcPaymentUpdateCount    = $rcStats['paymentUpdateCount'];
    @endphp

    <style>
        .rc-stats-container {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .rc-stat-box {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            min-height: 65px;
        }

        .rc-stat-box:hover {
            background-color: #f9fafb;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }

        .rc-stat-box.selected {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.18);
        }

        .rc-stat-box .stat-info {
            display: flex;
            flex-direction: column;
        }

        .rc-stat-box .stat-label {
            color: #6b7280;
            font-size: 13px;
            font-weight: 500;
        }

        .rc-stat-box .stat-count {
            font-size: 22px;
            font-weight: bold;
        }

        /* Color variants */
        .rc-stat-box.raw-data { border-left-color: #0891b2; }
        .rc-stat-box.raw-data .stat-count { color: #0891b2; }
        .rc-stat-box.raw-data.selected { background-color: rgba(8, 145, 178, 0.08); outline: 2px solid #0891b2; outline-offset: -2px; }

        .rc-stat-box.invoice-update { border-left-color: #f59e0b; }
        .rc-stat-box.invoice-update .stat-count { color: #f59e0b; }
        .rc-stat-box.invoice-update.selected { background-color: rgba(245, 158, 11, 0.08); outline: 2px solid #f59e0b; outline-offset: -2px; }

        .rc-stat-box.payment-update { border-left-color: #2563eb; }
        .rc-stat-box.payment-update .stat-count { color: #2563eb; }
        .rc-stat-box.payment-update.selected { background-color: rgba(37, 99, 235, 0.08); outline: 2px solid #2563eb; outline-offset: -2px; }

        .rc-stat-box.pending-reseller { border-left-color: #e11d48; }
        .rc-stat-box.pending-reseller .stat-count { color: #e11d48; }
        .rc-stat-box.pending-reseller.selected { background-color: rgba(225, 29, 72, 0.08); outline: 2px solid #e11d48; outline-offset: -2px; }

        .rc-stat-box.pending-finance { border-left-color: #7c3aed; }
        .rc-stat-box.pending-finance .stat-count { color: #7c3aed; }
        .rc-stat-box.pending-finance.selected { background-color: rgba(124, 58, 237, 0.08); outline: 2px solid #7c3aed; outline-offset: -2px; }

        .rc-stat-box.rc-completed { border-left-color: #10b981; }
        .rc-stat-box.rc-completed .stat-count { color: #10b981; }
        .rc-stat-box.rc-completed.selected { background-color: rgba(16, 185, 129, 0.08); outline: 2px solid #10b981; outline-offset: -2px; }

        .rc-content-area {
            min-height: 400px;
        }

        .rc-hint-message {
            text-align: center;
            padding: 48px;
            color: #6b7280;
        }

        .rc-hint-message h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .bg-red-100 td {
            background-color: rgb(254 226 226) !important;
        }

        .tippy-content {
            white-space: pre-line;
        }
    </style>

    <div x-data="{
        selectedStat: 'rc-raw-data',
        loadedStats: ['rc-raw-data'],

        setSelectedStat(value) {
            this.selectedStat = value;
            if (!this.loadedStats.includes(value)) {
                this.loadedStats.push(value);
            }
        }
    }">
        <!-- Stats Row -->
        <div class="rc-stats-container">
            <div class="rc-stat-box raw-data"
                 :class="{'selected': selectedStat === 'rc-raw-data'}"
                 @click="setSelectedStat('rc-raw-data')">
                <div class="stat-info">
                    <div class="stat-label">Raw Data</div>
                </div>
                <div class="stat-count">{{ $resellerCommissionCount }}</div>
            </div>

            <div class="rc-stat-box invoice-update"
                 :class="{'selected': selectedStat === 'rc-invoice-update'}"
                 @click="setSelectedStat('rc-invoice-update')">
                <div class="stat-info">
                    <div class="stat-label">Invoice Update</div>
                </div>
                <div class="stat-count">{{ $rcInvoiceUpdateCount }}</div>
            </div>

            <div class="rc-stat-box payment-update"
                 :class="{'selected': selectedStat === 'rc-payment-update'}"
                 @click="setSelectedStat('rc-payment-update')">
                <div class="stat-info">
                    <div class="stat-label">Payment Update</div>
                </div>
                <div class="stat-count">{{ $rcPaymentUpdateCount }}</div>
            </div>

            <div class="rc-stat-box pending-reseller"
                 :class="{'selected': selectedStat === 'rc-pending-reseller'}"
                 @click="setSelectedStat('rc-pending-reseller')">
                <div class="stat-info">
                    <div class="stat-label">Pending Reseller</div>
                </div>
                <div class="stat-count">{{ $rcPendingResellerCount }}</div>
            </div>

            <div class="rc-stat-box pending-finance"
                 :class="{'selected': selectedStat === 'rc-pending-finance'}"
                 @click="setSelectedStat('rc-pending-finance')">
                <div class="stat-info">
                    <div class="stat-label">Pending Finance</div>
                </div>
                <div class="stat-count">{{ $rcPendingFinanceCount }}</div>
            </div>

            <div class="rc-stat-box rc-completed"
                 :class="{'selected': selectedStat === 'rc-completed'}"
                 @click="setSelectedStat('rc-completed')">
                <div class="stat-info">
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-count">{{ $rcCompletedCount }}</div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="rc-content-area">
            <template x-if="loadedStats.includes('rc-raw-data')">
                <div x-show="selectedStat === 'rc-raw-data'" x-transition>
                    <livewire:admin-finance-dashboard.reseller-commission-raw-data-table lazy />
                </div>
            </template>

            <template x-if="loadedStats.includes('rc-invoice-update')">
                <div x-show="selectedStat === 'rc-invoice-update'" x-transition>
                    <livewire:admin-finance-dashboard.reseller-commission-invoice-update-table lazy />
                </div>
            </template>

            <template x-if="loadedStats.includes('rc-payment-update')">
                <div x-show="selectedStat === 'rc-payment-update'" x-transition>
                    <livewire:admin-finance-dashboard.reseller-commission-payment-update-table lazy />
                </div>
            </template>

            <template x-if="loadedStats.includes('rc-pending-reseller')">
                <div x-show="selectedStat === 'rc-pending-reseller'" x-transition>
                    <livewire:admin-finance-dashboard.rc-pending-reseller-table lazy />
                </div>
            </template>

            <template x-if="loadedStats.includes('rc-pending-finance')">
                <div x-show="selectedStat === 'rc-pending-finance'" x-transition>
                    <livewire:admin-finance-dashboard.rc-pending-finance-table lazy />
                </div>
            </template>

            <template x-if="loadedStats.includes('rc-completed')">
                <div x-show="selectedStat === 'rc-completed'" x-transition>
                    <livewire:admin-finance-dashboard.rc-completed-table lazy />
                </div>
            </template>
        </div>
    </div>
</x-filament-panels::page>
