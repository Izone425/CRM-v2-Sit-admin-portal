<x-filament-panels::page>
    <link rel="stylesheet" href="{{ asset('css/qc-ticketing/my-workspace.css') }}">

    @php
        $analytics = $this->analytics;
        $kanban = $this->kanbanColumns;
        $isBugTab = $activeTab === 'my_bugs';
        $drawerDatasets = $this->drawerDatasets;

        $priorityIcon = function ($priorityName) {
            $name = strtolower((string) $priorityName);
            if (str_contains($name, 'highest') || str_contains($name, 'critical')) {
                return ['svg' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2"><polyline points="17 11 12 6 7 11"/><polyline points="17 18 12 13 7 18"/></svg>', 'label' => 'Highest'];
            }
            if (str_contains($name, 'high')) {
                return ['svg' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#EA580C" stroke-width="2"><polyline points="6 15 12 9 18 15"/></svg>', 'label' => 'High'];
            }
            if (str_contains($name, 'medium')) {
                return ['svg' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#F59E0B" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>', 'label' => 'Medium'];
            }
            if (str_contains($name, 'low')) {
                return ['svg' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>', 'label' => 'Low'];
            }
            return ['svg' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#3B82F6" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>', 'label' => '-'];
        };
    @endphp

    <div class="myTask"
         x-data="{
            drawerOpen: false,
            drawerType: null,
            drawerSearch: '',
            datasets: @js($drawerDatasets),
            removeTicketFromDatasets(ticketId) {
                if (!ticketId) return;
                const keys = ['new_tickets', 'mandays_requests'];
                for (const k of keys) {
                    if (this.datasets[k]?.items) {
                        this.datasets[k].items = this.datasets[k].items.filter(i => i.id !== ticketId);
                    }
                }
            },
            reasonModalOpen: false,
            reasonAction: null,
            reasonTicket: null,
            reasonValue: '',
            openDrawer(type) {
                if (!this.datasets[type]) return;
                this.drawerType = type;
                this.drawerSearch = '';
                this.drawerOpen = true;
                document.body.style.overflow = 'hidden';
            },
            closeDrawer() {
                this.drawerOpen = false;
                document.body.style.overflow = '';
            },
            openReason(action, ticket) {
                this.reasonAction = action;
                this.reasonTicket = ticket;
                this.reasonValue = '';
                this.reasonModalOpen = true;
                this.$nextTick(() => { const el = document.getElementById('pdtReasonTextarea'); if (el) el.focus(); });
            },
            closeReason() {
                this.reasonModalOpen = false;
                this.reasonAction = null;
                this.reasonTicket = null;
                this.reasonValue = '';
            },
            submitReason() {
                const reason = (this.reasonValue || '').trim();
                if (reason === '' || !this.reasonTicket) return;
                const ticketId = this.reasonTicket.id;
                const code = this.reasonTicket.code;
                const isReject = this.reasonAction === 'reject';
                const method = isReject ? 'rejectTicket' : 'holdTicket';
                // Optimistic: close UI + remove from datasets + fire Filament notification instantly.
                this.removeTicketFromDatasets(ticketId);
                this.closeReason();
                this.closeDrawer();
                this.notify(isReject ? `${code} rejected` : `${code} put on hold`, isReject ? 'danger' : 'warning');
                $wire.call(method, ticketId, reason);
            },
            notify(title, status = 'success') {
                if (typeof FilamentNotification !== 'undefined') {
                    const n = new FilamentNotification().title(title);
                    if (status === 'danger') n.danger();
                    else if (status === 'warning') n.warning();
                    else n.success();
                    n.send();
                } else if (window.$wireui?.notify) {
                    window.$wireui.notify({ title, icon: status });
                }
            },
            get reasonTitle() {
                return this.reasonAction === 'reject' ? 'Reject Ticket' : 'Put Ticket On Hold';
            },
            get reasonLabel() {
                return this.reasonAction === 'reject' ? 'Rejection reason' : 'On Hold reason';
            },
            get currentDataset() { return this.drawerType ? this.datasets[this.drawerType] : null; },
            get filteredItems() {
                const ds = this.currentDataset;
                if (!ds) return [];
                const s = (this.drawerSearch || '').toLowerCase();
                if (!s) return ds.items;
                return ds.items.filter(i => ((i.code || '') + ' ' + (i.title || '')).toLowerCase().includes(s));
            }
         }"
         @ticket-status-updated.window="removeTicketFromDatasets($event.detail?.ticketId ?? $event.detail?.[0])"
         @task-created.window="removeTicketFromDatasets($event.detail?.ticketId ?? $event.detail?.[0])">
        <!-- Page Header -->
        <div class="pageHeader">
            <div class="titleSection">
                <h1 class="pageTitle">My Workspace</h1>
                <p class="dateTimeSubtext">{{ now()->format('l, d/m/Y, g:i A') }}</p>
            </div>

            <div class="analyticsCards">
                @if ($analytics['tasksDueToday'] > 0)
                    <div class="analyticsBadge" @click="openDrawer('tasks')">
                        <div class="badgeIcon" style="background-color: #3B82F6;">
                            <svg width="16" height="16" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </div>
                        <span class="badgeLabel">Task Due</span>
                        <span class="badgeCount">{{ $analytics['tasksDueToday'] }}</span>
                    </div>
                @endif
                @if ($analytics['bugsDueToday'] > 0)
                    <div class="analyticsBadge" @click="openDrawer('bugs')">
                        <div class="badgeIcon" style="background-color: #EF4444;">
                            <svg width="16" height="16" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                        </div>
                        <span class="badgeLabel">Bug Due</span>
                        <span class="badgeCount">{{ $analytics['bugsDueToday'] }}</span>
                    </div>
                @endif
                @if (($analytics['newTicketsCount'] ?? 0) > 0)
                    <div class="analyticsBadge" @click="openDrawer('new_tickets')">
                        <div class="badgeIcon" style="background-color: #8B5CF6;">
                            <svg width="16" height="16" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><path d="M4 10V8a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2a2 2 0 0 0 0-4z"/><line x1="12" y1="6" x2="12" y2="18"/></svg>
                        </div>
                        <span class="badgeLabel">New Ticket</span>
                        <span class="badgeCount">{{ $analytics['newTicketsCount'] }}</span>
                    </div>
                @endif
                @if (($analytics['mandaysRequestsCount'] ?? 0) > 0)
                    <div class="analyticsBadge" @click="openDrawer('mandays_requests')">
                        <div class="badgeIcon" style="background-color: #F59E0B;">
                            <svg width="16" height="16" fill="none" stroke="white" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        </div>
                        <span class="badgeLabel">Mandays Requests</span>
                        <span class="badgeCount">{{ $analytics['mandaysRequestsCount'] }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabsContainer">
            <div class="tabs">
                <button type="button" wire:click="setTab('my_tasks')" class="tab {{ $activeTab === 'my_tasks' ? 'activeTab' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    My Tasks
                </button>
                @if ($this->showBugsTab())
                <button type="button" wire:click="setTab('my_bugs')" class="tab {{ $activeTab === 'my_bugs' ? 'activeTab' : '' }}">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="8" y="6" width="8" height="14" rx="4"/><path d="M19 7l-3 2"/><path d="M5 7l3 2"/><path d="M19 13h-3"/><path d="M8 13H5"/><path d="M19 19l-3-2"/><path d="M5 19l3-2"/><path d="M12 6V3"/></svg>
                    My Bugs
                </button>
                @endif
            </div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Filter Bar -->
            <div class="kanbanFilters">
                <div class="filterGroup">
                    <div class="filterItem">
                        <select wire:model.live="filterTimeframe">
                            @foreach ($this->timeframeOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="filterItem">
                        <select wire:model.live="filterProduct">
                            @foreach ($this->productOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if (!$isBugTab)
                        <div class="filterItem">
                            <select wire:model.live="filterPriority">
                                @foreach ($this->priorityOptions as $opt)
                                    <option value="{{ $opt }}">{{ $opt }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    @if ($filterTimeframe !== 'All Dates' || $filterProduct !== 'All Products' || $filterPriority !== 'All Urgency')
                        <button class="clearFiltersButton" wire:click="clearFilters" title="Clear all filters">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Clear Filters
                        </button>
                    @endif
                </div>
            </div>

            <!-- Kanban Board -->
            <div
                class="kanbanBoard"
                x-data
                x-init="
                    (async () => {
                        if (!window.Sortable) {
                            await new Promise(r => {
                                const s = document.createElement('script');
                                s.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
                                s.onload = r; document.head.appendChild(s);
                            });
                        }
                        const board = $el;
                        const storageKey = 'qc_workspace_column_order_{{ $activeTab }}';

                        // Restore saved order
                        const saved = localStorage.getItem(storageKey);
                        if (saved) {
                            try {
                                const order = JSON.parse(saved);
                                order.forEach(id => {
                                    const node = board.querySelector(`[data-col-id='${id}']`);
                                    if (node) board.appendChild(node);
                                });
                            } catch (e) {}
                        }

                        new Sortable(board, {
                            animation: 150,
                            handle: '.columnHeader',
                            draggable: '.kanbanColumn',
                            onEnd: () => {
                                const order = Array.from(board.querySelectorAll('.kanbanColumn')).map(c => c.dataset.colId);
                                localStorage.setItem(storageKey, JSON.stringify(order));
                            },
                        });
                    })();
                "
            >
                @foreach ($kanban as $colId => $col)
                    @php $cfg = $col['config']; $items = $col['items']; @endphp
                    <div class="kanbanColumn" data-col-id="{{ $colId }}" x-data="{ isCollapsed: false }" :class="{ 'collapsed': isCollapsed }">
                        <div class="columnHeader" style="cursor: grab;">
                            <div class="columnTitle" style="border-left-color: {{ $cfg['color'] }}; border-top-color: {{ $cfg['color'] }};">
                                <span>{{ $cfg['title'] }}</span>
                                <span class="columnCount">{{ $items->count() }}</span>
                            </div>
                            <button class="collapseButton" type="button" @click.stop="isCollapsed = !isCollapsed">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" x-show="!isCollapsed"><polyline points="15 18 9 12 15 6"/></svg>
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" x-show="isCollapsed" x-cloak><polyline points="6 9 12 15 18 9"/></svg>
                            </button>
                        </div>
                        <div class="columnContent" x-show="!isCollapsed">
                            @forelse ($items as $item)
                                @php
                                    $daysInStatus = $item->updated_at ? max(0, $item->updated_at->diffInDays(now())) : 0;
                                    $isOverdue = !$isBugTab && $item->due_date && $item->due_date->lt(now()->startOfDay());
                                    $isDueToday = !$isBugTab && $item->due_date && $item->due_date->isToday();
                                    $pri = !$isBugTab ? $priorityIcon($item->priority?->name ?? '') : null;
                                @endphp
                                <div class="taskCard"
                                     @if (!$isBugTab) wire:click="$dispatch('openTaskModal', [{{ $item->id }}])" @endif
                                     style="cursor: pointer;">
                                    <div class="taskHeader">
                                        <span class="taskIdBadge">{{ $isBugTab ? $item->bug_id : $item->task_id }}</span>
                                        @if ($pri)
                                            <div class="taskPriorityIcon" title="{{ $pri['label'] }}">{!! $pri['svg'] !!}</div>
                                        @endif
                                    </div>
                                    <h3 class="taskTitle">{{ $item->title }}</h3>
                                    <div class="taskSubtext">
                                        {{ $item->product?->name ?? '-' }}
                                        @if ($daysInStatus > 0)
                                            <span class="statusDuration" title="In {{ $item->status }} status for {{ $daysInStatus }} day{{ $daysInStatus !== 1 ? 's' : '' }}">• {{ $daysInStatus }}d</span>
                                        @endif
                                    </div>
                                    <div class="taskFooter">
                                        @if (!$isBugTab && $item->due_date)
                                            <div class="metaItem {{ $isDueToday ? 'dueDateWarning' : '' }} {{ $isOverdue ? 'dueDateOverdue' : '' }}">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                                <span>{{ $item->due_date->format('d/m/Y') }}</span>
                                            </div>
                                        @endif
                                        <div class="metaItem">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                                            <span>{{ $item->assignee_names ?? '' }}</span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="emptyColumn">No items</div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Preloaded, Alpine-driven drawer: instant open/close, no server roundtrip -->
        <style>
            @keyframes drawerOverlayFadeIn { from { opacity: 0; } to { opacity: 1; } }
            @keyframes drawerOverlayFadeOut { from { opacity: 1; } to { opacity: 0; } }
            @keyframes drawerSlideIn { from { transform: translateX(calc(100% + 32px)); } to { transform: translateX(0); } }
            @keyframes drawerSlideOut { from { transform: translateX(0); } to { transform: translateX(calc(100% + 32px)); } }
            .drawerOverlay-enter { animation: drawerOverlayFadeIn 180ms ease-out forwards; }
            .drawerOverlay-leave { animation: drawerOverlayFadeOut 180ms ease-in forwards; }
            .drawerAside-enter { animation: drawerSlideIn 280ms cubic-bezier(0.22, 1, 0.36, 1) forwards; }
            .drawerAside-leave { animation: drawerSlideOut 180ms ease-in forwards; }
        </style>
        <template x-teleport="body">
        <div x-show="drawerOpen" x-cloak
             x-transition:enter="drawerOverlay-enter"
             x-transition:leave="drawerOverlay-leave"
             @keydown.escape.window="closeDrawer()"
             @click="closeDrawer()"
             style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 999998;"></div>
        </template>
        <template x-teleport="body">
        <aside x-show="drawerOpen" x-cloak
               x-transition:enter="drawerAside-enter"
               x-transition:leave="drawerAside-leave"
               @click.stop
               style="position: fixed; top: 16px; right: 16px; bottom: 16px; width: calc(100vw - 32px); max-width: 560px; background: #fff; box-shadow: -8px 0 24px rgba(0,0,0,0.15); border-radius: 16px; z-index: 999999; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 88px; display: flex; align-items: center; justify-content: space-between; padding: 24px 28px 20px 28px; border-bottom: 1px solid #E5E7EB; background: #fff; box-sizing: border-box;">
                    <div>
                        <h2 style="font-size: 22px; font-weight: 700; color: #111827; margin: 0;" x-text="currentDataset?.title ?? ''"></h2>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: #6B7280;">
                            <span x-text="(currentDataset?.items.length ?? 0)"></span> item(s)
                        </p>
                    </div>
                    <button type="button" @click="closeDrawer()"
                            style="background: transparent; border: 0; color: #9CA3AF; cursor: pointer; padding: 4px;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>

                <div style="position: absolute; top: 88px; left: 0; right: 0; height: 60px; padding: 16px 28px 0 28px; background: #fff; box-sizing: border-box;">
                    <div style="position: relative;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9CA3AF" stroke-width="2" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%);"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                        <input type="text" x-model="drawerSearch" placeholder="Search by ID or title..."
                               style="width: 100%; padding: 10px 14px 10px 40px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: #111827;">
                    </div>
                </div>

                <div style="position: absolute; top: 148px; left: 0; right: 0; bottom: 0; overflow-y: auto; padding: 16px 28px 24px 28px; box-sizing: border-box;">
                    <template x-for="item in filteredItems" :key="item.id + '-' + item.code">
                        <div @click="$wire.dispatch(item.dispatch, [item.id])"
                             style="padding: 14px; border: 1px solid #E5E7EB; border-radius: 10px; margin-bottom: 10px; cursor: pointer; transition: background 0.15s; display: flex; flex-direction: column; gap: 10px;"
                             onmouseover="this.style.background='#F9FAFB';" onmouseout="this.style.background='#fff';">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">
                                <span style="background: #EEF2FF; color: #4F46E5; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 700; font-family: ui-monospace, SFMono-Regular, monospace;" x-text="item.code"></span>
                                <template x-if="item.priority_label">
                                    <span :style="`background: ${item.priority_color ? item.priority_color + '22' : '#F3F4F6'}; color: ${item.priority_color || '#374151'}; padding: 4px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; letter-spacing: 0.02em; white-space: nowrap;`"
                                          x-text="item.priority_label"></span>
                                </template>
                                <template x-if="!item.priority_label">
                                    <span style="font-size: 11px; color: #6B7280;" x-text="item.status"></span>
                                </template>
                            </div>
                            <div style="font-size: 14px; color: #111827; font-weight: 600; line-height: 1.4;"
                                 x-text="(item.title || '').length > 80 ? (item.title.slice(0, 80) + '...') : item.title"></div>
                            <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 14px; font-size: 12px; color: #6B7280;">
                                <span x-show="item.product" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                    <span x-text="item.product"></span>
                                </span>
                                <span x-show="item.module" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                    <span x-text="item.module"></span>
                                </span>
                                <span x-show="item.due_date" :style="`display: inline-flex; align-items: center; gap: 4px; ${item.is_overdue ? 'color: #DC2626;' : 'color: #6B7280;'}`">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span>Due <span x-text="item.due_date"></span><span x-show="item.is_overdue"> (<span x-text="item.overdue_days"></span>d overdue)</span></span>
                                </span>
                                <span x-show="item.created_date && !item.show_pdt_actions" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <span>Created <span x-text="item.created_date"></span></span>
                                </span>
                            </div>
                            <template x-if="item.show_pdt_actions">
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; padding-top: 10px; border-top: 1px solid #F3F4F6;">
                                    <button type="button"
                                            @click.stop="closeDrawer(); Livewire.dispatch('openCreateTaskModal', { ticketId: item.id })"
                                            style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px; background: #ECFDF5; color: #047857; border: 1px solid #A7F3D0; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer;"
                                            onmouseover="this.style.background='#D1FAE5';" onmouseout="this.style.background='#ECFDF5';">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                        Accept
                                    </button>
                                    <button type="button"
                                            @click.stop="openReason('reject', item)"
                                            style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px; background: #FEF2F2; color: #B91C1C; border: 1px solid #FECACA; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer;"
                                            onmouseover="this.style.background='#FEE2E2';" onmouseout="this.style.background='#FEF2F2';">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                                        Reject
                                    </button>
                                    <button type="button"
                                            @click.stop="openReason('hold', item)"
                                            style="display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 8px; background: #FFFBEB; color: #B45309; border: 1px solid #FDE68A; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer;"
                                            onmouseover="this.style.background='#FEF3C7';" onmouseout="this.style.background='#FFFBEB';">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="6" y="5" width="4" height="14"/><rect x="14" y="5" width="4" height="14"/></svg>
                                        On Hold
                                    </button>
                                </div>
                            </template>
                            <template x-if="item.show_mandays_banner">
                                <div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; color: #047857; font-size: 13px; font-weight: 600;">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                                    <span>Awaiting mandays estimation</span>
                                </div>
                            </template>
                        </div>
                    </template>
                    <div x-show="filteredItems.length === 0" style="text-align: center; padding: 40px 0; color: #9CA3AF; font-size: 13px;"
                         x-text="currentDataset?.empty ?? ''"></div>
                </div>
            </aside>
        </template>

        <!-- Reason modal (Reject / On Hold) - teleported to body so no ancestor transform can offset it -->
        <template x-teleport="body">
        <div x-show="reasonModalOpen" x-cloak x-transition.opacity.duration.150ms
             @keydown.escape.window="closeReason()"
             @click.self="closeReason()"
             style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 1000000; display: flex; align-items: center; justify-content: center; padding: 16px; box-sizing: border-box; margin: 0;">
            <div x-show="reasonModalOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 @click.stop
                 style="background: #fff; width: 100%; max-width: 440px; border-radius: 14px; box-shadow: 0 25px 50px rgba(0,0,0,0.25); display: flex; flex-direction: column; overflow: hidden; margin: auto;">
                <div style="padding: 20px 24px; border-bottom: 1px solid #F3F4F6;">
                    <h3 style="font-size: 17px; font-weight: 700; color: #111827; margin: 0;" x-text="reasonTitle"></h3>
                    <p x-show="reasonTicket" style="margin: 4px 0 0 0; font-size: 12px; color: #6B7280;">
                        <span x-text="reasonTicket?.code"></span> —
                        <span x-text="(reasonTicket?.title || '').length > 60 ? reasonTicket.title.slice(0,60) + '...' : reasonTicket?.title"></span>
                    </p>
                </div>
                <div style="padding: 20px 24px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px;">
                        <span x-text="reasonLabel"></span> <span style="color: #EF4444;">*</span>
                    </label>
                    <textarea id="pdtReasonTextarea" x-model="reasonValue" rows="4"
                              :placeholder="reasonAction === 'reject' ? 'Explain why this ticket is being rejected...' : 'Explain why this ticket is being put on hold...'"
                              style="width: 100%; padding: 10px 12px; background: #f9fafb; border: 1px solid #E5E7EB; border-radius: 10px; font-size: 13px; color: #111827; resize: vertical; font-family: inherit;"></textarea>
                </div>
                <div style="padding: 16px 24px; border-top: 1px solid #F3F4F6; display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" @click="closeReason()"
                            style="padding: 10px 20px; background: #fff; color: #374151; border: 1px solid #D1D5DB; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        Cancel
                    </button>
                    <button type="button" @click="submitReason()" :disabled="(reasonValue || '').trim() === ''"
                            :style="(reasonValue || '').trim() === '' ? 'padding: 10px 22px; background: #D1D5DB; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: not-allowed;' : (reasonAction === 'reject' ? 'padding: 10px 22px; background: #DC2626; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer;' : 'padding: 10px 22px; background: #D97706; color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer;')">
                        <span x-text="reasonAction === 'reject' ? 'Reject Ticket' : 'Mark On Hold'"></span>
                    </button>
                </div>
            </div>
        </div>
        </template>
    </div>

    <livewire:task-modal />
    <livewire:ticket-modal />
    <livewire:create-task-drawer />
    <livewire:create-bug-drawer />
    <livewire:create-ticket-drawer />
    <livewire:create-suggestion-drawer />
    <livewire:create-creative-request-drawer />
    <livewire:create-release-drawer />
</x-filament-panels::page>
