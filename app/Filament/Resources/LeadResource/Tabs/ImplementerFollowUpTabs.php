<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use App\Filament\Actions\ImplementerActions;
use App\Models\SoftwareHandover;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Card;

class ImplementerFollowUpTabs
{
    protected static function canEditFollowUp($record): bool
    {
        $user = auth()->user();

        // Admin users (role_id = 3) can always edit
        if ($user->role_id == 3) {
            return true;
        }

        // Get the software handover for this lead
        $swHandover = SoftwareHandover::where('lead_id', $record->id)
            ->orderBy('created_at', 'desc')
            ->first();

        // Check if the current user is the assigned implementer
        if ($swHandover && $swHandover->implementer === $user->name) {
            return true;
        }

        // Otherwise, no edit permissions
        return false;
    }

    public static function getSchema(): array
    {
        return [
            Grid::make(1)
                ->schema([
                    Section::make('Implementer Follow Up')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->headerActions([
                            ImplementerActions::addImplementerFollowUpForLead()
                                ->visible(function ($record) {
                                    return self::canEditFollowUp($record);
                                }),
                        ])
                        ->schema([
                            Card::make()
                                ->schema([
                                    View::make('components.implementer-followup-history')
                                        ->extraAttributes(['class' => 'p-0']),
                                ])
                                ->columnSpanFull(),
                        ]),
                ]),
        ];
    }
}
