{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/admin-renewal-process-data-myr.blade.php --}}
<x-filament-panels::page>
    <style>
        /* Force sticky headers for Filament tables */
        .fi-ta-table {
            position: relative;
        }

        .fi-ta-table thead {
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
        }

        .fi-ta-table thead th {
            position: sticky !important;
            top: 0 !important;
            z-index: 20 !important;
            background-color: rgb(250, 250, 250) !important;
            border-bottom: 2px solid #e5e7eb !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        }

        /* Dark mode support */
        .dark .fi-ta-table thead th {
            background-color: rgb(17 24 39) !important;
            border-bottom: 2px solid rgb(55 65 81) !important;
            color: rgb(229 231 235) !important;
        }

        /* Table container with fixed height */
        .fi-ta-content {
            max-height: calc(100vh - 250px) !important;
            overflow: auto !important;
        }

        /* Ensure proper scrolling */
        .fi-ta-ctn {
            overflow: visible !important;
        }

        /* Fix for filter dropdowns to appear above sticky headers */
        [x-data*="dropdown"], .fi-dropdown-panel {
            z-index: 30 !important;
        }

        /* Renewal Dashboard Grid */
        .renewal-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1rem;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .renewal-dashboard-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 768px) {
            .renewal-dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .renewal-dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card styling */
        .renewal-card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        .renewal-card-content {
            padding: 1.25rem 0.75rem;
        }

        .renewal-card-layout {
            display: flex;
            align-items: center;
        }

        /* Icon container */
        .renewal-icon-container {
            flex-shrink: 0;
            padding: 0.75rem;
            border-radius: 0.375rem;
        }

        .renewal-icon-container.green {
            background-color: rgba(34, 197, 94, 0.1);
        }

        .renewal-icon-container.blue {
            background-color: rgba(37, 99, 235, 0.1);
        }

        .renewal-icon-container.purple {
            background-color: rgba(124, 58, 237, 0.1);
        }

        .renewal-icon-container.orange {
            background-color: rgba(249, 115, 22, 0.1);
        }

        .renewal-icon-container.red {
            background-color: rgba(220, 38, 38, 0.1);
        }

        .renewal-icon {
            width: 1.5rem;
            height: 1.5rem;
        }

        .renewal-icon-container.green .renewal-icon {
            color: rgba(34, 197, 94, 1);
        }

        .renewal-icon-container.blue .renewal-icon {
            color: rgba(37, 99, 235, 1);
        }

        .renewal-icon-container.purple .renewal-icon {
            color: rgba(124, 58, 237, 1);
        }

        .renewal-icon-container.orange .renewal-icon {
            color: rgba(249, 115, 22, 1);
        }

        .renewal-icon-container.red .renewal-icon {
            color: rgba(220, 38, 38, 1);
        }

        /* Renewal details */
        .renewal-details {
            flex: 1;
            width: 0;
            margin-left: 0.5rem;
        }

        .renewal-title {
            font-size: 0.95rem;
            font-weight: 500;
            color: #111827;
        }

        .renewal-subtitle {
            font-size: 0.75rem;
            color: #6B7280;
        }

        .renewal-amount-label {
            font-size: 1rem;
            font-weight: 500;
            color: #111827;
        }

        .renewal-amount {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .renewal-amount.green {
            color: rgba(34, 197, 94, 1);
        }

        .renewal-amount.blue {
            color: rgba(37, 99, 235, 1);
        }

        .renewal-amount.purple {
            color: rgba(124, 58, 237, 1);
        }

        .renewal-amount.orange {
            color: rgba(249, 115, 22, 1);
        }

        .renewal-amount.red {
            color: rgba(220, 38, 38, 1);
        }

        .refresh-button-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 1rem;
        }

        .refresh-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #3B82F6;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .refresh-button:hover {
            background-color: #2563EB;
        }

        .refresh-icon {
            width: 1rem;
            height: 1rem;
        }
    </style>

    <!-- Dashboard Cards -->
    @php $filteredCompanyIds = $this->getFilteredCompanyIds(); @endphp
    <livewire:admin-renewal-dashboard.process-data-myr-stats-cards
        :company-ids="$filteredCompanyIds"
        :key="'stats-cards-' . md5(json_encode($filteredCompanyIds))"
        lazy
    />

    <!-- Filament Table -->
    {{ $this->table }}

    @php $currencyLabel = 'RM'; @endphp
    {{-- Forecast Cost Modal --}}
    @if($showForecastModal)
    <div wire:click="closeForecastModal"
         style="position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9999; display:flex; align-items:center; justify-content:center;">
        <div wire:click.stop
             style="background:#fff; border-radius:12px; width:min(460px,92vw); padding:24px; box-shadow:0 20px 50px rgba(0,0,0,0.25);">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="font-size:1rem; font-weight:700; color:#1a56db; margin:0;">Forecast Cost</h3>
                <button wire:click="closeForecastModal" style="border:none; background:transparent; cursor:pointer; color:#94a3b8; font-size:1.5rem; line-height:1;">&times;</button>
            </div>

            <div style="display:flex; flex-direction:column; gap:10px; font-size:0.9rem;">
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#6b7280;">Total Headcount</span>
                    <span style="font-weight:600; color:#1f2937;">{{ number_format($forecastData['headcount']) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#6b7280;">Cost per Headcount</span>
                    <span style="font-weight:600; color:#1f2937;">{{ $currencyLabel }} {{ number_format($forecastData['rate'], 2) }}</span>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span style="color:#6b7280;">Months</span>
                    <span style="font-weight:600; color:#1f2937;">{{ $forecastData['months'] }}</span>
                </div>

                @php
                    $forecastModuleLabels = ['TA' => 'Attendance', 'TL' => 'Leave', 'TC' => 'Claim', 'TP' => 'Payroll'];
                    $forecastModuleColors = ['TA' => '#3b82f6', 'TL' => '#8b5cf6', 'TC' => '#f59e0b', 'TP' => '#10b981'];
                    $forecastTotalHc = max(0, (int) ($forecastData['headcount'] ?? 0));
                    $forecastCirc    = 2 * M_PI * 50;
                    $forecastCumulative = 0;
                    $forecastSegments = [];
                    foreach (['TA','TL','TC','TP'] as $mod) {
                        $hc  = (int) ($forecastData['modules'][$mod]['headcount'] ?? 0);
                        $pct = $forecastTotalHc > 0 ? $hc / $forecastTotalHc : 0;
                        $dash = $pct * $forecastCirc;
                        $forecastSegments[$mod] = ['dash' => $dash, 'offset' => -$forecastCumulative, 'pct' => $pct];
                        $forecastCumulative += $dash;
                    }
                @endphp
                <div style="margin-top:6px; padding-top:14px; border-top:1px dashed #e5e7eb;">
                    <div style="font-size:0.7rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em; margin-bottom:12px;">By Module</div>
                    <div style="display:flex; align-items:center; gap:18px;">
                        <div style="position:relative; flex-shrink:0; width:128px; height:128px;">
                            <svg width="128" height="128" viewBox="0 0 120 120" style="display:block;">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="#f1f5f9" stroke-width="18"/>
                                <g transform="rotate(-90 60 60)">
                                    @foreach(['TA','TL','TC','TP'] as $mod)
                                        @if($forecastSegments[$mod]['pct'] > 0)
                                            <circle cx="60" cy="60" r="50" fill="none"
                                                stroke="{{ $forecastModuleColors[$mod] }}"
                                                stroke-width="18"
                                                stroke-dasharray="{{ $forecastSegments[$mod]['dash'] }} {{ $forecastCirc - $forecastSegments[$mod]['dash'] }}"
                                                stroke-dashoffset="{{ $forecastSegments[$mod]['offset'] }}"/>
                                        @endif
                                    @endforeach
                                </g>
                            </svg>
                            <div style="position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none;">
                                <span style="font-size:0.55rem; font-weight:700; color:#9ca3af; letter-spacing:0.1em;">HEADCOUNT</span>
                                <span style="font-size:1.1rem; font-weight:800; color:#1f2937; line-height:1.1; margin-top:2px;">{{ number_format($forecastTotalHc) }}</span>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:9px; flex:1; min-width:0;">
                            @foreach(['TA','TL','TC','TP'] as $mod)
                                <div style="display:flex; align-items:flex-start; gap:8px; font-size:0.8rem;">
                                    <span style="width:9px; height:9px; border-radius:9999px; background:{{ $forecastModuleColors[$mod] }}; margin-top:5px; flex-shrink:0;"></span>
                                    <div style="flex:1; min-width:0;">
                                        <div style="display:flex; justify-content:space-between; align-items:baseline; gap:6px;">
                                            <span style="font-weight:600; color:#1f2937;">{{ $forecastModuleLabels[$mod] }}</span>
                                            <span style="font-weight:600; color:#1f2937; white-space:nowrap;">{{ $currencyLabel }} {{ number_format($forecastData['modules'][$mod]['cost'] ?? 0, 2) }}</span>
                                        </div>
                                        <div style="font-size:0.7rem; color:#9ca3af; margin-top:1px;">
                                            {{ number_format($forecastData['modules'][$mod]['headcount'] ?? 0) }} HC
                                            @if($forecastTotalHc > 0)
                                                <span style="color:#cbd5e1;">·</span> {{ number_format($forecastSegments[$mod]['pct'] * 100, 1) }}%
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <hr style="border:none; border-top:1px solid #e5e7eb; margin:6px 0;">
                <div style="display:flex; justify-content:space-between; font-size:1rem;">
                    <span style="font-weight:700; color:#1f2937;">Total Forecast Cost</span>
                    <span style="font-weight:700; color:#2563eb;">{{ $currencyLabel }} {{ number_format($forecastData['total'], 2) }}</span>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; margin-top:20px;">
                <button wire:click="closeForecastModal"
                    style="padding:8px 16px; border:none; border-radius:8px; background:#1f2937; color:#fff; font-size:0.85rem; font-weight:600; cursor:pointer;">Close</button>
            </div>
        </div>
    </div>
    @endif
</x-filament-panels::page>
