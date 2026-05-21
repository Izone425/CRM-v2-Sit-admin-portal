<x-filament-panels::page>
    @php
        $companies = $this->getTerminatedCompanies();
    @endphp

    <style>
        .terminated-container {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #E5E7EB;
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #F3F4F6;
        }

        .page-title {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .company-count {
            padding: 4px 12px;
            background: #FEE2E2;
            color: #DC2626;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .company-item {
            margin-bottom: 12px;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.2s;
        }

        .company-item:hover {
            border-color: #DC2626;
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.1);
        }

        .company-header {
            padding: 16px 20px;
            background: linear-gradient(to right, #FEF2F2, white);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }

        .company-header:hover {
            background: linear-gradient(to right, #FEE2E2, #FEF2F2);
        }

        .company-name {
            font-size: 15px;
            font-weight: 600;
            color: #DC2626;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .company-id {
            font-size: 12px;
            color: #6B7280;
            font-weight: 500;
            padding: 2px 8px;
            background: white;
            border-radius: 4px;
            border: 1px solid #E5E7EB;
        }

        .expand-icon {
            color: #9CA3AF;
            transition: transform 0.2s;
            font-size: 20px;
        }

        .expand-icon.expanded {
            transform: rotate(90deg);
        }

        .modules-section {
            padding: 20px;
            background: #FAFBFC;
            border-top: 1px solid #E5E7EB;
        }

        .modules-grid {
            display: grid;
            gap: 12px;
        }

        .module-card {
            background: white;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 16px;
            align-items: center;
        }

        .module-label {
            font-size: 11px;
            color: #6B7280;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .module-value {
            font-size: 14px;
            color: #111827;
            font-weight: 600;
        }

        .module-name {
            padding: 6px 12px;
            background: #EEF2FF;
            color: #4338CA;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .headcount-badge {
            padding: 6px 12px;
            background: #F0FDF4;
            color: #15803D;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .expiry-date {
            padding: 6px 12px;
            background: #FEE2E2;
            color: #DC2626;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            display: inline-block;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 16px;
        }

        .empty-text {
            color: #9CA3AF;
            font-size: 14px;
        }
    </style>

    <div class="terminated-container">
        <div class="header-section">
            <h2 class="page-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 24px; height: 24px; color: #DC2626;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                License Terminated (2025)
            </h2>
            <span class="company-count">{{ count($companies) }} Companies</span>
        </div>

        @if(count($companies) > 0)
            @foreach($companies as $company)
                <div class="company-item">
                    <div class="company-header" wire:click="toggleCompany({{ $company->f_company_id }})">
                        <div class="company-name">
                            <span class="company-id">ID: {{ $company->f_company_id }}</span>
                            <span>{{ strtoupper($company->f_company_name) }}</span>
                        </div>
                        <span class="expand-icon {{ in_array($company->f_company_id, $expandedCompanies) ? 'expanded' : '' }}">
                            ▶
                        </span>
                    </div>

                    @if(in_array($company->f_company_id, $expandedCompanies))
                        @php
                            $modules = $this->getCompanyModules($company->f_company_id);
                        @endphp

                        <div class="modules-section">
                            <div style="margin-bottom: 16px;">
                                <h3 style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 12px;">
                                    Terminated Modules ({{ count($modules) }})
                                </h3>
                            </div>

                            <div class="modules-grid">
                                @foreach($modules as $module)
                                    <div class="module-card">
                                        <div>
                                            <div class="module-label">Module</div>
                                            <span class="module-name">{{ $module->f_module }}</span>
                                        </div>
                                        <div>
                                            <div class="module-label">Headcount</div>
                                            <span class="headcount-badge">{{ number_format($module->f_head_count) }}</span>
                                        </div>
                                        <div>
                                            <div class="module-label">Expired On</div>
                                            <span class="expiry-date">{{ \Carbon\Carbon::parse($module->f_expiry_date)->format('d M Y') }}</span>
                                        </div>
                                        <div>
                                            <div class="module-label">License Key</div>
                                            <div class="module-value" style="font-size: 11px; color: #6B7280; font-family: monospace;">
                                                {{ substr($module->f_license_key, 0, 20) }}...
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <div class="empty-state">
                <div class="empty-icon">✓</div>
                <div class="empty-text">No terminated licenses found for 2025</div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
