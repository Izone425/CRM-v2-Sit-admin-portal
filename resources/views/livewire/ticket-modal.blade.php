{{-- filepath: /var/www/html/timeteccrm/resources/views/livewire/ticket-modal.blade.php --}}
<div>
    {{-- Ticket Modal --}}
    @if($showTicketModal && $selectedTicket)
        @include('filament.pages.partials.ticket-modal')
    @endif

    {{-- Reopen Modal --}}
    @if($showReopenModal && $selectedTicket)
        @include('filament.pages.partials.reopen-modal')
    @endif
</div>
