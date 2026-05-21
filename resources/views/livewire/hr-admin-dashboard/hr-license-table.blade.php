<div>
    <div style="margin-bottom: 10px; font-size: 0.875rem; color: #4b5563;">
        Total Licenses: <span style="font-weight: 600; color: #111827;">{{ number_format($this->getLicenseCount()) }}</span>
    </div>

    {{ $this->table }}
</div>
