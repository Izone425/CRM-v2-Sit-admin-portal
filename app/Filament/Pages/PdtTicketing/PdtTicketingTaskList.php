<?php

namespace App\Filament\Pages\PdtTicketing;

use App\Filament\Pages\QcTicketing\QcTicketingTaskList;

class PdtTicketingTaskList extends QcTicketingTaskList
{
    protected static ?string $navigationLabel = 'Task List';
    protected static ?string $title = 'Task List';
    protected static ?string $slug = 'pdt-ticketing/task-list';
}
