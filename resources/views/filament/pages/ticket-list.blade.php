{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/ticket-list.blade.php --}}
<x-filament-panels::page>
    <style>
        .tab-button {
            display: inline-flex;
            align-items: center;
            padding: 12px 24px;
            border: 2px solid;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            background: none;
            outline: none;
            position: relative;
            overflow: hidden;
        }

        .tab-button:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .tab-button.active-v1 {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-color: #059669;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.4), 0 4px 6px -2px rgba(16, 185, 129, 0.05);
        }

        .tab-button.active-v1:hover {
            background: linear-gradient(135deg, #059669 0%, #047857 100%);
            box-shadow: 0 20px 25px -5px rgba(16, 185, 129, 0.5), 0 10px 10px -5px rgba(16, 185, 129, 0.1);
        }

        .tab-button.active-v2 {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            border-color: #2563eb;
            color: white;
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4), 0 4px 6px -2px rgba(59, 130, 246, 0.05);
        }

        .tab-button.active-v2:hover {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            box-shadow: 0 20px 25px -5px rgba(59, 130, 246, 0.5), 0 10px 10px -5px rgba(59, 130, 246, 0.1);
        }

        .tab-button.inactive {
            background: white;
            border-color: #d1d5db;
            color: #374151;
        }

        .tab-button.inactive:hover {
            background: #f9fafb;
            border-color: #9ca3af;
            color: #111827;
        }

        .ticket-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .tab-icon {
            width: 20px;
            height: 20px;
            margin-right: 8px;
            flex-shrink: 0;
        }

        .content-area {
            padding: 0;
            margin: 0;
        }

        .tab-container {
            display: flex;
            justify-content: flex-start;
            gap: 16px;
        }

        .space-y-6 > * + * {
            margin-top: 24px;
        }

        /* Active tab glow effect */
        .tab-button.active-v1::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 50%, transparent 100%);
            pointer-events: none;
        }

        .tab-button.active-v2::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.2) 0%, rgba(255, 255, 255, 0.1) 50%, transparent 100%);
            pointer-events: none;
        }

        /* Button ripple effect */
        .tab-button:active {
            transform: scale(0.98);
        }

        /* Focus states for accessibility */
        .tab-button:focus {
            outline: 2px solid #3b82f6;
            outline-offset: 2px;
        }

        .tab-button.active-v1:focus {
            outline-color: #10b981;
        }

        .tab-button.active-v2:focus {
            outline-color: #3b82f6;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .tab-container {
                flex-direction: column;
                gap: 12px;
            }

            .tab-button {
                padding: 10px 20px;
                font-size: 13px;
                justify-content: center;
            }
        }

        /* Animation for tab switching */
        .ticket-container {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <style>
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
    </style>

    <div style="display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 16px; flex-wrap: wrap;">
        {{-- Version Toggle --}}
        <div class="scope-toggle">
            <button type="button" wire:click="setActiveTab('all')" class="{{ $activeTab === 'all' ? 'active' : '' }}">All</button>
            <button type="button" wire:click="setActiveTab('v1')" class="{{ $activeTab === 'v1' ? 'active' : '' }}">Version 1 Tickets</button>
            <button type="button" wire:click="setActiveTab('v2')" class="{{ $activeTab === 'v2' ? 'active' : '' }}">Version 2 Tickets</button>
        </div>

        {{-- Scope Toggle --}}
        <div class="scope-toggle">
            <button type="button" wire:click="setTicketScope('all')" class="{{ $ticketScope === 'all' ? 'active' : '' }}">All Tickets</button>
            <button type="button" wire:click="setTicketScope('my')" class="{{ $ticketScope === 'my' ? 'active' : '' }}">My Tickets</button>
        </div>
    </div>

    {{-- Content Area --}}
    <div class="space-y-6">
        @if($activeTab === 'all')
            <div class="ticket-container">
                <div class="content-area">
                    <livewire:ticket-list-v1 :scope="$ticketScope" :productIds="[1, 2]" :key="'tl-all-' . $ticketScope" />
                </div>
            </div>
        @endif

        @if($activeTab === 'v1')
            <div class="ticket-container">
                <div class="content-area">
                    <livewire:ticket-list-v1 :scope="$ticketScope" :key="'tl-v1-' . $ticketScope" />
                </div>
            </div>
        @endif

        @if($activeTab === 'v2')
            <div class="ticket-container">
                <div class="content-area">
                    <livewire:ticket-list-v2 :scope="$ticketScope" :key="'tl-v2-' . $ticketScope" />
                </div>
            </div>
        @endif
    </div>

    {{-- Ticket Modal --}}
    @if($showTicketModal && $selectedTicket)
        @include('filament.pages.partials.ticket-modal')
    @endif

    {{-- Reopen Modal --}}
    @if($showReopenModal && $selectedTicket)
        @include('filament.pages.partials.reopen-modal')
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
