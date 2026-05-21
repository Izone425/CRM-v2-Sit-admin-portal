<?php

namespace App\Livewire\AdminFinanceDashboard;

use Livewire\Component;
use App\Models\ResellerHandoverFe;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;

class FeHandoverCompletedTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $lastRefreshTime;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;

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

    public function table(Table $table): Table
    {
        return $table
            ->query(ResellerHandoverFe::query()->where('status', 'completed')->orderBy('completed_at', 'desc'))
            ->columns([
                TextColumn::make('fe_id')
                    ->label('FE ID')
                    ->sortable()
                    ->action(
                        Action::make('view_files')
                            ->label('View Files')
                            ->action(fn (ResellerHandoverFe $record) => $this->openFilesModal($record->id))
                    )
                    ->color('primary')
                    ->weight('bold')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw("CONCAT('FE', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ["%{$search}%"]);
                    }),
                TextColumn::make('autocount_invoice_number')
                    ->label('A/C Invoice')
                    ->searchable(),
                TextColumn::make('ap_document')
                    ->label('AP Document')
                    ->searchable()
                    ->placeholder('-')
                    ->url(fn (ResellerHandoverFe $record): ?string => $record->ap_document_url)
                    ->openUrlInNewTab()
                    ->color(fn (ResellerHandoverFe $record): ?string => $record->ap_document_url ? 'success' : null),
                TextColumn::make('timetec_proforma_invoice')
                    ->label('TT Invoice No')
                    ->searchable()
                    ->placeholder('-')
                    ->url(fn (ResellerHandoverFe $record): ?string => $record->invoice_url)
                    ->openUrlInNewTab()
                    ->color(fn (ResellerHandoverFe $record): ?string => $record->invoice_url ? 'primary' : null)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reseller_company_name')
                    ->label('Reseller Name')
                    ->wrap()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->wrap()
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'completed',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('completed_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function openFilesModal($handoverId)
    {
        $this->selectedHandover = ResellerHandoverFe::find($handoverId);

        if ($this->selectedHandover) {
            $this->handoverFiles = $this->selectedHandover->getCategorizedFilesForModal();
            $this->showFilesModal = true;
        }
    }

    public function closeFilesModal()
    {
        $this->showFilesModal = false;
        $this->selectedHandover = null;
        $this->handoverFiles = [];
    }

    public function render()
    {
        return view('livewire.admin-finance-dashboard.fe-handover-completed-table');
    }
}
