<x-filament-panels::page>
    <style>
        .ta-row:hover { background: #f0f9ff !important; }
        .ta-chevron { transition: transform 0.2s; }
        .ta-chevron.open { transform: rotate(90deg); }
        .ta-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 14px;
            font-weight: 700;
            color: #ffffff;
        }
        .ta-icon.green { background: #16a34a; }
        .ta-icon.red { background: #dc2626; }
        .ta-icon.gray { background: #d1d5db; }
    </style>


    {{-- Filter Bar --}}
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px 20px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        <div style="display:flex; align-items:center; gap:12px;">
            {{-- Filter Mode Toggle --}}
            <div style="display:flex; align-items:center; gap:4px; background:#f1f5f9; border-radius:8px; padding:2px;">
                <button wire:click="$set('filterMode', 'all')"
                    style="padding:6px 14px; border-radius:6px; border:none; font-size:0.8rem; font-weight:600; cursor:pointer;
                    {{ $filterMode === 'all' ? 'background:#1a56db; color:#fff;' : 'background:transparent; color:#64748b;' }}">
                    All
                </button>
                <button wire:click="$set('filterMode', 'year')"
                    style="padding:6px 14px; border-radius:6px; border:none; font-size:0.8rem; font-weight:600; cursor:pointer;
                    {{ $filterMode === 'year' ? 'background:#1a56db; color:#fff;' : 'background:transparent; color:#64748b;' }}">
                    Year
                </button>
                <button wire:click="$set('filterMode', 'month')"
                    style="padding:6px 14px; border-radius:6px; border:none; font-size:0.8rem; font-weight:600; cursor:pointer;
                    {{ $filterMode === 'month' ? 'background:#1a56db; color:#fff;' : 'background:transparent; color:#64748b;' }}">
                    Month
                </button>
                <button wire:click="$set('filterMode', 'range')"
                    style="padding:6px 14px; border-radius:6px; border:none; font-size:0.8rem; font-weight:600; cursor:pointer;
                    {{ $filterMode === 'range' ? 'background:#1a56db; color:#fff;' : 'background:transparent; color:#64748b;' }}">
                    Range
                </button>
            </div>

            @if($filterMode === 'year')
            {{-- Year dropdown --}}
            <select wire:model.change="selectedYear" style="padding:7px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:0.8rem; background:#fff;">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
            @elseif($filterMode === 'month')
                {{-- Month picker --}}
                <input type="month" wire:model.change="selectedMonthYear"
                    style="padding:7px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:0.8rem; background:#fff;"
                    value="{{ $selectedYear }}-{{ str_pad($selectedMonth, 2, '0', STR_PAD_LEFT) }}">
            @elseif($filterMode === 'range')
                {{-- Date Range --}}
                <div style="display:flex; align-items:center; gap:4px;">
                    <input type="date" wire:model.change="startDate" style="padding:7px 8px; border:1px solid #d1d5db; border-radius:8px; font-size:0.75rem;">
                    <span style="color:#9ca3af; font-size:0.75rem;">to</span>
                    <input type="date" wire:model.change="endDate" style="padding:7px 8px; border:1px solid #d1d5db; border-radius:8px; font-size:0.75rem;">
                </div>
            @endif

            {{-- Region Filter --}}
            <div style="display:flex; align-items:center; gap:4px; background:#f1f5f9; border-radius:8px; padding:2px;">
                <button wire:click="$set('region', 'all')"
                    style="padding:6px 12px; border-radius:6px; border:none; font-size:0.8rem; font-weight:600; cursor:pointer;
                    {{ $region === 'all' ? 'background:#1a56db; color:#fff;' : 'background:transparent; color:#64748b;' }}">
                    All
                </button>
                <button wire:click="$set('region', 'malaysia')"
                    style="padding:6px 12px; border-radius:6px; border:none; font-size:0.8rem; font-weight:600; cursor:pointer;
                    {{ $region === 'malaysia' ? 'background:#1a56db; color:#fff;' : 'background:transparent; color:#64748b;' }}">
                    Malaysia
                </button>
                <button wire:click="$set('region', 'overseas')"
                    style="padding:6px 12px; border-radius:6px; border:none; font-size:0.8rem; font-weight:600; cursor:pointer;
                    {{ $region === 'overseas' ? 'background:#1a56db; color:#fff;' : 'background:transparent; color:#64748b;' }}">
                    Overseas
                </button>
            </div>

            {{-- Separator --}}
            <div style="width:1px; height:28px; background:#e5e7eb;"></div>

            {{-- Search --}}
            <div style="display:flex; align-items:center; gap:8px;">
                <div style="position:relative; width:180px;">
                    <svg style="position:absolute; left:10px; top:50%; transform:translateY(-50%); width:16px; height:16px; color:#9ca3af; pointer-events:none;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input type="text" wire:model.live.debounce.500ms="search"
                        placeholder="Search"
                        style="width:100%; padding:7px 12px 7px 34px; border:1px solid #d1d5db; border-radius:8px; font-size:0.8rem; outline:none;"
                        onfocus="this.style.borderColor='#06b6d4'" onblur="this.style.borderColor='#d1d5db'">
                </div>
            </div>

            {{-- Combined Filter --}}
            @php
                $activeFilterCount = ($categoryFilter !== 'all' ? 1 : 0) + ($reasonFilter !== 'all' ? 1 : 0);
            @endphp
            <div x-data="{ open: false }" style="position:relative; margin-left:auto;">
                <button @click="open = !open"
                    style="display:flex; align-items:center; gap:6px; padding:7px 12px; border:1px solid {{ $activeFilterCount > 0 ? '#1a56db' : '#d1d5db' }}; border-radius:8px; background:{{ $activeFilterCount > 0 ? '#eff6ff' : '#fff' }}; cursor:pointer; font-size:0.8rem; color:{{ $activeFilterCount > 0 ? '#1a56db' : '#475569' }}; font-weight:500;">
                    <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Filter
                    @if($activeFilterCount > 0)
                        <span style="background:#1a56db; color:#fff; border-radius:9999px; width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; font-size:0.65rem; font-weight:700;">{{ $activeFilterCount }}</span>
                    @endif
                </button>
                <div x-show="open" @click.away="open = false" x-transition
                    style="position:absolute; right:0; top:100%; margin-top:4px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:50; min-width:200px; overflow:hidden;">
                    {{-- Category Section --}}
                    <div style="padding:8px 14px 4px; font-size:0.7rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em;">Category</div>
                    @foreach(['all' => 'All', 'end_user' => 'End User', 'dealer' => 'Dealer', 'distributor' => 'Distributor'] as $value => $label)
                        <button wire:click="$set('categoryFilter', '{{ $value }}')"
                            style="display:flex; align-items:center; justify-content:space-between; width:100%; padding:8px 14px; border:none; background:#fff; cursor:pointer; font-size:0.8rem; color:#475569; text-align:left;"
                            onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                            {{ $label }}
                            @if($categoryFilter === $value) <svg style="width:14px; height:14px; color:#1a56db;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> @endif
                        </button>
                    @endforeach

                    {{-- Divider --}}
                    <div style="height:1px; background:#e5e7eb; margin:4px 0;"></div>

                    {{-- Reason Section --}}
                    <div style="padding:8px 14px 4px; font-size:0.7rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em;">Reason</div>
                    @foreach(['all' => 'All', 'completed' => 'Completed', 'pending' => 'Pending'] as $value => $label)
                        <button wire:click="$set('reasonFilter', '{{ $value }}')"
                            style="display:flex; align-items:center; justify-content:space-between; width:100%; padding:8px 14px; border:none; background:#fff; cursor:pointer; font-size:0.8rem; color:#475569; text-align:left;"
                            onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                            {{ $label }}
                            @if($reasonFilter === $value) <svg style="width:14px; height:14px; color:#1a56db;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> @endif
                        </button>
                    @endforeach

                    @if($activeFilterCount > 0)
                        <div style="height:1px; background:#e5e7eb; margin:4px 0;"></div>
                        <button wire:click="$set('categoryFilter', 'all')" x-on:click="$wire.set('reasonFilter', 'all'); open = false"
                            style="display:flex; align-items:center; gap:6px; width:100%; padding:8px 14px; border:none; background:#fff; cursor:pointer; font-size:0.8rem; color:#dc2626; text-align:left;"
                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
                            Reset filters
                        </button>
                    @endif
                </div>
            </div>

            {{-- Export Button --}}
            <button wire:click="exportExcel"
                style="display:flex; align-items:center; gap:6px; padding:7px 12px; border:none; border-radius:8px; background:#16a34a; cursor:pointer; font-size:0.8rem; color:#fff; font-weight:600;"
                onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
                <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Export
            </button>

            {{-- View Mode Toggle --}}
            <div style="display:flex; align-items:center; gap:4px; background:#f1f5f9; border-radius:8px; padding:2px;">
                <button wire:click="$set('viewMode', 'list')"
                    style="padding:6px 12px; border-radius:6px; border:none; cursor:pointer; display:flex; align-items:center; gap:4px; font-size:0.8rem; font-weight:600;
                    {{ $viewMode === 'list' ? 'background:#1a56db; color:#fff;' : 'background:transparent; color:#64748b;' }}">
                    <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                    </svg>
                    List
                </button>
                <button wire:click="$set('viewMode', 'chart')"
                    style="padding:6px 12px; border-radius:6px; border:none; cursor:pointer; display:flex; align-items:center; gap:4px; font-size:0.8rem; font-weight:600;
                    {{ $viewMode === 'chart' ? 'background:#1a56db; color:#fff;' : 'background:transparent; color:#64748b;' }}">
                    <svg style="width:16px; height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4v16"></path>
                    </svg>
                    Chart
                </button>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div style="display:flex; gap:16px; flex-wrap:wrap;">
        {{-- Box 1: Totals --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:20px 24px; flex:1.2; min-width:200px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <p style="font-size:0.7rem; color:#9ca3af; margin:0;">{{ $summary['date_from'] ?? '' }} - {{ $summary['date_to'] ?? '' }}</p>
            <div style="display:flex; align-items:baseline; gap:16px; margin:4px 0 0;">
                <p style="font-size:2rem; font-weight:700; color:#dc2626; margin:0;">{{ number_format($summary['total_terminated'] ?? 0) }} <span style="font-size:0.85rem; font-weight:600; color:#6b7280;">company</span></p>
                <p style="font-size:1.5rem; font-weight:700; color:#f59e0b; margin:0;">{{ number_format($summary['total_headcount'] ?? 0) }} <span style="font-size:0.85rem; font-weight:600; color:#6b7280;">headcount</span></p>
            </div>
        </div>
        {{-- Box 2: Category Breakdown --}}
        <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px 24px; flex:0.8; min-width:200px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <p style="font-size:0.8rem; color:#6b7280; margin:0; font-weight:600;">{{ number_format($summary['total_terminated'] ?? 0) }} Company</p>
            <div style="display:flex; align-items:center; margin-top:8px; justify-content:space-around;">
                <div style="text-align:center; flex:1;">
                    <span style="font-size:2rem; font-weight:700; color:#10b981;">{{ $summary['categories']['end_user'] ?? 0 }}</span>
                    <p style="font-size:0.75rem; color:#475569; margin:2px 0 0;">End User</p>
                </div>
                <div style="text-align:center; flex:1;">
                    <span style="font-size:2rem; font-weight:700; color:#2563eb;">{{ $summary['categories']['dealer'] ?? 0 }}</span>
                    <p style="font-size:0.75rem; color:#475569; margin:2px 0 0;">Dealer</p>
                </div>
                <div style="text-align:center; flex:1;">
                    <span style="font-size:2rem; font-weight:700; color:#7c3aed;">{{ $summary['categories']['distributor'] ?? 0 }}</span>
                    <p style="font-size:0.75rem; color:#475569; margin:2px 0 0;">Distributor</p>
                </div>
            </div>
        </div>
        {{-- Box 3: Module Cards --}}
        @php
            $moduleColors = ['TA' => '#3b82f6', 'TL' => '#8b5cf6', 'TC' => '#f59e0b', 'TP' => '#10b981'];
            $moduleLabels = ['TA' => 'Attendance', 'TL' => 'Leave', 'TC' => 'Claim', 'TP' => 'Payroll'];
        @endphp
        @foreach(['TA', 'TL', 'TC', 'TP'] as $mod)
            <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px 20px; min-width:140px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <p style="font-size:0.75rem; color:#6b7280; margin:0; font-weight:600;">{{ $moduleLabels[$mod] }}</p>
                <p style="font-size:0.85rem; color:#9ca3af; margin:2px 0 0;">{{ number_format($summary['modules'][$mod]['headcount'] ?? 0) }} headcount</p>
                <div style="display:flex; align-items:baseline; gap:6px; margin-top:4px;">
                    <span style="font-size:2rem; font-weight:700; color:{{ $moduleColors[$mod] }};">{{ $summary['modules'][$mod]['companies'] ?? 0 }}</span>
                    <span style="font-size:0.85rem; font-weight:600; color:#6b7280;">company</span>
                </div>
            </div>
        @endforeach
    </div>

    @if($viewMode === 'chart')
    {{-- Chart View --}}
    <style>
        .ta-svg-chart { width:100%; height:350px; background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.1); }
        .ta-grid-line { stroke:#e2e8f0; stroke-width:1; }
        .ta-axis-line { stroke:#cbd5e0; stroke-width:2; }
        .ta-axis-text { font-size:11px; fill:#718096; font-family:system-ui, -apple-system, sans-serif; }
        .ta-data-line { fill:none; stroke-width:2.5; stroke-linecap:round; stroke-linejoin:round; transition:opacity 0.3s; }
        .ta-data-point { r:4; stroke:white; stroke-width:2; transition:opacity 0.3s; cursor:pointer; }
        .ta-data-point:hover { r:6; stroke-width:3; }
        .ta-data-line.hidden, .ta-data-point.hidden { opacity:0.1; }
        .ta-legend-container { display:flex; flex-wrap:wrap; gap:16px; padding:12px 16px; justify-content:center; }
        .ta-legend-item { display:flex; align-items:center; gap:6px; cursor:pointer; font-size:0.8rem; color:#475569; user-select:none; }
        .ta-legend-item.dimmed { opacity:0.3; }
        .ta-legend-color { width:12px; height:12px; border-radius:3px; }
        .ta-chart-tooltip { position:fixed; background:#1a56db; color:#fff; padding:8px 12px; border-radius:6px; font-size:0.75rem; pointer-events:none; z-index:999; white-space:nowrap; }
        .ta-chart-tooltip::after { content:''; position:absolute; top:100%; left:50%; transform:translateX(-50%); border:5px solid transparent; border-top-color:#1a56db; }
    </style>

    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:24px; box-shadow:0 1px 3px rgba(0,0,0,0.05);"
        x-data="{ showTooltip(e, text) { let t = document.getElementById('ta-tooltip'); if(!t){t=document.createElement('div');t.id='ta-tooltip';t.className='ta-chart-tooltip';document.body.appendChild(t);} t.innerHTML=text; t.style.display='block'; t.style.left=(e.clientX-t.offsetWidth/2)+'px'; t.style.top=(e.clientY-45)+'px'; }, hideTooltip() { let t=document.getElementById('ta-tooltip'); if(t) t.style.display='none'; } }">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; padding:12px 16px; background:#f8fafc; border-radius:8px;">
            <div style="font-size:1.05rem; font-weight:600; color:#2d3748;">Company Termination Trend</div>
            <div style="font-size:0.875rem; color:#718096;">{{ $summary['date_from'] ?? '' }} - {{ $summary['date_to'] ?? '' }}</div>
        </div>

        @if(!empty($chartData))
            @php
                $datasets = [
                    ['key' => 'companies', 'label' => 'Companies Terminated', 'color' => '#1a56db', 'dashed' => false],
                ];

                // Filter out hidden datasets
                $hiddenChartDatasets = $this->hiddenChartDatasets ?? [];

                $maxValue = 0;
                foreach ($datasets as $dsIdx => $ds) {
                    if (in_array($dsIdx, $hiddenChartDatasets)) continue;
                    foreach ($chartData as $point) {
                        $val = $point[$ds['key']] ?? 0;
                        if ($val > $maxValue) $maxValue = $val;
                    }
                }
                $maxValue = max($maxValue, 1);

                $rawStep = $maxValue / 5;
                $magnitude = pow(10, floor(log10(max($rawStep, 1))));
                $normalized = $rawStep / $magnitude;
                if ($normalized <= 1) $niceStep = 1 * $magnitude;
                elseif ($normalized <= 2) $niceStep = 2 * $magnitude;
                elseif ($normalized <= 5) $niceStep = 5 * $magnitude;
                else $niceStep = 10 * $magnitude;
                $niceStep = max(1, (int) $niceStep);
                $gridSteps = max(1, (int) ceil($maxValue / $niceStep));
                $maxValue = $gridSteps * $niceStep;

                $chartWidth = 1400;
                $chartHeight = 350;
                $padding = 60;
                $plotWidth = $chartWidth - (2 * $padding);
                $plotHeight = $chartHeight - (2 * $padding);
                $pointCount = count($chartData);
                $stepX = $pointCount > 1 ? $plotWidth / ($pointCount - 1) : 0;
            @endphp

            <svg class="ta-svg-chart" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="xMidYMid meet">
                {{-- Grid lines --}}
                @for($i = 0; $i <= $gridSteps; $i++)
                    @php $y = $padding + ($plotHeight * $i / $gridSteps); @endphp
                    <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $chartWidth - $padding }}" y2="{{ $y }}" class="ta-grid-line" />
                    <text x="{{ $padding - 10 }}" y="{{ $y + 4 }}" class="ta-axis-text" text-anchor="end">{{ (int)($maxValue - ($niceStep * $i)) }}</text>
                @endfor

                {{-- X-axis labels (max 16 evenly spaced) --}}
                @php
                    $maxLabels = 16;
                    $labelStep = $pointCount > $maxLabels ? max(1, (int) ceil($pointCount / $maxLabels)) : 1;
                @endphp
                @foreach($chartData as $index => $point)
                    @if($index % $labelStep === 0 || $index === $pointCount - 1)
                        @php $x = $padding + ($stepX * $index); @endphp
                        <text x="{{ $x }}" y="{{ $chartHeight - 20 }}" class="ta-axis-text" text-anchor="middle">{{ $point['label'] }}</text>
                    @endif
                @endforeach

                {{-- Axis lines --}}
                <line x1="{{ $padding }}" y1="{{ $padding }}" x2="{{ $padding }}" y2="{{ $chartHeight - $padding }}" class="ta-axis-line" />
                <line x1="{{ $padding }}" y1="{{ $chartHeight - $padding }}" x2="{{ $chartWidth - $padding }}" y2="{{ $chartHeight - $padding }}" class="ta-axis-line" />

                {{-- Data lines + points --}}
                @foreach($datasets as $dsIndex => $ds)
                    @php
                        $isHidden = in_array($dsIndex, $hiddenChartDatasets);
                        $points = [];
                        foreach ($chartData as $pIndex => $point) {
                            $val = $point[$ds['key']] ?? 0;
                            $x = $padding + ($stepX * $pIndex);
                            $y = $maxValue > 0 ? $chartHeight - $padding - (($val / $maxValue) * $plotHeight) : $chartHeight - $padding;
                            $points[] = "$x,$y";
                        }
                        $pathData = 'M ' . implode(' L ', $points);
                    @endphp

                    @if(!$isHidden)
                    <path d="{{ $pathData }}" class="ta-data-line" stroke="{{ $ds['color'] }}"
                        {!! $ds['dashed'] ? 'stroke-dasharray="8,4"' : '' !!} />

                    @foreach($chartData as $pIndex => $point)
                        @php
                            $val = $point[$ds['key']] ?? 0;
                            $x = $padding + ($stepX * $pIndex);
                            $y = $maxValue > 0 ? $chartHeight - $padding - (($val / $maxValue) * $plotHeight) : $chartHeight - $padding;
                        @endphp
                        <circle cx="{{ $x }}" cy="{{ $y }}" class="ta-data-point" fill="{{ $ds['color'] }}"
                            @mouseenter="showTooltip($event, '{{ $ds['label'] }}<br>{{ $point['label'] }}: {{ $val }}')"
                            @mouseleave="hideTooltip()" />
                    @endforeach
                    @endif
                @endforeach
            </svg>

            {{-- Legend (clickable, triggers Livewire to re-render with new scale) --}}
            <div class="ta-legend-container">
                @foreach($datasets as $dsIndex => $ds)
                    @php $isHidden = in_array($dsIndex, $hiddenChartDatasets); @endphp
                    <div class="ta-legend-item {{ $isHidden ? 'dimmed' : '' }}" wire:click="toggleChartDataset({{ $dsIndex }})" style="cursor:pointer;">
                        <div class="ta-legend-color" style="background:{{ $ds['color'] }};"></div>
                        <span>{{ $ds['label'] }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <div style="padding:48px; text-align:center; color:#9ca3af;">No data available for chart</div>
        @endif
    </div>
    @else
    {{-- 3-Tier Table --}}
    <div style="background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:visible; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
        {{-- Table Header --}}
        <div style="display:grid; grid-template-columns:28px 12px 1fr 420px 150px 130px 20px 60px 60px 50px; padding:12px 20px; background:#1a56db; color:#fff; font-size:0.8rem; font-weight:600; align-items:center;">
            <span x-data="{ open: false }" style="position:relative;">
                <div style="position:relative;">
                    <button @click="open = !open" style="border:none; background:{{ $sortBy !== 'expiry_desc' ? 'rgba(255,255,255,0.2)' : 'transparent' }}; cursor:pointer; color:#fff; padding:3px 6px; border-radius:4px; display:flex; align-items:center; gap:3px;"
                        onmouseover="this.style.background='rgba(255,255,255,0.2)'" onmouseout="this.style.background='{{ $sortBy !== 'expiry_desc' ? 'rgba(255,255,255,0.2)' : 'transparent' }}'">
                        <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                        style="position:absolute; left:0; top:100%; margin-top:4px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:50; min-width:200px; overflow:hidden;">
                        <div style="padding:8px 14px 4px; font-size:0.7rem; font-weight:700; color:#9ca3af; text-transform:uppercase; letter-spacing:0.05em;">Sort by</div>
                        @foreach([
                            'name_asc' => 'Name A → Z',
                            'name_desc' => 'Name Z → A',
                            'expiry_desc' => 'Expiry Newest First',
                            'expiry_asc' => 'Expiry Oldest First',
                        ] as $value => $label)
                            <button wire:click="$set('sortBy', '{{ $value }}')" @click="open = false"
                                style="display:flex; align-items:center; justify-content:space-between; width:100%; padding:8px 14px; border:none; background:#fff; cursor:pointer; font-size:0.8rem; color:#475569; text-align:left;"
                                onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                                {{ $label }}
                                @if($sortBy === $value)
                                    <svg style="width:14px; height:14px; color:#1a56db;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </span>
            <span></span>
            <span>Details</span>
            <span></span>
            <span style="text-align:right;">Company</span>
            <span style="text-align:right;">Headcount</span>
            <span></span>
            <span style="text-align:center;">Reason</span>
            <span style="text-align:center;">Analysis</span>
            <span></span>
        </div>

        @forelse($groupedData as $yearMonth => $monthData)
            {{-- TIER 1: Year-Month --}}
            <div wire:click="toggleYearMonth('{{ $yearMonth }}')"
                class="ta-row"
                style="display:grid; grid-template-columns:28px 12px 1fr 420px 150px 130px 20px 60px 60px 50px; padding:14px 20px; border-bottom:1px solid #e5e7eb; cursor:pointer; background:#f8fafc; align-items:center;">
                <span>
                    <svg class="ta-chevron {{ in_array($yearMonth, $expandedYearMonths) ? 'open' : '' }}" style="width:18px; height:18px; color:#475569;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </span>
                <span></span>
                <span style="font-weight:700; font-size:0.95rem; color:#1a56db;">
                    {{ $monthData['label'] }}
                </span>
                <span></span>
                <span style="text-align:right;">
                    <span style="background:#fef2f2; color:#dc2626; padding:2px 10px; border-radius:9999px; font-size:0.75rem; font-weight:600;">
                        {{ $monthData['count'] }} companies
                    </span>
                </span>
                <span style="font-size:0.8rem; font-weight:600; color:#475569; text-align:right;">
                    {{ number_format($monthData['total_headcount'] ?? 0) }} HC
                </span>
                <span></span>
                <span></span>
                <span></span>
                <span></span>
            </div>

            {{-- TIER 2: Companies --}}
            @if(in_array($yearMonth, $expandedYearMonths))
                @foreach($monthData['companies'] as $company)
                    <div wire:click="toggleCompany('{{ $company['company_id'] }}')"
                        class="ta-row"
                        style="display:grid; grid-template-columns:28px 12px 28px 1fr 420px 150px 130px 20px 60px 60px 50px; padding:12px 20px; border-bottom:1px solid #f1f5f9; cursor:pointer; background:#fff; align-items:center;">
                        <span></span>
                        <span></span>
                        <span>
                            <svg class="ta-chevron {{ in_array($company['company_id'], $expandedCompanies) ? 'open' : '' }}" style="width:16px; height:16px; color:#94a3b8;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </span>
                        <span>
                            <span style="font-weight:600; color:#334155; font-size:0.875rem;">{{ strtoupper($company['company_name']) }}</span>
                            <br>
                            <span style="font-size:0.75rem; color:#6b7280;">Expiry: {{ $company['license_expiry'] ?? '-' }} &middot; {{ $company['country'] ?? '-' }} &middot; Suspended: {{ $company['suspend_date'] ?? '-' }}</span>
                        </span>
                        <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                            @if($company['reseller_type'] && $company['reseller_name'] && strtoupper($company['reseller_type']) !== 'SUBSCRIBER')
                                @php $typeColor = strtoupper($company['reseller_type']) === 'DEALER' ? '#2563eb' : '#7c3aed'; @endphp
                                <span style="font-size:0.7rem; font-weight:600; color:{{ $typeColor }};">{{ ucfirst(strtolower($company['reseller_type'])) }}:</span>
                                <span style="font-size:0.7rem; color:#475569;">{{ strtoupper($company['reseller_name']) }}</span>
                            @endif
                        </span>
                        <span style="text-align:right;">
                        </span>
                        <span style="text-align:right;">
                            <span style="font-size:0.85rem; font-weight:700; color:#334155;">{{ $company['total_headcount'] ?? 0 }} HC</span>
                            <br>
                            <span style="font-size:0.75rem; color:#94a3b8;">{{ $company['module_count'] ?? 0 }} {{ ($company['module_count'] ?? 0) === 1 ? 'invoice' : 'invoices' }}</span>
                        </span>
                        <span></span>
                        {{-- Reason Icon --}}
                        <span style="text-align:center; cursor:pointer;" wire:click.stop="openReasonModal('{{ $company['company_id'] }}')">
                            @if($company['termination_reason'] ?? null)
                                <span class="ta-icon green" title="{{ $company['termination_reason'] }}">&#10003;</span>
                            @else
                                <span class="ta-icon red">&#10005;</span>
                            @endif
                        </span>
                        {{-- Exclude Icon --}}
                        <span style="text-align:center;">
                            @if($company['is_excluded'] ?? false)
                                <span class="ta-icon red" title="{{ $company['exclude_reason'] ?? 'Excluded' }}">&#10005;</span>
                            @else
                                <span class="ta-icon green">&#10003;</span>
                            @endif
                        </span>
                        {{-- Actions --}}
                        <span style="display:flex; gap:4px; justify-content:flex-end;" wire:click.stop>
                            <div x-data="{ open: false }" style="position:relative;">
                                <button @click="open = !open" style="padding:6px 10px; border:none; border-radius:6px; background:transparent; cursor:pointer; color:#1a56db; white-space:nowrap;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                    <svg style="width:20px; height:20px;" fill="currentColor" viewBox="0 0 24 24">
                                        <circle cx="12" cy="5" r="2"></circle>
                                        <circle cx="12" cy="12" r="2"></circle>
                                        <circle cx="12" cy="19" r="2"></circle>
                                    </svg>
                                </button>
                                <div x-show="open" @click.away="open = false" x-transition
                                    style="position:absolute; right:0; top:100%; margin-top:4px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:50; min-width:220px; overflow:hidden;">
                                    @if(!($company['is_excluded'] ?? false))
                                        <button @click="open = false; $wire.excludeCompany('{{ $company['company_id'] }}')"
                                            style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 14px; border:none; background:#fff; cursor:pointer; font-size:0.75rem; color:#dc2626; text-align:left;"
                                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
                                            <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                            </svg>
                                            Exclude from Analysis
                                        </button>
                                    @else
                                        <button @click="open = false; $wire.includeCompany('{{ $company['company_id'] }}')"
                                            style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 14px; border:none; background:#fff; cursor:pointer; font-size:0.75rem; color:#10b981; text-align:left;"
                                            onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='#fff'">
                                            <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Include in Analysis
                                        </button>
                                    @endif
                                    <button @click="open = false; $wire.openReasonModal('{{ $company['company_id'] }}')"
                                        style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 14px; border:none; background:#fff; cursor:pointer; font-size:0.75rem; color:#475569; text-align:left; border-top:1px solid #f1f5f9;"
                                        onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                                        <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        {{ ($company['termination_reason'] ?? null) ? 'Edit' : 'Add' }} Termination Reason
                                    </button>
                                    @if($company['termination_reason'] ?? null)
                                        <button @click="open = false; $wire.openViewReasonModal('{{ $company['company_id'] }}')"
                                            style="display:flex; align-items:center; gap:8px; width:100%; padding:10px 14px; border:none; background:#fff; cursor:pointer; font-size:0.75rem; color:#475569; text-align:left; border-top:1px solid #f1f5f9;"
                                            onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
                                            <svg style="width:14px; height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View Reason
                                        </button>
                                    @endif
                                </div>

                            </div>
                        </span>
                    </div>

                    {{-- TIER 3: Modules/Licenses (grouped by invoice) --}}
                    @if(in_array($company['company_id'], $expandedCompanies))
                        @php
                            $licenses = $this->getCompanyLicenses($company['company_id']);
                            $groupedByInvoice = collect($licenses)->groupBy('invoice_no');
                        @endphp
                        @if(count($licenses) > 0)
                            @foreach($groupedByInvoice as $invoiceNo => $invoiceLicenses)
                                <div style="padding:8px 20px 8px 120px; border-bottom:1px solid #f8fafc; background:#fafbfc; display:flex; align-items:flex-start; gap:12px;">
                                    <svg style="width:14px; height:14px; color:#cbd5e1; flex-shrink:0; margin-top:3px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                    </svg>
                                    @if(($invoiceLicenses->first()['invoice_url'] ?? null))
                                        <a href="{{ $invoiceLicenses->first()['invoice_url'] }}" target="_blank" style="font-size:0.85rem; color:#2563eb; font-weight:600; min-width:120px; text-decoration:none; flex-shrink:0;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ $invoiceNo }}</a>
                                    @else
                                        <span style="font-size:0.85rem; color:#64748b; font-weight:600; min-width:120px; flex-shrink:0;">{{ $invoiceNo }}</span>
                                    @endif
                                    <div style="display:grid; grid-template-columns: 38ch auto 1fr; column-gap:12px; row-gap:2px; align-items:center;">
                                        @foreach($invoiceLicenses as $license)
                                            <span style="font-size:0.85rem; color:#334155;">{{ $license['name'] }}</span>
                                            <span style="font-size:0.85rem; color:#94a3b8;">|</span>
                                            <span style="font-size:0.85rem; color:#6b7280;">{{ $license['start_date'] }} - {{ $license['expiry_date'] }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div style="padding:12px 20px 12px 140px; border-bottom:1px solid #f8fafc; background:#fafbfc;">
                                <span style="font-size:0.8rem; color:#94a3b8; font-style:italic;">No license records found</span>
                            </div>
                        @endif
                    @endif
                @endforeach
            @endif
        @empty
            <div style="padding:48px; text-align:center;">
                <svg style="width:48px; height:48px; color:#d1d5db; margin:0 auto 12px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p style="color:#6b7280; font-size:0.875rem;">No terminated companies found for the selected period</p>
            </div>
        @endforelse
    </div>
    @endif

    {{-- Page-level Edit Reason Modal --}}
    @if($showReasonModal)
    <div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.08); z-index:9999;"
        wire:click.self="closeModals">
        <div style="position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; border-radius:12px; padding:24px; width:480px; max-width:90vw; box-shadow:0 20px 60px rgba(0,0,0,0.15);" wire:click.stop>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="font-size:1rem; font-weight:700; color:#1a56db; margin:0;">Termination Reason</h3>
                <button wire:click="closeModals" style="border:none; background:transparent; cursor:pointer; color:#94a3b8; font-size:1.5rem; line-height:1;">&times;</button>
            </div>
            <p style="font-size:0.8rem; color:#6b7280; margin:0 0 12px;">{{ strtoupper($modalCompanyName) }}</p>
            <textarea wire:model.defer="modalReasonText" rows="4"
                style="width:100%; padding:10px 14px; border:1px solid #d1d5db; border-radius:8px; font-size:0.85rem; outline:none; resize:vertical; text-transform:uppercase; box-sizing:border-box;"
                placeholder="Enter termination reason..."></textarea>
            <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:16px;">
                <button wire:click="closeModals"
                    style="padding:8px 16px; border:1px solid #d1d5db; border-radius:8px; background:#fff; cursor:pointer; font-size:0.8rem; color:#475569;">
                    Cancel
                </button>
                <button wire:click="submitTerminationReason"
                    style="padding:8px 16px; border:none; border-radius:8px; background:#1a56db; color:#fff; cursor:pointer; font-size:0.8rem; font-weight:600;">
                    Save
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Page-level View Reason Modal --}}
    @if($showViewReasonModal)
    <div style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.08); z-index:9999;"
        wire:click.self="closeModals">
        <div style="position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); background:#fff; border-radius:12px; padding:24px; width:480px; max-width:90vw; box-shadow:0 20px 60px rgba(0,0,0,0.15);" wire:click.stop>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="font-size:1rem; font-weight:700; color:#1a56db; margin:0;">Termination Reason</h3>
                <button wire:click="closeModals" style="border:none; background:transparent; cursor:pointer; color:#94a3b8; font-size:1.5rem; line-height:1;">&times;</button>
            </div>
            <p style="font-size:0.8rem; color:#6b7280; margin:0 0 12px;">{{ strtoupper($modalCompanyName) }}</p>
            <div style="padding:12px 16px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; font-size:0.85rem; color:#334155; text-transform:uppercase; white-space:pre-line;">
                {{ $modalReasonText ?? '-' }}
            </div>
            <div style="display:flex; justify-content:flex-end; margin-top:16px;">
                <button wire:click="closeModals"
                    style="padding:8px 16px; border:1px solid #d1d5db; border-radius:8px; background:#fff; cursor:pointer; font-size:0.8rem; color:#475569;">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif
</x-filament-panels::page>
