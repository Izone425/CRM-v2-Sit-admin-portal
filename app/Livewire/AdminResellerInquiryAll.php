<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerInquiry;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\View\View;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Livewire\Attributes\On;

class AdminResellerInquiryAll extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $showDetailModal = false;
    public $selectedInquiry = null;
    public $showTitleModal = false;
    public $showDescriptionModal = false;
    public $showRemarkModal = false;
    public $showRejectReasonModal = false;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-leadowner-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function openDetailModal($inquiryId)
    {
        $this->selectedInquiry = ResellerInquiry::find($inquiryId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedInquiry = null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ResellerInquiry::query())
            ->columns([
                TextColumn::make('formatted_id')
                    ->label('ID')
                    ->searchable(query: function ($query, string $search) {
                        // Match formatted ID pattern: RA{YYMM}-{XXXX}
                        if (preg_match('/^RA(\d{2})(\d{2})-?(\d+)?$/i', trim($search), $matches)) {
                            $year = (int) ('20' . $matches[1]);
                            $month = (int) $matches[2];
                            $query->whereYear('created_at', $year)
                                  ->whereMonth('created_at', $month);

                            if (!empty($matches[3])) {
                                $seq = (int) $matches[3];
                                $monthStart = \Carbon\Carbon::create($year, $month, 1)->startOfMonth();
                                $monthEnd = \Carbon\Carbon::create($year, $month, 1)->endOfMonth();
                                $record = ResellerInquiry::whereBetween('created_at', [$monthStart, $monthEnd])
                                    ->orderBy('id')
                                    ->skip($seq - 1)
                                    ->first();
                                $query->where('id', $record ? $record->id : 0);
                            }
                        } else {
                            $query->where('id', 'like', "%{$search}%");
                        }
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewDetails')
                            ->action(fn (ResellerInquiry $record) => $this->openDetailModal($record->id))
                    ),
                TextColumn::make('reseller_company_name')
                    ->label('Reseller Name')
                    ->searchable(query: function ($query, string $search) {
                        $resellerIds = \Illuminate\Support\Facades\DB::connection('frontenddb')
                            ->table('crm_reseller_link')
                            ->where('reseller_name', 'like', "%{$search}%")
                            ->pluck('reseller_id');
                        $query->whereIn('reseller_id', $resellerIds);
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state)),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => strtoupper($state)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'rejected' => 'danger',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'new' => 'New',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                        'draft' => 'Draft',
                    ]),
                SelectFilter::make('reseller_id')
                    ->label('Reseller Name')
                    ->options(function () {
                        return \Illuminate\Support\Facades\DB::connection('frontenddb')
                            ->table('crm_reseller_link')
                            ->pluck('reseller_name', 'reseller_id')
                            ->map(fn ($name) => strtoupper($name))
                            ->toArray();
                    })
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function render()
    {
        return view('livewire.admin-reseller-inquiry-all');
    }
}
