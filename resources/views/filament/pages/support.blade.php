<style>
    /* Container styling */
    .support-container {
        grid-column: 1 / -1;
        width: 100%;
    }

    /* Main layout with grid setup */
    .dashboard-layout {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 15px;
    }

    /* Group column styling */
    .group-column {
        padding-right: 10px;
        width: 230px;
    }

    .group-box {
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
        max-height: 82px;
        max-width: 220px;
    }

    .group-box:hover {
        background-color: #f9fafb;
        transform: translateX(3px);
    }

    .group-box.selected {
        background-color: #f9fafb;
        transform: translateX(5px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .group-info {
        display: flex;
        flex-direction: column;
    }

    .group-title {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 8px;
        text-align: left;
    }

    .group-count {
        font-size: 24px;
        font-weight: bold;
    }

    /* GROUP COLORS */
    .group-ticketing { border-top-color: #ec4899; }
    .group-ticketing .group-count { color: #ec4899; }

    /* Category column styling */
    .category-column {
        padding-right: 10px;
    }

    /* Category container */
    .category-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 10px;
        border-right: 1px solid #e5e7eb;
        padding-right: 10px;
        max-height: 75vh;
        overflow-y: auto;
    }

    /* Stat box styling */
    .stat-box {
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

    .stat-box:hover {
        background-color: #f9fafb;
        transform: translateX(3px);
    }

    .stat-box.selected {
        background-color: #f9fafb;
        transform: translateX(5px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }

    .stat-info {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
    }

    .stat-count {
        font-size: 20px;
        font-weight: bold;
        margin: 0;
        line-height: 1.2;
    }

    .stat-label {
        color: #6b7280;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.2;
    }

    /* Content area */
    .content-column {
        min-height: 600px;
    }

    .content-area {
        min-height: 600px;
    }

    .content-area .fi-ta {
        margin-top: 0;
    }

    .content-area .fi-ta-content {
        padding: 0.75rem !important;
    }

    /* Hint message */
    .hint-message {
        text-align: center;
        background-color: #f9fafb;
        border-radius: 0.5rem;
        border: 1px dashed #d1d5db;
        height: 530px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .hint-message h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .hint-message p {
        color: #6b7280;
    }

    /* STAT BOX COLORS */
    .license-pending { border-left: 4px solid #8b5cf6; }
    .license-pending .stat-count { color: #8b5cf6; }

    .license-completed { border-left: 4px solid #a855f7; }
    .license-completed .stat-count { color: #a855f7; }

    .migration-pending { border-left: 4px solid #10b981; }
    .migration-pending .stat-count { color: #10b981; }

    /* Selected states */
    .stat-box.selected.license-pending   { background-color: rgba(139, 92, 246, 0.05); border-left-width: 6px; }
    .stat-box.selected.license-completed { background-color: rgba(168, 85, 247, 0.05); border-left-width: 6px; }
    .stat-box.selected.migration-pending { background-color: rgba(16, 185, 129, 0.05); border-left-width: 6px; }

    [x-transition] {
        transition: all 0.2s ease-out;
    }

    @media (max-width: 1024px) {
        .dashboard-layout {
            grid-template-columns: 100%;
            grid-template-rows: auto auto;
        }
        .group-column { width: 100%; }
        .category-container {
            grid-template-columns: repeat(3, 1fr);
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
            max-height: none;
        }
    }

    @media (max-width: 768px) {
        .category-container { grid-template-columns: repeat(2, 1fr); }
        .stat-box:hover, .group-box:hover { transform: none; }
        .stat-box.selected, .group-box.selected { transform: none; }
    }

    @media (max-width: 640px) {
        .category-container { grid-template-columns: 1fr; }
    }
</style>

@php
    $ticketCompletedCount = app(\App\Livewire\SupportDashboard\SupportCompletedToday::class)
        ->getCompletedTicketsQuery()->count();

    $ticketAllStatusCount = app(\App\Livewire\SupportDashboard\SupportAllStatus::class)
        ->getCompletedTicketsQuery()->count();

    $ticketCommentPendingFECount = app(\App\Livewire\SupportDashboard\SupportCommentPendingFe::class)
        ->getCompletedTicketsQuery()->count();

    $ticketReminderTotal = $ticketCompletedCount;
@endphp

<div id="support-container" class="support-container"
    x-data="{
        selectedGroup: null,
        selectedStat: null,

        setSelectedGroup(value) {
            if (this.selectedGroup === value) {
                this.selectedGroup = null;
                this.selectedStat = null;
            } else {
                this.selectedGroup = value;
                this.selectedStat = null;
            }
        },

        setSelectedStat(value) {
            if (this.selectedStat === value) {
                this.selectedStat = null;
            } else {
                this.selectedStat = value;
            }
        },

        init() {
            this.selectedGroup = null;
            this.selectedStat = null;
        }
    }"
    x-init="init()">

    <div class="dashboard-layout" wire:poll.300s>
        <!-- Left sidebar with main category groups -->
        <div class="group-column">
            <!-- TICKET REMINDER -->
            <div class="group-box group-ticketing"
                :class="{'selected': selectedGroup === 'ticket-reminder'}"
                @click="setSelectedGroup('ticket-reminder')">
                <div class="group-info">
                    <div class="group-title">Ticket Reminder</div>
                </div>
                <div class="group-count">{{ $ticketReminderTotal }}</div>
            </div>
        </div>

        <!-- Right content column -->
        <div class="content-column">
            <!-- TICKET REMINDER Sub-tabs -->
            <div class="category-container" x-show="selectedGroup === 'ticket-reminder'" x-transition>
                <div class="stat-box license-pending"
                    :class="{'selected': selectedStat === 'ticket-reminder-comment-pending-fe'}"
                    @click="setSelectedStat('ticket-reminder-comment-pending-fe')">
                    <div class="stat-info">
                        <div class="stat-label">Comment : Pending FE</div>
                    </div>
                    <div class="stat-count">{{ $ticketCommentPendingFECount }}</div>
                </div>

                <div class="stat-box license-pending"
                    :class="{'selected': selectedStat === 'ticket-reminder-completed'}"
                    @click="setSelectedStat('ticket-reminder-completed')">
                    <div class="stat-info">
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-count">{{ $ticketCompletedCount }}</div>
                </div>

                <div class="stat-box migration-pending"
                    :class="{'selected': selectedStat === 'ticket-reminder-all-status'}"
                    @click="setSelectedStat('ticket-reminder-all-status')">
                    <div class="stat-info">
                        <div class="stat-label">All Status</div>
                    </div>
                    <div class="stat-count">{{ $ticketAllStatusCount }}</div>
                </div>
            </div>

            <!-- Content area for tables -->
            <div class="content-area">
                <!-- Hint when nothing selected -->
                <div class="hint-message" x-show="selectedGroup === null || selectedStat === null" x-transition>
                    <h3 x-text="selectedGroup === null ? 'Select a category to continue' : 'Select a subcategory to view data'"></h3>
                    <p x-text="selectedGroup === null ? 'Click on any of the category boxes to see options' : 'Click on any of the subcategory boxes to display the corresponding information'"></p>
                </div>

                <!-- TICKET REMINDER Tables -->
                <div x-show="selectedStat === 'ticket-reminder-comment-pending-fe'" x-transition>
                    <div class="p-4">
                        <livewire:support-dashboard.support-comment-pending-fe />
                    </div>
                </div>
                <div x-show="selectedStat === 'ticket-reminder-completed'" x-transition>
                    <div class="p-4">
                        <livewire:support-dashboard.support-completed-today />
                    </div>
                </div>
                <div x-show="selectedStat === 'ticket-reminder-all-status'" x-transition>
                    <div class="p-4">
                        <livewire:support-dashboard.support-all-status />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.resetSupport = function() {
            const container = document.getElementById('support-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                container.__x.$data.selectedStat = null;
            }
        };

        window.addEventListener('reset-support-dashboard', function() {
            window.resetSupport();
        });
    });
</script>
