<x-filament-panels::page>
    <style>
        /* === TOP BAR === */
        .qc-top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .scope-toggle {
            display: inline-flex;
            background: #F3F4F6;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 4px;
            gap: 2px;
        }
        .scope-toggle button {
            padding: 8px 18px;
            border: 0;
            background: transparent;
            color: #6B7280;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .scope-toggle button:hover { color: #111827; }
        .scope-toggle button.active {
            background: #6366F1;
            color: #fff;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.25);
        }

        /* === CONNECTED MODULE + TASKS CARD === */
        .qc-module-panel {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        /* Folder-tab style: tabs sit ON the panel */
        .qc-module-tabs {
            display: flex;
            align-items: stretch;
            justify-content: space-between;
            background: #F3F4F6;
            border-bottom: 1px solid #E5E7EB;
            padding: 0;
            flex-wrap: wrap;
            gap: 0;
        }
        .qc-module-tabs-left {
            display: flex;
            align-items: stretch;
            flex-wrap: wrap;
            flex: 1;
        }
        .qc-module-tabs button.qc-tab {
            padding: 12px 18px;
            border: 0;
            border-right: 1px solid #E5E7EB;
            background: transparent;
            color: #6B7280;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
        }
        .qc-module-tabs button.qc-tab:hover { color: #111827; background: #E5E7EB; }
        .qc-module-tabs button.qc-tab.active {
            background: #fff;
            color: #10B981;
        }
        .qc-module-tabs button.qc-tab.active::after {
            content: '';
            position: absolute;
            left: 0; right: 0;
            bottom: -1px;
            height: 3px;
            background: #10B981;
            border-radius: 3px 3px 0 0;
        }
        .qc-module-tabs .mt-count {
            font-size: 10px;
            padding: 2px 7px;
            border-radius: 9999px;
            background: rgba(0,0,0,0.08);
            font-weight: 700;
            line-height: 1.4;
        }
        .qc-module-tabs button.qc-tab.active .mt-count {
            background: #D1FAE5;
            color: #065F46;
        }

        .qc-module-tabs-right {
            display: flex;
            align-items: center;
            padding: 8px 14px;
        }

        .qc-module-body {
            padding: 18px;
            background: #FAFBFC;
        }

        .qc-module-body > :last-child { margin-bottom: 0; }

        .qc-create-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: #6366F1;
            color: #fff;
            border: 0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .qc-create-btn:hover { background: #4F46E5; color: #fff; }

        /* === MODULE HEADER CARD === */
        .qc-active-module-head {
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 10px;
            padding: 12px 18px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            border-left: 4px solid #10B981;
        }
        .qc-active-module-title {
            font-size: 16px;
            font-weight: 700;
            color: #065F46;
            margin: 0;
        }
        .qc-active-module-meta {
            font-size: 12px;
            color: #6B7280;
        }
        .qc-active-module-percent {
            font-size: 22px;
            font-weight: 700;
            color: #065F46;
        }

        /* === TASK BLOCK === */
        .qc-task-block {
            background: white;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 14px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .qc-task-block:hover {
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.08);
            border-color: #C7D2FE;
        }
        .qc-task-block::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #D1D5DB;
            transition: background 0.2s;
        }
        .qc-task-block.status-pending::before { background: linear-gradient(180deg, #D1D5DB 0%, #9CA3AF 100%); }
        .qc-task-block.status-in-progress::before { background: linear-gradient(180deg, #FBBF24 0%, #F59E0B 100%); }
        .qc-task-block.status-complete::before { background: linear-gradient(180deg, #34D399 0%, #10B981 100%); }

        .qc-task-block-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #F3F4F6;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .qc-task-title-row {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
        }
        .qc-task-title {
            font-size: 15px;
            font-weight: 700;
            color: #111827;
            word-break: break-word;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .qc-task-title::before {
            content: '📋';
            font-size: 14px;
        }
        .qc-task-progress-text {
            font-size: 12px;
            color: #6B7280;
            white-space: nowrap;
        }
        .qc-task-progress-text .done { color: #047857; font-weight: 700; }
        .qc-task-labels {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        .qc-tier-chip {
            display: inline-block;
            background: #EEF2FF;
            color: #4338CA;
            border-radius: 6px;
            padding: 3px 10px;
            font-size: 11px;
            font-weight: 600;
            border: 1px solid #C7D2FE;
        }
        .qc-tier-chip.empty {
            background: #F3F4F6;
            color: #9CA3AF;
            border-color: #E5E7EB;
        }
        .qc-task-progress-mini {
            font-size: 12px;
            color: #6B7280;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .qc-task-progress-mini .done { color: #047857; font-weight: 700; }
        .qc-progress-bar {
            flex: 1;
            max-width: 200px;
            height: 6px;
            background: #F3F4F6;
            border-radius: 9999px;
            overflow: hidden;
            position: relative;
        }
        .qc-progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10B981 0%, #059669 100%);
            border-radius: 9999px;
            transition: width 0.3s ease;
        }
        .qc-progress-bar-fill.zero { background: transparent; }

        .qc-task-delete {
            padding: 4px 10px;
            background: #fff;
            border: 1px solid #FCA5A5;
            color: #B91C1C;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            flex-shrink: 0;
        }
        .qc-task-delete:hover { background: #FEF2F2; }

        .qc-task-action-btn {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .qc-task-action-btn.view {
            background: #EEF2FF;
            border: 1px solid #C7D2FE;
            color: #4338CA;
        }
        .qc-task-action-btn.view:hover { background: #E0E7FF; }
        .qc-task-action-btn.edit {
            background: #FEF3C7;
            border: 1px solid #FDE68A;
            color: #92400E;
        }
        .qc-task-action-btn.edit:hover { background: #FDE68A; color: #92400E; }

        .qc-task-percent-badge {
            font-size: 16px;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 9999px;
            min-width: 60px;
            text-align: center;
        }
        .qc-task-percent-badge.complete {
            background: #D1FAE5;
            color: #065F46;
        }
        .qc-task-percent-badge.in-progress {
            background: #FEF3C7;
            color: #92400E;
        }
        .qc-task-percent-badge.pending {
            background: #F3F4F6;
            color: #6B7280;
        }

        /* === TIMELINE === */
        .progress-timeline {
            position: relative;
            overflow-x: auto;
            overflow-y: visible;
            padding: 14px 4px 4px 4px;
        }
        .timeline-container {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            min-width: max-content;
            gap: 4px;
        }
        .timeline-task {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
            min-width: 0;
        }
        .timeline-task:hover { z-index: 999; }

        .timeline-circle {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            border: 2.5px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            cursor: pointer;
            transition: all 0.25s ease;
            position: relative;
        }
        .timeline-circle:hover {
            transform: scale(1.12);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
        }
        .timeline-circle.completed {
            background: linear-gradient(135deg, #34D399 0%, #10B981 50%, #059669 100%);
            border-color: #10B981;
            box-shadow: 0 2px 6px rgba(16, 185, 129, 0.35);
        }
        .timeline-circle.pending {
            background-color: #fff;
            border-color: #D1D5DB;
            border-style: dashed;
        }
        .timeline-circle.pending:hover {
            border-color: #6366F1;
            background-color: #F5F7FF;
        }
        .timeline-icon-completed {
            width: 26px;
            height: 26px;
            color: white;
            pointer-events: none;
            filter: drop-shadow(0 1px 2px rgba(0,0,0,0.1));
        }
        .timeline-dot {
            width: 14px;
            height: 14px;
            background-color: #D1D5DB;
            border-radius: 50%;
            pointer-events: none;
            transition: all 0.25s;
        }
        .timeline-circle.pending:hover .timeline-dot {
            background-color: #6366F1;
            transform: scale(1.3);
        }
        .timeline-attach-badge {
            position: absolute;
            top: -6px;
            right: -8px;
            min-width: 22px;
            height: 22px;
            padding: 0 5px;
            background: #F59E0B;
            border: 2px solid #fff;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            line-height: 1;
            color: #fff;
            font-weight: 700;
            box-sizing: border-box;
        }

        .timeline-info {
            margin-top: 10px;
            text-align: center;
            max-width: 140px;
            min-width: 100px;
        }
        .timeline-number {
            font-size: 11px;
            font-weight: 700;
            color: #4338CA;
            background: #EEF2FF;
            padding: 1px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 6px;
        }
        .timeline-status-pill {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            padding: 2px 8px;
            border-radius: 9999px;
            display: inline-block;
        }
        .timeline-status-pill.completed { background: #D1FAE5; color: #065F46; }
        .timeline-status-pill.pending { background: #FEF3C7; color: #92400E; }

        .timeline-action-btn {
            margin-top: 8px;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            border: 0;
        }
        .timeline-action-btn.do-complete {
            background: #10B981;
            color: #fff;
        }
        .timeline-action-btn.do-complete:hover { background: #059669; }
        .timeline-action-btn.do-reopen {
            background: #fff;
            color: #374151;
            border: 1px solid #D1D5DB;
        }
        .timeline-action-btn.do-reopen:hover { background: #F9FAFB; }

        .timeline-line {
            flex: 1;
            border-top: 2px solid;
            margin-top: 22px;
            min-width: 24px;
            max-width: 30px;
            flex-shrink: 0;
        }
        .timeline-line.completed { border-color: #10b981; }
        .timeline-line.pending { border-color: #d1d5db; }

        .qc-empty-state {
            background: #F9FAFB;
            border: 1px dashed #D1D5DB;
            border-radius: 10px;
            padding: 32px 16px;
            text-align: center;
            color: #6B7280;
            font-size: 13px;
        }
        .qc-empty-state strong { color: #374151; display: block; margin-bottom: 4px; }

        /* === TOOLTIP === */
        #qc-tooltip-container {
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            pointer-events: none;
            z-index: 9999999;
        }
        #qc-tooltip-container .task-tooltip {
            position: fixed;
            background-color: #1f2937;
            color: white;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12px;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            min-width: 220px;
            max-width: 320px;
            pointer-events: none;
        }
        #qc-tooltip-container .task-tooltip.show {
            opacity: 1 !important;
            visibility: visible !important;
        }
        #qc-tooltip-container .task-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border: 8px solid transparent;
            border-top-color: #1f2937;
        }
        .tooltip-title {
            font-weight: 700;
            font-size: 12px;
            color: #93c5fd;
            margin-bottom: 6px;
        }
        .tooltip-text {
            font-size: 11px;
            color: #e5e7eb;
            line-height: 1.5;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .tooltip-divider {
            height: 1px;
            background: #374151;
            margin: 8px 0;
        }
        .tooltip-meta { font-size: 11px; color: #d1d5db; }
        .tooltip-attach {
            font-size: 11px;
            color: #FBBF24;
            font-weight: 600;
            margin-top: 4px;
        }
        .timeline-circle > .task-tooltip { display: none !important; }

        /* === MODAL === */
        .qc-modal-backdrop {
            position: fixed; inset: 0;
            background: rgba(17, 24, 39, 0.5);
            z-index: 1000;
            display: flex; align-items: flex-start; justify-content: center;
            padding: 60px 20px;
            overflow-y: auto;
        }
        .qc-modal {
            background: #fff;
            border-radius: 14px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .qc-modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid #E5E7EB;
            display: flex; align-items: center; justify-content: space-between;
        }
        .qc-modal-header h3 { font-size: 15px; font-weight: 700; color: #111827; margin: 0; }
        .qc-modal-close {
            background: transparent; border: 0; color: #6B7280; font-size: 22px; cursor: pointer; line-height: 1;
        }
        .qc-modal-body { padding: 18px 20px; }
        .qc-modal-footer {
            padding: 12px 20px;
            border-top: 1px solid #E5E7EB;
            display: flex; justify-content: flex-end; gap: 10px;
            background: #F9FAFB;
        }

        .qc-field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        .qc-file-upload {
            padding: 9px 12px;
            border: 1px dashed #A5B4FC;
            background: #F5F7FF;
            border-radius: 8px;
            font-size: 13px;
            color: #4338CA;
            width: 100%;
            box-sizing: border-box;
        }
        .qc-field-hint {
            font-size: 11px;
            color: #6B7280;
            margin-top: 6px;
        }
        .qc-err { color: #DC2626; font-size: 11px; margin-top: 4px; }

        .qc-btn-primary {
            background: #10B981; color: #fff; border: 0;
            padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px;
            cursor: pointer;
        }
        .qc-btn-primary:hover { background: #059669; }
        .qc-btn-primary:disabled { background: #86EFAC; cursor: not-allowed; }
        .qc-btn-secondary {
            background: #fff; color: #374151; border: 1px solid #D1D5DB;
            padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 13px;
            cursor: pointer;
        }
        .qc-btn-secondary:hover { background: #F9FAFB; }

        .qc-prompt-preview {
            background: #F3F4F6;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 12px;
            color: #374151;
            white-space: pre-wrap;
            word-break: break-word;
            margin-bottom: 14px;
        }
    </style>

    <script>
        function initQcTooltips() {
            const container = document.getElementById('qc-tooltip-container');
            if (!container) return;

            document.querySelectorAll('.timeline-circle').forEach(circle => {
                if (circle._qcBound) return;
                circle._qcBound = true;

                circle.addEventListener('mouseenter', function (e) {
                    // Read fresh each hover so status/attachment changes show immediately
                    const src = this.querySelector('.task-tooltip');
                    if (!src) return;

                    const rect = this.getBoundingClientRect();
                    const tip = document.createElement('div');
                    tip.className = 'task-tooltip show';
                    tip.innerHTML = src.innerHTML;
                    tip.style.bottom = (window.innerHeight - rect.top + 10) + 'px';
                    tip.style.left = (rect.left + rect.width / 2) + 'px';
                    tip.style.transform = 'translateX(-50%)';
                    container.appendChild(tip);
                    this._qcTip = tip;
                });

                circle.addEventListener('mouseleave', function () {
                    if (this._qcTip) {
                        this._qcTip.remove();
                        this._qcTip = null;
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', initQcTooltips);
        document.addEventListener('livewire:initialized', () => {
            Livewire.hook('morph.updated', () => setTimeout(initQcTooltips, 50));
        });
    </script>

    {{-- Row 1: Version toggle --}}
    <div class="qc-top-bar">
        <div class="scope-toggle">
            <button type="button" wire:click="setActiveTab('v1')" class="{{ $activeTab === 'v1' ? 'active' : '' }}">HR Version 1</button>
            <button type="button" wire:click="setActiveTab('v2')" class="{{ $activeTab === 'v2' ? 'active' : '' }}">HR Version 2</button>
        </div>
    </div>

    {{-- One connected panel: module tabs on top, tasks directly below --}}
    <div class="qc-module-panel">
        <div class="qc-module-tabs">
            <div class="qc-module-tabs-left">
                @foreach($this->availableModules as $mod)
                    @php $stats = $this->moduleStats[$mod] ?? ['tasks' => 0]; @endphp
                    <button type="button"
                            wire:click="setActiveModule('{{ $mod }}')"
                            class="qc-tab {{ $activeModule === $mod ? 'active' : '' }}">
                        {{ $mod }}
                        <span class="mt-count">{{ $stats['tasks'] }}</span>
                    </button>
                @endforeach
            </div>
            <div class="qc-module-tabs-right">
                <a href="{{ \App\Filament\Pages\QCPromptTaskCreate::getUrl(['version' => $activeTab, 'module' => $activeModule]) }}" class="qc-create-btn">
                    + Create Task
                </a>
            </div>
        </div>

        <div class="qc-module-body">
            @php $tasks = $this->currentModuleTasks; @endphp

            @if($tasks->isEmpty())
                <div class="qc-empty-state">
                    <strong>No tasks yet for {{ $activeModule }}.</strong>
                    Click "+ Create Task" to add your first task.
                </div>
            @else
                @foreach($tasks as $task)
            @php
                $taskTotal = $task->prompts->count();
                $taskDone = $task->prompts->where('status', 'completed')->count();
                $taskPercent = $taskTotal > 0 ? round(($taskDone / $taskTotal) * 100) : 0;
                $taskStatusClass = $taskPercent == 100 ? 'status-complete' : ($taskPercent > 0 ? 'status-in-progress' : 'status-pending');
            @endphp
            <div class="qc-task-block {{ $taskStatusClass }}" wire:key="task-{{ $task->id }}">
                <div class="qc-task-block-header">
                    <div style="flex:1; min-width:0;">
                        <div class="qc-task-title-row">
                            <span class="qc-task-title">{{ $task->title }}</span>
                            <div class="qc-progress-bar">
                                <div class="qc-progress-bar-fill {{ $taskPercent == 0 ? 'zero' : '' }}" style="width: {{ $taskPercent }}%;"></div>
                            </div>
                            <span class="qc-task-progress-text"><span class="done">{{ $taskDone }}</span>/{{ $taskTotal }} completed</span>
                        </div>
                        <div class="qc-task-labels">
                            <span class="qc-tier-chip {{ $task->label_tier1 ? '' : 'empty' }}">T1: {{ $task->label_tier1 ?: '—' }}</span>
                            <span class="qc-tier-chip {{ $task->label_tier2 ? '' : 'empty' }}">T2: {{ $task->label_tier2 ?: '—' }}</span>
                            <span class="qc-tier-chip {{ $task->label_tier3 ? '' : 'empty' }}">T3: {{ $task->label_tier3 ?: '—' }}</span>
                        </div>
                    </div>
                    <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                        <div class="qc-task-percent-badge {{ $taskPercent == 100 ? 'complete' : ($taskPercent > 0 ? 'in-progress' : 'pending') }}">
                            {{ $taskPercent }}%
                        </div>
                        <button type="button"
                                class="qc-task-action-btn view"
                                wire:click="openTaskModal({{ $task->id }})"
                                title="View all prompts and attachments">
                            👁 View
                        </button>
                        <a class="qc-task-action-btn edit"
                           href="{{ \App\Filament\Pages\QCPromptTaskEdit::getUrl(['record' => $task->id]) }}"
                           title="Edit task or add more prompts">
                            ✎ Edit
                        </a>
                    </div>
                </div>

                <div class="progress-timeline">
                    <div class="timeline-container">
                        @foreach($task->prompts as $i => $prompt)
                            @php
                                $isCompleted = $prompt->status === 'completed';
                                $statusClass = $isCompleted ? 'completed' : 'pending';
                            @endphp

                            <div class="timeline-task">
                                <div class="timeline-circle {{ $statusClass }}"
                                     wire:click="openPromptModal({{ $prompt->id }})"
                                     title="Click to view prompt details">
                                    <div class="task-tooltip">
                                        <div class="tooltip-title">Prompt #{{ $i + 1 }}</div>
                                        <div class="tooltip-text">{{ $prompt->prompt }}</div>
                                        <div class="tooltip-divider"></div>
                                        <div class="tooltip-meta">
                                            Status: <strong>{{ ucfirst($prompt->status) }}</strong>
                                        </div>
                                        @if($isCompleted && $prompt->completed_at)
                                            <div class="tooltip-meta">
                                                Completed: {{ $prompt->completed_at->format('d M Y, H:i') }}
                                            </div>
                                        @endif
                                        <div class="tooltip-divider"></div>
                                        <div class="tooltip-meta" style="color:#93c5fd; font-weight:600;">Click to view →</div>
                                    </div>

                                    @if($isCompleted)
                                        <svg class="timeline-icon-completed" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    @else
                                        <div class="timeline-dot"></div>
                                    @endif

                                    @if($prompt->attachments->isNotEmpty())
                                        <span class="timeline-attach-badge" title="{{ $prompt->attachments->count() }} attachment(s)">📎{{ $prompt->attachments->count() > 1 ? $prompt->attachments->count() : '' }}</span>
                                    @endif
                                </div>

                                <div class="timeline-info">
                                    <div class="timeline-status-pill {{ $statusClass }}">{{ $prompt->status }}</div>
                                    @if(!$isCompleted)
                                        <button type="button"
                                                class="timeline-action-btn do-complete"
                                                wire:click="openCompleteModal({{ $prompt->id }})">
                                            Mark Complete
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if(!$loop->last)
                                @php
                                    $nextCompleted = $task->prompts[$i + 1]->status === 'completed';
                                    $lineCompleted = $isCompleted && $nextCompleted;
                                @endphp
                                <div class="timeline-line {{ $lineCompleted ? 'completed' : 'pending' }}"></div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            @endif
        </div>
    </div>

    <div id="qc-tooltip-container"></div>

    {{-- TASK VIEW MODAL (all prompts + attachments) --}}
    @if($showTaskModal)
        @php
            $viewingTask = $viewingTaskId ? \App\Models\QcTask::with('prompts.attachments')->find($viewingTaskId) : null;
        @endphp
        @if($viewingTask)
            <div class="qc-modal-backdrop" wire:click.self="closeTaskModal">
                <div class="qc-modal" style="max-width:720px;">
                    <div class="qc-modal-header">
                        <h3>{{ $viewingTask->title }}</h3>
                        <button type="button" class="qc-modal-close" wire:click="closeTaskModal">&times;</button>
                    </div>

                    <div class="qc-modal-body" style="max-height:70vh; overflow-y:auto;">
                        {{-- Task meta --}}
                        <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px;">
                            <span class="qc-tier-chip">HR {{ strtoupper($viewingTask->hr_version) }}</span>
                            <span class="qc-tier-chip">Module: {{ $viewingTask->module }}</span>
                            @if($viewingTask->label_tier1)
                                <span class="qc-tier-chip">T1: {{ $viewingTask->label_tier1 }}</span>
                            @endif
                            @if($viewingTask->label_tier2)
                                <span class="qc-tier-chip">T2: {{ $viewingTask->label_tier2 }}</span>
                            @endif
                            @if($viewingTask->label_tier3)
                                <span class="qc-tier-chip">T3: {{ $viewingTask->label_tier3 }}</span>
                            @endif
                        </div>

                        {{-- Prompts list --}}
                        @foreach($viewingTask->prompts as $i => $p)
                            <div style="border: 1px solid #E5E7EB; border-radius: 10px; padding: 14px; margin-bottom: 10px; background: {{ $p->status === 'completed' ? '#F0FDF4' : '#FAFAFA' }};">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                                    <span style="background: {{ $p->status === 'completed' ? '#10B981' : '#4338CA' }}; color:#fff; padding:3px 10px; border-radius:9999px; font-size:11px; font-weight:700;">
                                        Prompt #{{ $i + 1 }}
                                    </span>
                                    <span class="timeline-status-pill {{ $p->status }}">{{ $p->status }}</span>
                                </div>
                                <div style="font-size:13px; color:#374151; white-space:pre-wrap; word-break:break-word; margin-bottom:10px;">{{ $p->prompt }}</div>

                                @if($p->attachments->isNotEmpty())
                                    <div style="display:flex; flex-direction:column; gap:6px;">
                                        @foreach($p->attachments as $ai => $att)
                                            <a href="{{ asset('storage/' . $att->file_path) }}"
                                               target="_blank"
                                               style="display:inline-flex; align-items:center; gap:6px; background:#FEF3C7; color:#92400E; padding:6px 12px; border-radius:6px; font-size:12px; font-weight:600; text-decoration:none;">
                                                📎 Attachment {{ $ai + 1 }}
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <div style="font-size:11px; color:#9CA3AF; font-style:italic;">No attachment</div>
                                @endif

                                @if($p->status === 'completed' && $p->completed_at)
                                    <div style="font-size:11px; color:#6B7280; margin-top:8px;">
                                        Completed {{ $p->completed_at->format('d M Y, H:i') }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="qc-modal-footer">
                        <button type="button" class="qc-btn-secondary" wire:click="closeTaskModal">Close</button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- PROMPT VIEW MODAL --}}
    @if($showPromptModal)
        @php
            $viewingPrompt = $viewingPromptId ? \App\Models\QcTaskPrompt::with(['task', 'attachments'])->find($viewingPromptId) : null;
        @endphp
        @if($viewingPrompt)
            <div class="qc-modal-backdrop" wire:click.self="closePromptModal">
                <div class="qc-modal">
                    <div class="qc-modal-header">
                        <h3>
                            Prompt Details
                            <span class="timeline-status-pill {{ $viewingPrompt->status }}" style="margin-left:8px;">
                                {{ $viewingPrompt->status }}
                            </span>
                        </h3>
                        <button type="button" class="qc-modal-close" wire:click="closePromptModal">&times;</button>
                    </div>

                    <div class="qc-modal-body">
                        <div style="font-size:11px; color:#6B7280; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.04em; font-weight:700;">Task</div>
                        <div style="font-size:14px; font-weight:700; color:#111827; margin-bottom:14px;">
                            {{ $viewingPrompt->task->title }}
                        </div>

                        <div style="font-size:11px; color:#6B7280; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.04em; font-weight:700;">Prompt</div>
                        <div class="qc-prompt-preview">{{ $viewingPrompt->prompt }}</div>

                        <div style="font-size:11px; color:#6B7280; margin-bottom:4px; text-transform:uppercase; letter-spacing:0.04em; font-weight:700;">Attachment</div>
                        @if($viewingPrompt->attachments->isNotEmpty())
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                @foreach($viewingPrompt->attachments as $ai => $att)
                                    <a href="{{ asset('storage/' . $att->file_path) }}"
                                       target="_blank"
                                       style="display:inline-flex; align-items:center; gap:6px; background:#FEF3C7; color:#92400E; padding:8px 14px; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none;">
                                        📎 Attachment {{ $ai + 1 }}
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div style="font-size:12px; color:#9CA3AF; font-style:italic; padding:10px 0;">No attachment uploaded.</div>
                        @endif

                        @if($viewingPrompt->status === 'completed' && $viewingPrompt->completed_at)
                            <div style="margin-top:14px; padding:8px 12px; background:#F0FDF4; border-radius:8px; font-size:12px; color:#065F46;">
                                ✓ Completed on {{ $viewingPrompt->completed_at->format('d M Y, H:i') }}
                            </div>
                        @endif
                    </div>

                    <div class="qc-modal-footer">
                        <button type="button" class="qc-btn-secondary" wire:click="closePromptModal">Close</button>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- COMPLETE MODAL --}}
    @if($showCompleteModal)
        @php
            $completingPrompt = $completingPromptId ? \App\Models\QcTaskPrompt::find($completingPromptId) : null;
        @endphp
        <div class="qc-modal-backdrop" wire:click.self="closeCompleteModal">
            <div class="qc-modal">
                <div class="qc-modal-header">
                    <h3>Mark Prompt as Completed</h3>
                    <button type="button" class="qc-modal-close" wire:click="closeCompleteModal">&times;</button>
                </div>

                <form wire:submit.prevent="submitComplete">
                    <div class="qc-modal-body">
                        @if($completingPrompt)
                            <div class="qc-prompt-preview">{{ $completingPrompt->prompt }}</div>
                        @endif

                        <label class="qc-field-label" for="qc-complete-file">Attachments (optional, multiple allowed)</label>
                        <input type="file"
                               id="qc-complete-file"
                               class="qc-file-upload"
                               multiple
                               wire:model="completionAttachments">

                        <div wire:loading wire:target="completionAttachments" class="qc-field-hint" style="color:#4F46E5;">
                            Uploading...
                        </div>

                        @if(!empty($completionAttachments))
                            <div style="margin-top:10px; display:flex; flex-direction:column; gap:4px;">
                                @foreach($completionAttachments as $file)
                                    @if($file)
                                        <div class="qc-field-hint" style="color:#065F46;">
                                            📎 {{ $file->getClientOriginalName() }}
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="qc-field-hint">Max 10MB per file. You can also mark complete without an attachment.</div>
                        @endif

                        @error('completionAttachments.*') <div class="qc-err">{{ $message }}</div> @enderror
                    </div>

                    <div class="qc-modal-footer">
                        <button type="button" class="qc-btn-secondary" wire:click="closeCompleteModal">Cancel</button>
                        <button type="submit"
                                class="qc-btn-primary"
                                wire:loading.attr="disabled"
                                wire:target="submitComplete,completionAttachment">
                            <span wire:loading.remove wire:target="submitComplete">Mark Complete</span>
                            <span wire:loading wire:target="submitComplete">Saving...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
