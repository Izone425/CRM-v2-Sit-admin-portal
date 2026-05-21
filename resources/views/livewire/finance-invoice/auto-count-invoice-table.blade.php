<div
    x-data="{
        selectedTotal: 0,
        selectedCount: 0,
        isCalculating: false,
        async calculate() {
            const tableEl = this.$el.querySelector('.fi-ta');
            if (!tableEl) return;

            const tableData = Alpine.$data(tableEl);
            const ids = tableData?.selectedRecords || [];

            if (ids.length === 0) {
                this.selectedTotal = 0;
                this.selectedCount = 0;
                this.isCalculating = false;
                return;
            }

            this.isCalculating = true;
            try {
                const result = await $wire.call('calculateTotal', ids);
                this.selectedTotal = result.total;
                this.selectedCount = result.count;
            } finally {
                this.isCalculating = false;
            }
        }
    }"
    x-init="
        let lastCount = -1;
        setInterval(() => {
            const tableEl = $el.querySelector('.fi-ta');
            if (!tableEl) return;
            const currentCount = Alpine.$data(tableEl)?.selectedRecords?.length || 0;
            if (currentCount !== lastCount) {
                lastCount = currentCount;
                calculate();
            }
        }, 500);
    "
>
    <style>
        .fi-ta-row.success {
            background-color: #d4edda !important;
        }
        .fi-ta-row.success:hover {
            background-color: #c3e6cb !important;
        }
        .fi-ta-row.danger {
            background-color: #f8d7da !important;
        }
        .fi-ta-row.danger:hover {
            background-color: #f1c0c5 !important;
        }
    </style>
    <div
        x-show="selectedCount > 0 || isCalculating"
        x-cloak
        class="selected-total-bar"
    >
        <span>
            Selected <span x-text="selectedCount">0</span> record(s) — Total Amount (MYR):
            <span x-text="selectedTotal.toFixed(2)">0.00</span>
        </span>
        <span
            x-bind:style="isCalculating ? 'visibility:visible' : 'visibility:hidden'"
            class="selected-total-spinner"
        ></span>
    </div>
    <style>
        .selected-total-bar {
            background-color: #dc2626;
            color: #ffffff;
            padding: 12px 16px;
            margin-bottom: 8px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            flex-direction: row;
            align-items: center;
            height: 44px;
        }
        .selected-total-spinner {
            width: 16px;
            height: 16px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
    {{ $this->table }}
</div>
