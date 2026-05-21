<?php

namespace App\Filament\Pages\PdtTicketing;

use App\Filament\Pages\QcTicketing\QcTicketingDashboard;

class PdtTicketingDashboard extends QcTicketingDashboard
{
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard';
    protected static ?string $slug = 'pdt-ticketing/dashboard';
    public bool $showBugs = false;
}
