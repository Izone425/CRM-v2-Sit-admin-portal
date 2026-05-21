<style>
    .v2-dashboard-layout {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 15px;
    }

    .v2-group-column {
        padding-right: 10px;
        width: 230px;
    }

    .v2-group-box {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px 15px;
        cursor: pointer;
        transition: all 0.2s;
        border-top: 4px solid transparent;
        display: flex;
        flex-direction: column;
        justify-content: center;
        margin-bottom: 15px;
        width: 100%;
        text-align: center;
        max-height: 95px;
        max-width: 220px;
    }

    .v2-group-box:hover { background-color: #f9fafb; transform: translateX(3px); }
    .v2-group-box.selected { background-color: #f9fafb; transform: translateX(5px); box-shadow: 0 4px 8px rgba(0,0,0,0.1); }

    .v2-group-info { display: flex; flex-direction: column; }
    .v2-group-title { font-size: 15px; font-weight: 600; margin-bottom: 8px; text-align: left; }
    .v2-group-count { font-size: 24px; font-weight: bold; }

    .v2-group-pc { border-top-color: #f59e0b; }
    .v2-group-pc .v2-group-count { color: #f59e0b; }
    .v2-group-pc.selected { background-color: rgba(245, 158, 11, 0.05); }

    .v2-group-pp { border-top-color: #dc2626; }
    .v2-group-pp .v2-group-count { color: #dc2626; }
    .v2-group-pp.selected { background-color: rgba(220, 38, 38, 0.05); }

    .v2-category-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 10px;
        border-right: 1px solid #e5e7eb;
        padding-right: 10px;
        max-height: 75vh;
        overflow-y: auto;
    }

    .v2-stat-box {
        background-color: white;
        width: 100%;
        min-height: 65px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        cursor: pointer;
        transition: all 0.2s ease;
        border-left: 4px solid transparent;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 15px;
        margin-bottom: 8px;
    }

    .v2-stat-box:hover { background-color: #f9fafb; transform: translateX(3px); }
    .v2-stat-box.selected { background-color: #f9fafb; transform: translateX(5px); box-shadow: 0 2px 5px rgba(0,0,0,0.15); }

    .v2-stat-info { display: flex; flex-direction: column; align-items: flex-start; justify-content: center; }
    .v2-stat-count { font-size: 20px; font-weight: bold; margin: 0; line-height: 1.2; }
    .v2-stat-label { color: #6b7280; font-size: 13px; font-weight: 500; line-height: 1.2; }

    .v2-content-column { min-height: 600px; }
    .v2-content-area { min-height: 600px; }
    .v2-content-area .fi-ta { margin-top: 0; }
    .v2-content-area .fi-ta-content { padding: 0.75rem !important; }

    .v2-hint-message {
        text-align: center; background-color: #f9fafb; border-radius: 0.5rem;
        border: 1px dashed #d1d5db; height: 530px; display: flex;
        flex-direction: column; justify-content: center; align-items: center;
    }
    .v2-hint-message h3 { font-size: 1.25rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem; }
    .v2-hint-message p { color: #6b7280; }

    /* Pending Confirmation sub-tab colors */
    .v2-follow-up-today-pc { border-left: 4px solid #f59e0b; }
    .v2-follow-up-today-pc .v2-stat-count { color: #f59e0b; }
    .v2-stat-box.selected.v2-follow-up-today-pc { background-color: rgba(245, 158, 11, 0.05); border-left-width: 6px; }

    .v2-follow-up-overdue-pc { border-left: 4px solid #f97316; }
    .v2-follow-up-overdue-pc .v2-stat-count { color: #f97316; }
    .v2-stat-box.selected.v2-follow-up-overdue-pc { background-color: rgba(249, 115, 22, 0.05); border-left-width: 6px; }

    .v2-follow-up-future-pc { border-left: 4px solid #ea580c; }
    .v2-follow-up-future-pc .v2-stat-count { color: #ea580c; }
    .v2-stat-box.selected.v2-follow-up-future-pc { background-color: rgba(234, 88, 12, 0.05); border-left-width: 6px; }

    .v2-follow-up-all-pc { border-left: 4px solid #dc2626; }
    .v2-follow-up-all-pc .v2-stat-count { color: #dc2626; }
    .v2-stat-box.selected.v2-follow-up-all-pc { background-color: rgba(220, 38, 38, 0.05); border-left-width: 6px; }

    /* Pending Payment sub-tab colors */
    .v2-follow-up-today-pp { border-left: 4px solid #dc2626; }
    .v2-follow-up-today-pp .v2-stat-count { color: #dc2626; }
    .v2-stat-box.selected.v2-follow-up-today-pp { background-color: rgba(220, 38, 38, 0.05); border-left-width: 6px; }

    .v2-follow-up-overdue-pp { border-left: 4px solid #b91c1c; }
    .v2-follow-up-overdue-pp .v2-stat-count { color: #b91c1c; }
    .v2-stat-box.selected.v2-follow-up-overdue-pp { background-color: rgba(185, 28, 28, 0.05); border-left-width: 6px; }

    .v2-follow-up-future-pp { border-left: 4px solid #991b1b; }
    .v2-follow-up-future-pp .v2-stat-count { color: #991b1b; }
    .v2-stat-box.selected.v2-follow-up-future-pp { background-color: rgba(153, 27, 27, 0.05); border-left-width: 6px; }

    .v2-follow-up-all-pp { border-left: 4px solid #7f1d1d; }
    .v2-follow-up-all-pp .v2-stat-count { color: #7f1d1d; }
    .v2-stat-box.selected.v2-follow-up-all-pp { background-color: rgba(127, 29, 29, 0.05); border-left-width: 6px; }

    @media (max-width: 1024px) {
        .v2-dashboard-layout { grid-template-columns: 100%; }
        .v2-group-column { width: 100%; }
        .v2-category-container { grid-template-columns: repeat(3, 1fr); border-right: none; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 15px; max-height: none; }
    }
    @media (max-width: 768px) {
        .v2-category-container { grid-template-columns: repeat(2, 1fr); }
        .v2-stat-box:hover, .v2-group-box:hover { transform: none; }
        .v2-stat-box.selected, .v2-group-box.selected { transform: none; }
    }
</style>

<div x-data="{
        selectedGroup: null,
        selectedStat: null,
        setSelectedGroup(value) {
            if (this.selectedGroup === value) { this.selectedGroup = null; this.selectedStat = null; }
            else { this.selectedGroup = value; this.selectedStat = null; }
        },
        setSelectedStat(value) {
            this.selectedStat = this.selectedStat === value ? null : value;
        }
    }">

    <div class="v2-dashboard-layout" wire:poll.300s>
        <!-- Left sidebar -->
        <div class="v2-group-column">
            <div class="v2-group-box v2-group-pc"
                :class="{'selected': selectedGroup === 'pc'}"
                @click="setSelectedGroup('pc')">
                <div class="v2-group-info">
                    <div class="v2-group-title">Pending Confirmation</div>
                </div>
                <div class="v2-group-count">{{ $followUpTotalPC }}</div>
            </div>

            <div class="v2-group-box v2-group-pp"
                :class="{'selected': selectedGroup === 'pp'}"
                @click="setSelectedGroup('pp')">
                <div class="v2-group-info">
                    <div class="v2-group-title">Pending Payment</div>
                </div>
                <div class="v2-group-count">{{ $followUpTotalPP }}</div>
            </div>
        </div>

        <!-- Right content -->
        <div class="v2-content-column">
            <!-- Pending Confirmation sub-tabs -->
            <div class="v2-category-container" x-show="selectedGroup === 'pc'" x-transition>
                <div class="v2-stat-box v2-follow-up-today-pc" :class="{'selected': selectedStat === 'today-pc'}" @click="setSelectedStat('today-pc')">
                    <div class="v2-stat-info"><div class="v2-stat-label">Today</div></div>
                    <div class="v2-stat-count">{{ $followUpTodayPC }}</div>
                </div>
                <div class="v2-stat-box v2-follow-up-overdue-pc" :class="{'selected': selectedStat === 'overdue-pc'}" @click="setSelectedStat('overdue-pc')">
                    <div class="v2-stat-info"><div class="v2-stat-label">Overdue</div></div>
                    <div class="v2-stat-count">{{ $followUpOverduePC }}</div>
                </div>
                <div class="v2-stat-box v2-follow-up-future-pc" :class="{'selected': selectedStat === 'future-pc'}" @click="setSelectedStat('future-pc')">
                    <div class="v2-stat-info"><div class="v2-stat-label">Next Follow Up</div></div>
                    <div class="v2-stat-count">{{ $followUpFuturePC }}</div>
                </div>
                <div class="v2-stat-box v2-follow-up-all-pc" :class="{'selected': selectedStat === 'all-pc'}" @click="setSelectedStat('all-pc')">
                    <div class="v2-stat-info"><div class="v2-stat-label">All Follow Ups</div></div>
                    <div class="v2-stat-count">{{ $followUpAllPC }}</div>
                </div>
            </div>

            <!-- Pending Payment sub-tabs -->
            <div class="v2-category-container" x-show="selectedGroup === 'pp'" x-transition>
                <div class="v2-stat-box v2-follow-up-today-pp" :class="{'selected': selectedStat === 'today-pp'}" @click="setSelectedStat('today-pp')">
                    <div class="v2-stat-info"><div class="v2-stat-label">Today</div></div>
                    <div class="v2-stat-count">{{ $followUpTodayPP }}</div>
                </div>
                <div class="v2-stat-box v2-follow-up-overdue-pp" :class="{'selected': selectedStat === 'overdue-pp'}" @click="setSelectedStat('overdue-pp')">
                    <div class="v2-stat-info"><div class="v2-stat-label">Overdue</div></div>
                    <div class="v2-stat-count">{{ $followUpOverduePP }}</div>
                </div>
                <div class="v2-stat-box v2-follow-up-future-pp" :class="{'selected': selectedStat === 'future-pp'}" @click="setSelectedStat('future-pp')">
                    <div class="v2-stat-info"><div class="v2-stat-label">Next Follow Up</div></div>
                    <div class="v2-stat-count">{{ $followUpFuturePP }}</div>
                </div>
                <div class="v2-stat-box v2-follow-up-all-pp" :class="{'selected': selectedStat === 'all-pp'}" @click="setSelectedStat('all-pp')">
                    <div class="v2-stat-info"><div class="v2-stat-label">All Follow Ups</div></div>
                    <div class="v2-stat-count">{{ $followUpAllPP }}</div>
                </div>
            </div>

            <!-- Content area -->
            <div class="v2-content-area">
                <div class="v2-hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a category to continue' : 'Select a subcategory to view data'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the category boxes to see options' : 'Click on any of the subcategory boxes to display the corresponding information'"></p>
                </div>

                <!-- Pending Confirmation tables -->
                <div x-show="selectedStat === 'today-pc'" x-transition>
                    <div class="p-4"><livewire:hr-admin-dashboard.ar-v2-follow-up-today-pc /></div>
                </div>
                <div x-show="selectedStat === 'overdue-pc'" x-transition>
                    <div class="p-4"><livewire:hr-admin-dashboard.ar-v2-follow-up-overdue-pc /></div>
                </div>
                <div x-show="selectedStat === 'future-pc'" x-transition>
                    <div class="p-4"><livewire:hr-admin-dashboard.ar-v2-follow-up-upcoming-pc /></div>
                </div>
                <div x-show="selectedStat === 'all-pc'" x-transition>
                    <div class="p-4"><livewire:hr-admin-dashboard.ar-v2-follow-up-all-pc /></div>
                </div>

                <!-- Pending Payment tables -->
                <div x-show="selectedStat === 'today-pp'" x-transition>
                    <div class="p-4"><livewire:hr-admin-dashboard.ar-v2-follow-up-today-pp /></div>
                </div>
                <div x-show="selectedStat === 'overdue-pp'" x-transition>
                    <div class="p-4"><livewire:hr-admin-dashboard.ar-v2-follow-up-overdue-pp /></div>
                </div>
                <div x-show="selectedStat === 'future-pp'" x-transition>
                    <div class="p-4"><livewire:hr-admin-dashboard.ar-v2-follow-up-upcoming-pp /></div>
                </div>
                <div x-show="selectedStat === 'all-pp'" x-transition>
                    <div class="p-4"><livewire:hr-admin-dashboard.ar-v2-follow-up-all-pp /></div>
                </div>
            </div>
        </div>
    </div>
</div>
