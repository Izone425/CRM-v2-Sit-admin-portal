<?php
namespace App\Livewire\AdminFinanceDashboard;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;
use App\Models\FinanceHandover;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Illuminate\Support\Str;

class FinanceHandoverCompletedTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

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

    #[On('refresh-finance-handover-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function getCompletedFinanceHandovers()
    {
        return FinanceHandover::query()
            ->where('status', 'Completed')
            ->orderBy('submitted_at', 'desc')
            ->with(['lead', 'lead.companyDetail', 'reseller', 'creator']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getCompletedFinanceHandovers())
            ->defaultSort('submitted_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption('all')
            ->paginated(['all'])
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->where('created_by', $data['value']);
                        }
                    }),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, FinanceHandover $record) {
                        if (!$state) return 'Unknown';
                        return $record->formatted_handover_id;
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (FinanceHandover $record): View {
                                return view('components.finance-handover-details', [
                                    'record' => $record,
                                ]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->getStateUsing(function (FinanceHandover $record) {
                        if ($record->created_by) {
                            $user = User::find($record->created_by);
                            return $user ? $user->name : 'Unknown';
                        }
                        return 'Unknown';
                    })
                    ->searchable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, FinanceHandover $record) {
                        $displayName = $state ?? ($record->lead?->name ?? 'Unknown Company');
                        $shortened = strtoupper(Str::limit($displayName, 25, '...'));

                        if ($record->lead && $record->lead->id) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);
                            return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                        target="_blank"
                                        title="' . e($displayName) . '"
                                        class="inline-block"
                                        style="color:#338cf0;">
                                        ' . $shortened . '
                                    </a>';
                        }
                        return $shortened;
                    })
                    ->html(),

                TextColumn::make('reseller.company_name')
                    ->label('Reseller')
                    ->sortable()
                    ->searchable()
                    ->default('Unknown'),
            ]);
    }

    public function render()
    {
        return view('livewire.admin-finance-dashboard.finance-handover-completed-table');
    }
}
