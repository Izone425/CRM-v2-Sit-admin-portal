<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/qc-ticketing/dashboard-v2.css') }}">

    @php
        $taskCounts = array_filter($this->taskStatusCounts, fn ($v) => (int) $v > 0);
        $bugCounts = array_filter($this->bugStatusCounts, fn ($v) => (int) $v > 0);
        $taskTotal = array_sum($taskCounts);
        $bugTotal = array_sum($bugCounts);
        $overdueTasks = $this->overdueTasks;

        $statusPalette = [
            'Closed' => '#6ee7b7',
            'Live' => '#fb923c',
            'New' => '#60a5fa',
            'QC - In Progress' => '#eab308',
            'Ready For Live' => '#fed7aa',
            'Ready For Testing' => '#bef264',
            'Reopen' => '#a5b4fc',
            'RND - In Progress' => '#facc15',
            'Rejected' => '#fca5a5',
            'Cancelled' => '#d1d5db',
            'Cancel' => '#d1d5db',
            'Completed' => '#6ee7b7',
            'Ready For Development' => '#fcd34d',
            'In Progress' => '#eab308',
            'Testing' => '#bef264',
        ];
        $fallback = ['#6ee7b7', '#fb923c', '#60a5fa', '#eab308', '#fed7aa', '#bef264', '#a5b4fc', '#facc15', '#fca5a5'];

        $buildColors = function (array $keys) use ($statusPalette, $fallback) {
            $colors = [];
            $i = 0;
            foreach ($keys as $k) {
                $colors[] = $statusPalette[$k] ?? $fallback[$i % count($fallback)];
                $i++;
            }
            return $colors;
        };
    @endphp

    <div class="dv2-container">
        <div class="dv2-tabs">
            <button type="button" class="dv2-tab active">Personal Workload Summary</button>
        </div>

        <div class="dv2-tab-panel">
            <div class="dv2-row">
                <div class="dv2-card">
                    <div class="dv2-card-header">
                        <h3>Task Status</h3>
                        <span class="dv2-total-pill">{{ $taskTotal }} total</span>
                    </div>
                    @if ($taskTotal === 0)
                        <div style="padding: 60px 16px; text-align: center; color: #9CA3AF; font-size: 13px;">No task found</div>
                    @else
                    <div class="dv2-pie-wrapper">
                        <canvas
                            x-data
                            x-init="
                                (async () => {
                                    if (!window.Chart) {
                                        await new Promise(r => {
                                            const s = document.createElement('script');
                                            s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                                            s.onload = r; document.head.appendChild(s);
                                        });
                                    }
                                    new Chart($el, {
                                        type: 'pie',
                                        data: {
                                            labels: @js(array_keys($taskCounts)),
                                            datasets: [{
                                                data: @js(array_values($taskCounts)),
                                                backgroundColor: @js($buildColors(array_keys($taskCounts))),
                                                borderWidth: 0,
                                            }]
                                        },
                                        options: {
                                            maintainAspectRatio: false,
                                            onClick: (evt, els, chart) => {
                                                if (!els.length) return;
                                                const label = chart.data.labels[els[0].index];
                                                Livewire.dispatch('openStatusList', { type: 'task', status: label });
                                            },
                                            plugins: {
                                                legend: {
                                                    position: 'right',
                                                    labels: {
                                                        boxWidth: 10,
                                                        boxHeight: 10,
                                                        usePointStyle: true,
                                                        pointStyle: 'circle',
                                                        padding: 8,
                                                        font: { size: 13, family: 'Inter, sans-serif' },
                                                        generateLabels: (chart) => {
                                                            const ds = chart.data.datasets[0];
                                                            return chart.data.labels.map((label, i) => ({
                                                                text: `${label} (${ds.data[i]})`,
                                                                fillStyle: ds.backgroundColor[i],
                                                                strokeStyle: ds.backgroundColor[i],
                                                                pointStyle: 'circle',
                                                                hidden: !chart.getDataVisibility(i),
                                                                index: i,
                                                            }));
                                                        },
                                                    }
                                                },
                                                tooltip: { enabled: true },
                                            }
                                        }
                                    });
                                })();
                            "
                        ></canvas>
                    </div>
                    @endif
                </div>

                @if ($showBugs)
                <div class="dv2-card">
                    <div class="dv2-card-header">
                        <h3>Bug Status</h3>
                        <span class="dv2-total-pill">{{ $bugTotal }} total</span>
                    </div>
                    @if ($bugTotal === 0)
                        <div style="padding: 60px 16px; text-align: center; color: #9CA3AF; font-size: 13px;">No bug found</div>
                    @else
                    <div class="dv2-pie-wrapper">
                        <canvas
                            x-data
                            x-init="
                                (async () => {
                                    if (!window.Chart) {
                                        await new Promise(r => {
                                            const s = document.createElement('script');
                                            s.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                                            s.onload = r; document.head.appendChild(s);
                                        });
                                    }
                                    new Chart($el, {
                                        type: 'pie',
                                        data: {
                                            labels: @js(array_keys($bugCounts)),
                                            datasets: [{
                                                data: @js(array_values($bugCounts)),
                                                backgroundColor: @js($buildColors(array_keys($bugCounts))),
                                                borderWidth: 0,
                                            }]
                                        },
                                        options: {
                                            maintainAspectRatio: false,
                                            onClick: (evt, els, chart) => {
                                                if (!els.length) return;
                                                const label = chart.data.labels[els[0].index];
                                                Livewire.dispatch('openStatusList', { type: 'bug', status: label });
                                            },
                                            plugins: {
                                                legend: {
                                                    position: 'right',
                                                    labels: {
                                                        boxWidth: 10,
                                                        boxHeight: 10,
                                                        usePointStyle: true,
                                                        pointStyle: 'circle',
                                                        padding: 8,
                                                        font: { size: 13, family: 'Inter, sans-serif' },
                                                        generateLabels: (chart) => {
                                                            const ds = chart.data.datasets[0];
                                                            return chart.data.labels.map((label, i) => ({
                                                                text: `${label} (${ds.data[i]})`,
                                                                fillStyle: ds.backgroundColor[i],
                                                                strokeStyle: ds.backgroundColor[i],
                                                                pointStyle: 'circle',
                                                                hidden: !chart.getDataVisibility(i),
                                                                index: i,
                                                            }));
                                                        },
                                                    }
                                                },
                                                tooltip: { enabled: true },
                                            }
                                        }
                                    });
                                })();
                            "
                        ></canvas>
                    </div>
                    @endif
                </div>
                @endif
            </div>

            <div class="dv2-card">
                <div class="dv2-card-header">
                    <h3>Overdue Tasks</h3>
                    <span class="dv2-total-pill">{{ $overdueTasks->count() }}</span>
                </div>
                <ul class="dv2-overdue-list" x-data="{ showAll: false }">
                    @foreach ($overdueTasks as $index => $task)
                        <li
                            class="dv2-overdue-item clickable"
                            role="button"
                            tabindex="0"
                            wire:click="$dispatch('openTaskModal', [{{ $task->id }}])"
                            @if ($index >= 10) x-show="showAll" x-cloak @endif
                        >
                            <div class="dv2-overdue-main">
                                <span class="dv2-overdue-title">
                                    <strong>{{ $task->task_id }}</strong>
                                    {{ \Illuminate\Support\Str::limit($task->title, 80) }}
                                </span>
                                <div class="dv2-overdue-meta">
                                    <span class="dv2-overdue-status">{{ $task->status }}</span>
                                    @if ($task->due_date)
                                        <span class="dv2-overdue-due-date">Due {{ $task->due_date->format('n/j/Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            @if ($task->due_date)
                                <span class="dv2-overdue-date">due {{ $task->due_date->diffInDays(now()) }} days ago</span>
                            @endif
                        </li>
                    @endforeach
                    @if ($overdueTasks->isEmpty())
                        <li class="py-8 text-sm text-center">No overdue tasks</li>
                    @endif
                </ul>
                @if ($overdueTasks->count() > 10)
                    <button type="button" class="dv2-show-more" x-data x-on:click="$el.previousElementSibling.__x.$data.showAll = !$el.previousElementSibling.__x.$data.showAll; $el.textContent = $el.previousElementSibling.__x.$data.showAll ? 'Show less' : 'Show more...'">Show more...</button>
                @endif
            </div>
        </div>
    </div>

    <livewire:task-modal />
    <livewire:bug-modal />
    <livewire:status-list-drawer />
    <livewire:create-task-drawer />
    <livewire:create-bug-drawer />
    <livewire:create-ticket-drawer />
    <livewire:create-suggestion-drawer />
    <livewire:create-creative-request-drawer />
    <livewire:create-release-drawer />
</x-filament-panels::page>


