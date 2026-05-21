{{-- filepath: /var/www/html/timeteccrm/resources/views/livewire/ticket-list-v2.blade.php --}}
<div>
    {{-- Table --}}
    {{ $this->table }}

    {{-- Ticket Modal Component --}}
    <livewire:ticket-modal />

    {{-- Create Ticket Action Modal --}}
    <x-filament-actions::modals />
</div>
