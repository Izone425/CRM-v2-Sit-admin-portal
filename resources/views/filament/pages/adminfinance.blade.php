{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/adminfinance.blade.php --}}
<style>
    /* Container styling */
    .finance-ho-container {
        grid-column: 1 / -1;
        width: 100%;
    }

    /* Main layout with grid setup */
    .finance-ho-layout {
        display: grid;
        grid-template-columns: auto 1fr;
        gap: 15px;
    }

    /* Group column styling */
    .finance-ho-group-column {
        padding-right: 10px;
        width: 230px;
    }

    .finance-ho-group-box {
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

    .finance-ho-group-box:hover {
        background-color: #f9fafb;
        transform: translateX(3px);
    }

    .finance-ho-group-box.selected {
        background-color: #f0f7ff;
        transform: translateX(5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border-top-width: 5px;
        outline: 2px solid currentColor;
        outline-offset: -2px;
    }

    .finance-ho-group-info {
        display: flex;
        flex-direction: column;
    }

    .finance-ho-group-title {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 8px;
        text-align: left;
    }

    .finance-ho-group-count {
        font-size: 24px;
        font-weight: bold;
    }

    /* GROUP COLORS */
    .group-fh-all { border-top-color: #8b5cf6; }
    .group-fh-all .finance-ho-group-count { color: #8b5cf6; }

    .group-fh-new { border-top-color: #3b82f6; }
    .group-fh-new .finance-ho-group-count { color: #3b82f6; }

    .group-fh-pending-payment { border-top-color: #f59e0b; }
    .group-fh-pending-payment .finance-ho-group-count { color: #f59e0b; }

    .group-fh-completed { border-top-color: #10b981; }
    .group-fh-completed .finance-ho-group-count { color: #10b981; }

    .group-fh-rejected { border-top-color: #ef4444; }
    .group-fh-rejected .finance-ho-group-count { color: #ef4444; }

    .group-fh-draft { border-top-color: #f97316; }
    .group-fh-draft .finance-ho-group-count { color: #f97316; }

    .finance-ho-group-box.selected.group-fh-all { outline-color: #8b5cf6; background-color: rgba(139, 92, 246, 0.08); }
    .finance-ho-group-box.selected.group-fh-new { outline-color: #3b82f6; background-color: rgba(59, 130, 246, 0.08); }
    .finance-ho-group-box.selected.group-fh-pending-payment { outline-color: #f59e0b; background-color: rgba(245, 158, 11, 0.08); }
    .finance-ho-group-box.selected.group-fh-completed { outline-color: #10b981; background-color: rgba(16, 185, 129, 0.08); }
    .finance-ho-group-box.selected.group-fh-rejected { outline-color: #ef4444; background-color: rgba(239, 68, 68, 0.08); }
    .finance-ho-group-box.selected.group-fh-draft { outline-color: #f97316; background-color: rgba(249, 115, 22, 0.08); }

    /* Content area */
    .finance-ho-content-column {
        min-height: 600px;
    }

    .finance-ho-content-area {
        min-height: 600px;
    }

    .finance-ho-content-area .fi-ta {
        margin-top: 0;
    }

    .finance-ho-content-area .fi-ta-content {
        padding: 0.75rem !important;
    }

    /* Hint message */
    .finance-ho-hint-message {
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

    .finance-ho-hint-message h3 {
        font-size: 1.25rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
    }

    .finance-ho-hint-message p {
        color: #6b7280;
    }

    /* Animation for tab switching */
    [x-transition] {
        transition: all 0.2s ease-out;
    }

    /* Responsive adjustments */
    @media (max-width: 1024px) {
        .finance-ho-layout {
            grid-template-columns: 100%;
            grid-template-rows: auto auto;
        }

        .finance-ho-group-column {
            width: 100%;
        }

        .finance-ho-group-container {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            border-right: none;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
    }

    @media (max-width: 768px) {
        .finance-ho-group-container {
            grid-template-columns: repeat(2, 1fr);
        }

        .finance-ho-group-box:hover {
            transform: none;
        }

        .finance-ho-group-box.selected {
            transform: none;
        }
    }

    @media (max-width: 640px) {
        .finance-ho-group-container {
            grid-template-columns: 1fr;
        }
    }
</style>

@php
    use App\Models\FinanceHandover;

    $fhAllCount = FinanceHandover::count();
    $fhNewCount = FinanceHandover::where('status', 'New')->count();
    $fhPendingPaymentCount = FinanceHandover::where('status', 'Pending Payment')->count();
    $fhCompletedCount = FinanceHandover::where('status', 'Completed')->count();
    $fhRejectedCount = FinanceHandover::where('status', 'Rejected')->count();
    $fhDraftCount = FinanceHandover::where('status', 'Draft')->count();
@endphp

<div id="finance-ho-container" class="finance-ho-container"
    x-data="{
        selectedGroup: null,

        setSelectedGroup(value) {
            if (this.selectedGroup === value) {
                this.selectedGroup = null;
            } else {
                this.selectedGroup = value;
            }
        },

        init() {
            this.selectedGroup = null;
        }
    }"
    x-init="init()">

    <div class="finance-ho-layout" wire:poll.300s>
        <!-- Left sidebar with main category groups -->
        <div class="finance-ho-group-column">
            <!-- 1. All -->
            <div class="finance-ho-group-box group-fh-all"
                :class="{'selected': selectedGroup === 'fh-all'}"
                @click="setSelectedGroup('fh-all')">
                <div class="finance-ho-group-info">
                    <div class="finance-ho-group-title">All Handover</div>
                </div>
                @if($fhAllCount > 0)
                <div class="finance-ho-group-count">{{ $fhAllCount }}</div>
                @endif
            </div>

            <!-- 2. New -->
            <div class="finance-ho-group-box group-fh-new"
                :class="{'selected': selectedGroup === 'fh-new'}"
                @click="setSelectedGroup('fh-new')">
                <div class="finance-ho-group-info">
                    <div class="finance-ho-group-title">New Task</div>
                </div>
                @if($fhNewCount > 0)
                <div class="finance-ho-group-count">{{ $fhNewCount }}</div>
                @endif
            </div>

            <!-- 3. Pending Payment -->
            <div class="finance-ho-group-box group-fh-pending-payment"
                :class="{'selected': selectedGroup === 'fh-pending-payment'}"
                @click="setSelectedGroup('fh-pending-payment')">
                <div class="finance-ho-group-info">
                    <div class="finance-ho-group-title">Pending Payment</div>
                </div>
                @if($fhPendingPaymentCount > 0)
                <div class="finance-ho-group-count">{{ $fhPendingPaymentCount }}</div>
                @endif
            </div>

            <!-- 4. Completed -->
            <div class="finance-ho-group-box group-fh-completed"
                :class="{'selected': selectedGroup === 'fh-completed'}"
                @click="setSelectedGroup('fh-completed')">
                <div class="finance-ho-group-info">
                    <div class="finance-ho-group-title">Completed</div>
                </div>
                @if($fhCompletedCount > 0)
                <div class="finance-ho-group-count">{{ $fhCompletedCount }}</div>
                @endif
            </div>

            <!-- 5. Rejected -->
            <div class="finance-ho-group-box group-fh-rejected"
                :class="{'selected': selectedGroup === 'fh-rejected'}"
                @click="setSelectedGroup('fh-rejected')">
                <div class="finance-ho-group-info">
                    <div class="finance-ho-group-title">Rejected</div>
                </div>
                @if($fhRejectedCount > 0)
                <div class="finance-ho-group-count">{{ $fhRejectedCount }}</div>
                @endif
            </div>

            <!-- 6. Draft -->
            <div class="finance-ho-group-box group-fh-draft"
                :class="{'selected': selectedGroup === 'fh-draft'}"
                @click="setSelectedGroup('fh-draft')">
                <div class="finance-ho-group-info">
                    <div class="finance-ho-group-title">Draft</div>
                </div>
                @if($fhDraftCount > 0)
                <div class="finance-ho-group-count">{{ $fhDraftCount }}</div>
                @endif
            </div>
        </div>

        <!-- Right content column -->
        <div class="finance-ho-content-column">
            <!-- Content area for tables -->
            <div class="finance-ho-content-area">
                <!-- Display hint message when nothing is selected -->
                <div class="finance-ho-hint-message" x-show="selectedGroup === null" x-transition>
                    <h3>Select a category to continue</h3>
                    <p>Click on any of the category boxes to view finance handover data</p>
                </div>

                <!-- 1. All Table -->
                <div x-show="selectedGroup === 'fh-all'" x-transition>
                    <div class="p-4">
                        <livewire:admin-finance-dashboard.finance-handover-all-table />
                    </div>
                </div>

                <!-- 2. New Table -->
                <div x-show="selectedGroup === 'fh-new'" x-transition>
                    <div class="p-4">
                        <livewire:admin-finance-dashboard.finance-handover-new-table />
                    </div>
                </div>

                <!-- 3. Pending Payment Table -->
                <div x-show="selectedGroup === 'fh-pending-payment'" x-transition>
                    <div class="p-4">
                        <livewire:admin-finance-dashboard.finance-handover-pending-payment-table />
                    </div>
                </div>

                <!-- 4. Completed Table -->
                <div x-show="selectedGroup === 'fh-completed'" x-transition>
                    <div class="p-4">
                        <livewire:admin-finance-dashboard.finance-handover-completed-table />
                    </div>
                </div>

                <!-- 5. Rejected Table -->
                <div x-show="selectedGroup === 'fh-rejected'" x-transition>
                    <div class="p-4">
                        <livewire:admin-finance-dashboard.finance-handover-rejected-table />
                    </div>
                </div>

                <!-- 6. Draft Table -->
                <div x-show="selectedGroup === 'fh-draft'" x-transition>
                    <div class="p-4">
                        <livewire:admin-finance-dashboard.finance-handover-draft-table />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.resetAdminFinance = function() {
            const container = document.getElementById('finance-ho-container');
            if (container && container.__x) {
                container.__x.$data.selectedGroup = null;
                console.log('Admin Finance dashboard reset via global function');
            }
        };

        window.addEventListener('reset-admin-finance-dashboard', function() {
            window.resetAdminFinance();
        });
    });
</script>
