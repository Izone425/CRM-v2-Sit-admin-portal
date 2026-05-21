<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ResellerCommissionDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.reseller-commission-dashboard';
    protected static ?string $navigationLabel = 'Reseller Commission';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'reseller-commission-dashboard';
}
