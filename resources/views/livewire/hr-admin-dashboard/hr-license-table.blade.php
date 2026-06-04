<div>
    <header class="tt-banner">
        <h1>All Licenses</h1>
        <p>Manage and monitor all software licenses</p>
    </header>

    <div class="tt-stats" role="group" aria-label="License summary">
        <div class="tt-stat tt-stat--total">
            <p class="tt-stat__label">Total Licenses</p>
            <p class="tt-stat__value">{{ number_format($this->getLicenseCount()) }}</p>
        </div>
        <div class="tt-stat tt-stat--enabled">
            <p class="tt-stat__label">Enabled</p>
            <p class="tt-stat__value">{{ number_format($this->getEnabledCount()) }}</p>
        </div>
        <div class="tt-stat tt-stat--disabled">
            <p class="tt-stat__label">Disabled</p>
            <p class="tt-stat__value">{{ number_format($this->getDisabledCount()) }}</p>
        </div>
        <div class="tt-stat tt-stat--subscribers">
            <p class="tt-stat__label">Subscribers</p>
            <p class="tt-stat__value">{{ number_format($this->getSubscriberCount()) }}</p>
        </div>
    </div>

    {{ $this->table }}
</div>
