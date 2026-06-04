<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ResellerAccount extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Reseller Accounts';
    protected static ?string $title = 'Reseller Accounts';
    protected static string $view = 'filament.pages.reseller-account';
    protected static ?int $navigationSort = 70;
}
