<?php

namespace App\Filament\Pages;

use App\Models\PartnerApplication;
use Filament\Pages\Page;

class HrResellers extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static string $view = 'filament.pages.hr-resellers';
    protected static ?string $navigationLabel = 'Reseller';
    protected static ?string $title = 'Reseller';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'hr-resellers';

    public string $activeTab = 'all';

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getPendingCountProperty(): int
    {
        return PartnerApplication::where('partner_type', 'reseller')
            ->where('status', 'pending')
            ->count();
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
