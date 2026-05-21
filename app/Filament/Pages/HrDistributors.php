<?php

namespace App\Filament\Pages;

use App\Models\PartnerApplication;
use Filament\Pages\Page;

class HrDistributors extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static string $view = 'filament.pages.hr-distributors';
    protected static ?string $navigationLabel = 'Distributor';
    protected static ?string $title = 'Distributor';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'hr-distributors';

    public string $activeTab = 'all';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getPendingCountProperty(): int
    {
        return PartnerApplication::where('partner_type', 'distributor')
            ->where('status', 'pending')
            ->count();
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
