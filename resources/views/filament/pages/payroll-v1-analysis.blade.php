<x-filament-panels::page>
    <style>
        .pv1-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; }
        @media (max-width: 1024px) { .pv1-grid { grid-template-columns: 1fr; } }
        .pv1-card { background: #fff; border: 1px solid #E5E7EB; border-radius: 0.75rem; padding: 1.25rem; box-shadow: 0 1px 2px rgba(0,0,0,0.04); }
        .pv1-card h3 { font-size: 0.875rem; font-weight: 600; color: #111827; margin: 0 0 0.75rem; }
        .pv1-canvas-wrap { position: relative; width: 100%; max-width: 280px; aspect-ratio: 1; margin: 0 auto; }
        .pv1-canvas-wrap canvas { width: 100% !important; height: 100% !important; }
        .pv1-hint { font-size: 0.6875rem; color: #9CA3AF; text-align: center; margin-top: 0.5rem; }

        .pv1-slideover-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); z-index: 9998; }
        .pv1-slideover { position: fixed; top: 0; right: 0; bottom: 0; width: 640px; max-width: 95vw; background: #fff; box-shadow: -4px 0 20px rgba(0,0,0,0.15); z-index: 9999; display: flex; flex-direction: column; }
        .pv1-slideover-header { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.25rem; border-bottom: 1px solid #E5E7EB; }
        .pv1-slideover-title { font-size: 1rem; font-weight: 700; color: #111827; }
        .pv1-slideover-close { background: transparent; border: 0; cursor: pointer; color: #6B7280; font-size: 1.25rem; line-height: 1; padding: 0.25rem 0.5rem; border-radius: 4px; }
        .pv1-slideover-close:hover { background: #F3F4F6; color: #111827; }
        .pv1-slideover-header-actions { display: flex; align-items: center; gap: 0.25rem; }
        .pv1-slideover-action { background: transparent; border: 1px solid #E5E7EB; cursor: pointer; color: #6B7280; padding: 0.25rem 0.5rem; border-radius: 4px; line-height: 1; transition: all 0.15s; letter-spacing: -0.1em; }
        .pv1-slideover-action:hover { background: #EEF2FF; color: #4F46E5; border-color: #C7D2FE; }
        .pv1-slideover-body { flex: 1; overflow-y: auto; padding: 1rem 1.25rem; }
        .pv1-slideover-row { display: grid; grid-template-columns: 110px 1fr 90px 90px; gap: 0.5rem; padding: 0.625rem 0; border-bottom: 1px solid #F3F4F6; font-size: 0.8125rem; align-items: center; content-visibility: auto; contain-intrinsic-size: 0 36px; }
        .pv1-slideover-row .code { font-weight: 600; color: #111827; }
        .pv1-slideover-row .name { color: #4B5563; }
        .pv1-slideover-row .ratio { font-variant-numeric: tabular-nums; color: #374151; text-align: right; font-size: 0.75rem; white-space: nowrap; }
        .pv1-slideover-row .ratio .num { font-weight: 600; }
        .pv1-slideover-row .ratio .sep { color: #9CA3AF; margin: 0 0.125rem; }
        .pv1-slideover-row .ratio .den { color: #6B7280; }
        .pv1-slideover-row-header { display: grid; grid-template-columns: 110px 1fr 90px 90px; gap: 0.5rem; padding: 0.375rem 0; border-bottom: 1px solid #E5E7EB; font-size: 0.6875rem; color: #6B7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; }
        .pv1-slideover-row-header .ratio { text-align: right; }
        .pv1-slideover-empty { padding: 2rem; text-align: center; color: #9CA3AF; font-size: 0.875rem; }
        .pv1-slideover-count { font-size: 0.6875rem; color: #6B7280; padding: 0.5rem 1.25rem; background: #F9FAFB; border-bottom: 1px solid #E5E7EB; }
        .pv1-slideover-search { display: flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-bottom: 1px solid #E5E7EB; background: #fff; }
        .pv1-search-wrap { position: relative; flex: 1; }
        .pv1-search-input { width: 100%; padding: 0.5rem 2rem 0.5rem 2rem; border: 1px solid #D1D5DB; border-radius: 6px; font-size: 0.8125rem; outline: none; transition: border-color 0.15s, box-shadow 0.15s; }
        .pv1-search-input:focus { border-color: #4F46E5; box-shadow: 0 0 0 3px rgba(79,70,229,0.15); }
        .pv1-search-icon { position: absolute; left: 0.625rem; top: 50%; transform: translateY(-50%); color: #9CA3AF; font-size: 0.875rem; pointer-events: none; }
        .pv1-search-clear { position: absolute; right: 0.5rem; top: 50%; transform: translateY(-50%); background: transparent; border: 0; cursor: pointer; color: #9CA3AF; font-size: 1rem; padding: 0; line-height: 1; }
        .pv1-search-clear:hover { color: #4B5563; }
        .pv1-toggle-group { display: inline-flex; border: 1px solid #D1D5DB; border-radius: 6px; overflow: hidden; flex-shrink: 0; }
        .pv1-toggle-group button { background: #fff; border: 0; cursor: pointer; color: #6B7280; padding: 0.45rem 0.625rem; line-height: 1; transition: background 0.15s, color 0.15s; font-size: 0.6875rem; letter-spacing: -0.05em; }
        .pv1-toggle-group button + button { border-left: 1px solid #D1D5DB; }
        .pv1-toggle-group button:hover { background: #EEF2FF; color: #4F46E5; }
        .pv1-search-empty { padding: 2rem; text-align: center; color: #9CA3AF; font-size: 0.8125rem; }
        .pv1-group { margin-bottom: 1rem; contain: layout style; }
        .pv1-group-header { display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0.75rem; background: #EEF2FF; color: #4F46E5; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.25rem; cursor: pointer; user-select: none; transition: background 0.15s; }
        .pv1-group-header:hover { background: #E0E7FF; }
        .pv1-group-header-left { display: flex; align-items: center; gap: 0.5rem; }
        .pv1-group-chevron { display: inline-flex; transition: transform 0.2s ease; font-size: 0.625rem; }
        .pv1-group-chevron.is-collapsed { transform: rotate(-90deg); }
        .pv1-group-count { font-size: 0.6875rem; color: #6B7280; font-weight: 600; }
        .pv1-group-body { overflow: hidden; }
        .pv1-group-body[x-cloak] { display: none; }
    </style>

    <div class="pv1-grid">
        <div class="pv1-card">
            <h3>Database Status</h3>
            <div class="pv1-canvas-wrap" wire:ignore>
                <canvas
                    x-data="{ chart: null }"
                    x-init="
                        (async () => {
                            if (!window.Chart) {
                                await new Promise(r => {
                                    const s = document.createElement('script');
                                    s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                                    s.onload = r; document.head.appendChild(s);
                                });
                            }
                            const centerTextPlugin = {
                                id: 'pv1CenterText',
                                afterDatasetsDraw(chart) {
                                    const { ctx, chartArea } = chart;
                                    if (!chartArea) return;
                                    const active = chart.data.datasets[0].data[0] || 0;
                                    const inactive = chart.data.datasets[0].data[1] || 0;
                                    const cx = (chartArea.left + chartArea.right) / 2;
                                    const cy = (chartArea.top + chartArea.bottom) / 2;
                                    const activeEls = chart.getActiveElements();
                                    const hoverIdx = activeEls.length ? activeEls[0].index : -1;
                                    const baseSize = 24;
                                    const hoverSize = 34;
                                    const colorActive = hoverIdx === 0 ? '#0F9D6E' : '#5DBFA9';
                                    const colorInactive = hoverIdx === 1 ? '#DC2626' : '#FF7C7C';
                                    const sizeActive = hoverIdx === 0 ? hoverSize : baseSize;
                                    const sizeInactive = hoverIdx === 1 ? hoverSize : baseSize;
                                    ctx.save();
                                    ctx.textBaseline = 'middle';
                                    const activeStr = active.toLocaleString();
                                    const sepStr = ' / ';
                                    const inactiveStr = inactive.toLocaleString();
                                    ctx.font = '700 ' + sizeActive + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    const wActive = ctx.measureText(activeStr).width;
                                    ctx.font = '700 ' + baseSize + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    const wSep = ctx.measureText(sepStr).width;
                                    ctx.font = '700 ' + sizeInactive + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    const wInactive = ctx.measureText(inactiveStr).width;
                                    const total = wActive + wSep + wInactive;
                                    const xActive = cx - total / 2;
                                    const xSep = xActive + wActive;
                                    const xInactive = xSep + wSep;
                                    ctx.textAlign = 'left';
                                    ctx.font = '700 ' + sizeActive + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    ctx.fillStyle = colorActive;
                                    ctx.fillText(activeStr, xActive, cy - 6);
                                    ctx.font = '700 ' + baseSize + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    ctx.fillStyle = '#9CA3AF';
                                    ctx.fillText(sepStr, xSep, cy - 6);
                                    ctx.font = '700 ' + sizeInactive + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    ctx.fillStyle = colorInactive;
                                    ctx.fillText(inactiveStr, xInactive, cy - 6);
                                    ctx.textAlign = 'center';
                                    ctx.font = '500 11px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    ctx.fillStyle = '#5DBFA9';
                                    ctx.fillText('Active', xActive + wActive / 2, cy + 22);
                                    ctx.fillStyle = '#FF7C7C';
                                    ctx.fillText('InActive', xInactive + wInactive / 2, cy + 22);
                                    ctx.restore();
                                    chart.$pv1Regions = [
                                        { idx: 0, x: xActive, y: cy - 26, w: wActive, h: 56 },
                                        { idx: 1, x: xInactive, y: cy - 26, w: wInactive, h: 56 },
                                    ];
                                },
                            };
                            const numberHoverPlugin = {
                                id: 'pv1NumberHover',
                                afterEvent(chart, args) {
                                    if (!chart.$pv1Regions) return;
                                    const e = args.event;
                                    if (e.type !== 'mousemove' && e.type !== 'mouseout' && e.type !== 'click') return;
                                    const x = e.x, y = e.y;
                                    let hit = -1;
                                    if (e.type !== 'mouseout') {
                                        for (const r of chart.$pv1Regions) {
                                            if (x >= r.x && x <= r.x + r.w && y >= r.y && y <= r.y + r.h) { hit = r.idx; break; }
                                        }
                                    }
                                    if (e.type === 'click' && hit >= 0) {
                                        const seg = ['active', 'inactive'][hit];
                                        if (seg) $wire.call('openSegment', 'license', seg);
                                        return;
                                    }
                                    if (hit >= 0) {
                                        const current = chart.getActiveElements();
                                        if (current.length === 0 || current[0].index !== hit) {
                                            chart.setActiveElements([{ datasetIndex: 0, index: hit }]);
                                            chart.tooltip.setActiveElements([{ datasetIndex: 0, index: hit }], { x, y });
                                            args.changed = true;
                                        }
                                        chart.canvas.style.cursor = 'pointer';
                                    } else if (chart._pv1WasHover) {
                                        chart.setActiveElements([]);
                                        chart.tooltip.setActiveElements([], { x: 0, y: 0 });
                                        args.changed = true;
                                    }
                                    chart._pv1WasHover = (hit >= 0);
                                },
                            };
                            const build = (s) => {
                                if (chart) chart.destroy();
                                chart = new Chart($el, {
                                    type: 'doughnut',
                                    data: {
                                        labels: ['Active', 'InActive'],
                                        datasets: [{
                                            data: [s.active || 0, s.inactive || 0],
                                            backgroundColor: ['#82CEC2', '#FF8A8A'],
                                            hoverBackgroundColor: ['#5DBFA9', '#ff7c7c'],
                                            borderWidth: 3,
                                            borderColor: '#ffffff',
                                            hoverBorderColor: '#ffffff',
                                            hoverOffset: 14,
                                        }],
                                    },
                                    options: {
                                        maintainAspectRatio: false,
                                        cutout: '65%',
                                        animation: { animateRotate: true, animateScale: true, duration: 700, easing: 'easeOutQuart' },
                                        plugins: {
                                            legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, padding: 14, usePointStyle: true, pointStyle: 'circle', font: { size: 12, weight: '500' }, color: '#374151' } },
                                            tooltip: {
                                                backgroundColor: 'rgba(17,24,39,0.95)', titleColor: '#F9FAFB', bodyColor: '#E5E7EB', borderColor: 'rgba(255,255,255,0.08)', borderWidth: 1, padding: 10, cornerRadius: 8, displayColors: true, boxPadding: 4,
                                                callbacks: { label: (ctx) => {
                                                    const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                                                    const pct = total ? ((ctx.parsed/total)*100).toFixed(1) : 0;
                                                    return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                                                } },
                                            },
                                        },
                                        onClick: (evt, els) => {
                                            if (!els.length) return;
                                            const seg = ['active', 'inactive'][els[0].index];
                                            if (seg) $wire.call('openSegment', 'license', seg);
                                        },
                                    },
                                    plugins: [centerTextPlugin, numberHoverPlugin],
                                });
                            };
                            build(@js($licenseStats));
                            window.addEventListener('pv1-stats-updated', (e) => {
                                const s = (e.detail && e.detail.stats) || (e.detail && e.detail[0] && e.detail[0].stats);
                                if (s && s.license) build(s.license);
                            });
                        })();
                    "
                ></canvas>
            </div>
        </div>

        <div class="pv1-card">
            <h3>Database Type</h3>
            <div class="pv1-canvas-wrap" wire:ignore>
                <canvas
                    x-data="{ chart: null }"
                    x-init="
                        (async () => {
                            if (!window.Chart) {
                                await new Promise(r => {
                                    const s = document.createElement('script');
                                    s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                                    s.onload = r; document.head.appendChild(s);
                                });
                            }
                            const centerTextPlugin = {
                                id: 'pv1CenterText',
                                afterDatasetsDraw(chart) {
                                    const { ctx, chartArea } = chart;
                                    if (!chartArea) return;
                                    const internal = chart.data.datasets[0].data[0] || 0;
                                    const paid = chart.data.datasets[0].data[1] || 0;
                                    const cx = (chartArea.left + chartArea.right) / 2;
                                    const cy = (chartArea.top + chartArea.bottom) / 2;
                                    const activeEls = chart.getActiveElements();
                                    const hoverIdx = activeEls.length ? activeEls[0].index : -1;
                                    const baseSize = 24;
                                    const hoverSize = 34;
                                    const sizeInternal = hoverIdx === 0 ? hoverSize : baseSize;
                                    const sizePaid = hoverIdx === 1 ? hoverSize : baseSize;
                                    const colorInternal = hoverIdx === 0 ? '#D97706' : '#FFB066';
                                    const colorPaid = hoverIdx === 1 ? '#6D28D9' : '#8b5cf6';
                                    ctx.save();
                                    ctx.textBaseline = 'middle';
                                    const internalStr = internal.toLocaleString();
                                    const sepStr = ' / ';
                                    const paidStr = paid.toLocaleString();
                                    ctx.font = '700 ' + sizeInternal + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    const wInternal = ctx.measureText(internalStr).width;
                                    ctx.font = '700 ' + baseSize + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    const wSep = ctx.measureText(sepStr).width;
                                    ctx.font = '700 ' + sizePaid + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    const wPaid = ctx.measureText(paidStr).width;
                                    const total = wInternal + wSep + wPaid;
                                    const xInternal = cx - total / 2;
                                    const xSep = xInternal + wInternal;
                                    const xPaid = xSep + wSep;
                                    ctx.textAlign = 'left';
                                    ctx.font = '700 ' + sizeInternal + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    ctx.fillStyle = colorInternal;
                                    ctx.fillText(internalStr, xInternal, cy - 6);
                                    ctx.font = '700 ' + baseSize + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    ctx.fillStyle = '#9CA3AF';
                                    ctx.fillText(sepStr, xSep, cy - 6);
                                    ctx.font = '700 ' + sizePaid + 'px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    ctx.fillStyle = colorPaid;
                                    ctx.fillText(paidStr, xPaid, cy - 6);
                                    ctx.textAlign = 'center';
                                    ctx.font = '500 11px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    ctx.fillStyle = '#FFB066';
                                    ctx.fillText('Internal', xInternal + wInternal / 2, cy + 22);
                                    ctx.fillStyle = '#8b5cf6';
                                    ctx.fillText('Paid', xPaid + wPaid / 2, cy + 22);
                                    ctx.restore();
                                    chart.$pv1Regions = [
                                        { idx: 0, x: xInternal, y: cy - 26, w: wInternal, h: 56 },
                                        { idx: 1, x: xPaid, y: cy - 26, w: wPaid, h: 56 },
                                    ];
                                },
                            };
                            const numberHoverPlugin = {
                                id: 'pv1NumberHover',
                                afterEvent(chart, args) {
                                    if (!chart.$pv1Regions) return;
                                    const e = args.event;
                                    if (e.type !== 'mousemove' && e.type !== 'mouseout' && e.type !== 'click') return;
                                    const x = e.x, y = e.y;
                                    let hit = -1;
                                    if (e.type !== 'mouseout') {
                                        for (const r of chart.$pv1Regions) {
                                            if (x >= r.x && x <= r.x + r.w && y >= r.y && y <= r.y + r.h) { hit = r.idx; break; }
                                        }
                                    }
                                    if (e.type === 'click' && hit >= 0) {
                                        const seg = ['internal', 'paid'][hit];
                                        if (seg) $wire.call('openSegment', 'account_type', seg);
                                        return;
                                    }
                                    if (hit >= 0) {
                                        const current = chart.getActiveElements();
                                        if (current.length === 0 || current[0].index !== hit) {
                                            chart.setActiveElements([{ datasetIndex: 0, index: hit }]);
                                            chart.tooltip.setActiveElements([{ datasetIndex: 0, index: hit }], { x, y });
                                            args.changed = true;
                                        }
                                        chart.canvas.style.cursor = 'pointer';
                                    } else if (chart._pv1WasHover) {
                                        chart.setActiveElements([]);
                                        chart.tooltip.setActiveElements([], { x: 0, y: 0 });
                                        args.changed = true;
                                    }
                                    chart._pv1WasHover = (hit >= 0);
                                },
                            };
                            const build = (s) => {
                                if (chart) chart.destroy();
                                chart = new Chart($el, {
                                    type: 'doughnut',
                                    data: {
                                        labels: ['Internal Use', 'Paid Customer'],
                                        datasets: [{
                                            data: [s.internal || 0, s.paid || 0],
                                            backgroundColor: ['#FFD59C', '#B3B0F7'],
                                            hoverBackgroundColor: ['#FFB066', '#8b5cf6'],
                                            borderWidth: 3,
                                            borderColor: '#ffffff',
                                            hoverBorderColor: '#ffffff',
                                            hoverOffset: 14,
                                        }],
                                    },
                                    options: {
                                        maintainAspectRatio: false,
                                        cutout: '65%',
                                        animation: { animateRotate: true, animateScale: true, duration: 700, easing: 'easeOutQuart' },
                                        plugins: {
                                            legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, padding: 14, usePointStyle: true, pointStyle: 'circle', font: { size: 12, weight: '500' }, color: '#374151' } },
                                            tooltip: {
                                                backgroundColor: 'rgba(17,24,39,0.95)', titleColor: '#F9FAFB', bodyColor: '#E5E7EB', borderColor: 'rgba(255,255,255,0.08)', borderWidth: 1, padding: 10, cornerRadius: 8, displayColors: true, boxPadding: 4,
                                                callbacks: { label: (ctx) => {
                                                    const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                                                    const pct = total ? ((ctx.parsed/total)*100).toFixed(1) : 0;
                                                    return ctx.label + ': ' + ctx.parsed + ' (' + pct + '%)';
                                                } },
                                            },
                                        },
                                        onClick: (evt, els) => {
                                            if (!els.length) return;
                                            const seg = ['internal', 'paid'][els[0].index];
                                            if (seg) $wire.call('openSegment', 'account_type', seg);
                                        },
                                    },
                                    plugins: [centerTextPlugin, numberHoverPlugin],
                                });
                            };
                            build(@js($accountTypeStats));
                            window.addEventListener('pv1-stats-updated', (e) => {
                                const s = (e.detail && e.detail.stats) || (e.detail && e.detail[0] && e.detail[0].stats);
                                if (s && s.account) build(s.account);
                            });
                        })();
                    "
                ></canvas>
            </div>
        </div>

        <div class="pv1-card">
            <h3>Last Processed Activity</h3>
            <div class="pv1-canvas-wrap" wire:ignore>
                <canvas
                    x-data="{ chart: null }"
                    x-init="
                        (async () => {
                            if (!window.Chart) {
                                await new Promise(r => {
                                    const s = document.createElement('script');
                                    s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                                    s.onload = r; document.head.appendChild(s);
                                });
                            }
                            const valueLabelPlugin = {
                                id: 'pv1ValueLabel',
                                afterDatasetsDraw(chart) {
                                    const { ctx } = chart;
                                    const meta = chart.getDatasetMeta(0);
                                    const data = chart.data.datasets[0].data;
                                    ctx.save();
                                    ctx.font = '600 12px ui-sans-serif, system-ui, -apple-system, Roboto, sans-serif';
                                    ctx.fillStyle = '#374151';
                                    ctx.textBaseline = 'middle';
                                    ctx.textAlign = 'left';
                                    meta.data.forEach((bar, i) => {
                                        const v = data[i] || 0;
                                        if (!v) return;
                                        ctx.fillText(v.toLocaleString(), bar.x + 6, bar.y);
                                    });
                                    ctx.restore();
                                },
                            };
                            const build = (s) => {
                                if (chart) chart.destroy();
                                const labels = ['Last 30 Days', 'Last 60 Days', 'Others'];
                                const data = [s.last_30_days || 0, s.last_60_days || 0, s.others || 0];
                                const bg = ['#B9D6F8', '#D7C7F4', '#D0D3D9'];
                                const hoverBg = ['#4cc5fd', '#805ba7', '#A0A0A0'];
                                const total = data.reduce((a,b)=>a+b,0);
                                chart = new Chart($el, {
                                    type: 'bar',
                                    data: {
                                        labels,
                                        datasets: [{
                                            data,
                                            backgroundColor: bg,
                                            hoverBackgroundColor: hoverBg,
                                            borderWidth: 0,
                                            borderRadius: 6,
                                            barPercentage: 0.7,
                                            categoryPercentage: 0.8,
                                        }],
                                    },
                                    options: {
                                        indexAxis: 'y',
                                        maintainAspectRatio: false,
                                        layout: { padding: { right: 48 } },
                                        animation: { duration: 700, easing: 'easeOutQuart' },
                                        scales: {
                                            x: { display: false, beginAtZero: true, grid: { display: false } },
                                            y: { grid: { display: false }, ticks: { color: '#374151', font: { size: 12, weight: '500' } } },
                                        },
                                        plugins: {
                                            legend: { display: false },
                                            tooltip: {
                                                backgroundColor: 'rgba(17,24,39,0.95)', titleColor: '#F9FAFB', bodyColor: '#E5E7EB', borderColor: 'rgba(255,255,255,0.08)', borderWidth: 1, padding: 10, cornerRadius: 8, displayColors: true, boxPadding: 4,
                                                callbacks: { label: (ctx) => {
                                                    const pct = total ? ((ctx.parsed.x/total)*100).toFixed(1) : 0;
                                                    return ctx.parsed.x + ' (' + pct + '%)';
                                                } },
                                            },
                                        },
                                        onClick: (evt, els) => {
                                            if (!els.length) return;
                                            const seg = ['last_30_days', 'last_60_days', 'others'][els[0].index];
                                            if (seg) $wire.call('openSegment', 'activity', seg);
                                        },
                                        onHover: (evt, els) => {
                                            evt.native.target.style.cursor = els.length ? 'pointer' : '';
                                        },
                                    },
                                    plugins: [valueLabelPlugin],
                                });
                            };
                            build(@js($activityStats));
                            window.addEventListener('pv1-stats-updated', (e) => {
                                const s = (e.detail && e.detail.stats) || (e.detail && e.detail[0] && e.detail[0].stats);
                                if (s && s.activity) build(s.activity);
                            });
                        })();
                    "
                ></canvas>
            </div>
        </div>
    </div>

    <div style="margin-top: 1.5rem;">
        {{ $this->table }}
    </div>

    @if ($slideoverOpen)
        <div class="pv1-slideover-overlay" onclick="window.pv1HideSlideover()" wire:click="closeSlideover"></div>
        <aside class="pv1-slideover" id="pv1-slideover-root">
            <div class="pv1-slideover-header">
                <div class="pv1-slideover-title">{{ $slideoverTitle }}</div>
                <button type="button" class="pv1-slideover-close" onclick="window.pv1HideSlideover()" wire:click="closeSlideover">&times;</button>
            </div>
            @php
                $totalAccounts = collect($slideoverRows)->sum(fn ($g) => count($g));
            @endphp
            <div class="pv1-slideover-count">{{ $totalAccounts }} account(s) across {{ count($slideoverRows) }} database(s)</div>
            <div class="pv1-slideover-search">
                <div class="pv1-search-wrap">
                    <span class="pv1-search-icon">🔍</span>
                    <input
                        type="text"
                        id="pv1-search-input"
                        class="pv1-search-input"
                        placeholder="Search code or name..."
                        oninput="window.pv1FilterSlideover(this.value)"
                    />
                    <button
                        type="button"
                        id="pv1-search-clear"
                        class="pv1-search-clear"
                        style="display: none;"
                        onclick="document.getElementById('pv1-search-input').value=''; window.pv1FilterSlideover('');"
                        title="Clear"
                    >&times;</button>
                </div>
                <div class="pv1-toggle-group">
                    <button type="button" id="pv1-toggle-all-btn" onclick="window.pv1ToggleAllSwitch()" title="Collapse all">▶▶</button>
                </div>
            </div>
            <div class="pv1-slideover-body">
                @forelse ($slideoverRows as $database => $rows)
                    @php
                        $groupHay = collect($rows)
                            ->map(fn ($r) => strtolower(($r['account_code'] ?? '').' '.($r['account_name'] ?? '')))
                            ->implode(' ||| ');
                    @endphp
                    <div
                        class="pv1-group"
                        data-pv1-group-hay="{{ $groupHay }}"
                        data-pv1-open="1"
                    >
                        <div class="pv1-group-header" onclick="window.pv1ToggleGroup(this.parentElement)" role="button">
                            <div class="pv1-group-header-left">
                                <span class="pv1-group-chevron">▼</span>
                                <span>{{ $database }}</span>
                            </div>
                            <span class="pv1-group-count">{{ count($rows) }}</span>
                        </div>
                        <div class="pv1-group-body">
                            <div class="pv1-slideover-row-header">
                                <div>Code</div>
                                <div>Name</div>
                                <div class="ratio">Company</div>
                                <div class="ratio">Employee</div>
                            </div>
                            @foreach ($rows as $row)
                                @php
                                    $rowHay = strtolower(($row['account_code'] ?? '').' '.($row['account_name'] ?? ''));
                                @endphp
                                <div class="pv1-slideover-row" data-pv1-hay="{{ $rowHay }}">
                                    <div class="code">{{ $row['account_code'] ?: '—' }}</div>
                                    <div class="name">{{ $row['account_name'] ?: '—' }}</div>
                                    <div class="ratio">
                                        <span class="num">{{ $row['total_company'] ?? 0 }}</span><span class="sep">/</span><span class="den">{{ $row['company_license_count'] ?? 0 }}</span>
                                    </div>
                                    <div class="ratio">
                                        <span class="num">{{ $row['total_employee_active'] ?? 0 }}</span><span class="sep">/</span><span class="den">{{ $row['employee_license_count'] ?? 0 }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="pv1-slideover-empty">No accounts found.</div>
                @endforelse
            </div>
        </aside>
    @endif

    <script>
        (function () {
            window.pv1FilterSlideover = function (q) {
                q = (q || '').toLowerCase().trim();
                const root = document.getElementById('pv1-slideover-root');
                if (!root) return;
                const clearBtn = document.getElementById('pv1-search-clear');
                if (clearBtn) clearBtn.style.display = q === '' ? 'none' : '';

                const groups = root.querySelectorAll('.pv1-group');
                groups.forEach((group) => {
                    const groupHay = group.dataset.pv1GroupHay || '';
                    const groupMatch = q === '' || groupHay.indexOf(q) !== -1;
                    group.style.display = groupMatch ? '' : 'none';
                    if (!groupMatch) return;

                    if (q === '') {
                        const rows = group.querySelectorAll('.pv1-slideover-row');
                        for (let i = 0; i < rows.length; i++) rows[i].style.display = '';
                    } else {
                        const rows = group.querySelectorAll('.pv1-slideover-row');
                        for (let i = 0; i < rows.length; i++) {
                            const hay = rows[i].dataset.pv1Hay || '';
                            rows[i].style.display = hay.indexOf(q) !== -1 ? '' : 'none';
                        }
                    }
                });
            };

            window.pv1ToggleGroup = function (groupEl) {
                if (!groupEl) return;
                const isOpen = groupEl.dataset.pv1Open === '1';
                const next = !isOpen;
                groupEl.dataset.pv1Open = next ? '1' : '0';
                const body = groupEl.querySelector('.pv1-group-body');
                if (body) body.style.display = next ? '' : 'none';
                const chev = groupEl.querySelector('.pv1-group-chevron');
                if (chev) chev.classList.toggle('is-collapsed', !next);
            };

            window.pv1ToggleAll = function (open) {
                const root = document.getElementById('pv1-slideover-root');
                if (!root) return;
                const groups = root.querySelectorAll('.pv1-group');
                groups.forEach((group) => {
                    group.dataset.pv1Open = open ? '1' : '0';
                    const body = group.querySelector('.pv1-group-body');
                    if (body) body.style.display = open ? '' : 'none';
                    const chev = group.querySelector('.pv1-group-chevron');
                    if (chev) chev.classList.toggle('is-collapsed', !open);
                });
            };

            window.pv1ToggleAllSwitch = function () {
                const root = document.getElementById('pv1-slideover-root');
                const btn = document.getElementById('pv1-toggle-all-btn');
                if (!root || !btn) return;
                const groups = root.querySelectorAll('.pv1-group');
                let anyOpen = false;
                groups.forEach((g) => { if (g.dataset.pv1Open === '1') anyOpen = true; });
                const next = !anyOpen;
                window.pv1ToggleAll(next);
                btn.textContent = next ? '▶▶' : '▼▼';
                btn.title = next ? 'Collapse all' : 'Expand all';
            };

            window.pv1HideSlideover = function () {
                const root = document.getElementById('pv1-slideover-root');
                const overlay = document.querySelector('.pv1-slideover-overlay');
                if (root) root.style.display = 'none';
                if (overlay) overlay.style.display = 'none';
            };
        })();
    </script>
</x-filament-panels::page>
