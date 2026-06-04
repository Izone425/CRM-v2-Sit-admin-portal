<div>
    @if ($showHero)
        <header class="tt-banner">
            <h1>All Resellers</h1>
            <p>Manage and monitor all reseller accounts</p>
        </header>

        <div class="tt-stats" role="group" aria-label="Reseller summary">
            <div class="tt-stat tt-stat--total">
                <p class="tt-stat__label">Total Resellers</p>
                <p class="tt-stat__value">{{ number_format($this->getTotalCount()) }}</p>
            </div>
            <div class="tt-stat tt-stat--enabled">
                <p class="tt-stat__label">Active</p>
                <p class="tt-stat__value">{{ number_format($this->getActiveCount()) }}</p>
            </div>
            <div class="tt-stat tt-stat--disabled">
                <p class="tt-stat__label">Inactive</p>
                <p class="tt-stat__value">{{ number_format($this->getInactiveCount()) }}</p>
            </div>
            <div class="tt-stat tt-stat--subscribers">
                <p class="tt-stat__label">With Active Licenses</p>
                <p class="tt-stat__value">{{ number_format($this->getWithActiveLicensesCount()) }}</p>
            </div>
        </div>
    @endif

    {{ $this->table }}
</div>
