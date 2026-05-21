<?php

namespace App\Filament\Pages\PdtTicketing;

use App\Filament\Pages\QcTicketing\QcTicketingSuggestionList;

class PdtTicketingSuggestionList extends QcTicketingSuggestionList
{
    protected static ?string $navigationLabel = 'Suggestion List';
    protected static ?string $title = 'Suggestion List';
    protected static ?string $slug = 'pdt-ticketing/suggestion-list';
}
