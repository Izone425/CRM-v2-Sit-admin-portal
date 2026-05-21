<?php

namespace App\Livewire\SalespersonDashboard;

use App\Models\CompanyDetail;
use App\Models\SalespersonTodoList;
use App\Models\User;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\On;

class TodoListCompleted extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $selectedUser;
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

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]);
        $this->resetTable();
    }

    public function getTodoQuery()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = SalespersonTodoList::query()
            ->where('status', 'completed')
            ->orderBy('completed_at', 'desc');

        // Salesperson filter logic (similar to PrTodaySalespersonTable)
        if ($this->selectedUser === 'all-salespersons') {
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereIn('salesperson_id', $salespersonIds);
        } elseif (is_numeric($this->selectedUser)) {
            $query->where('salesperson_id', $this->selectedUser);
        } else {
            $query->where('salesperson_id', auth()->id());
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTodoQuery())
            ->columns([
                TextColumn::make('todo_id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('salesperson.name')
                    ->label('Salesperson')
                    ->searchable()
                    ->sortable()
                    ->visible(fn () => auth()->user()->role_id == 3),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            $shortened = strtoupper(Str::limit($company->company_name, 20, '...'));
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $company->company_name . '
                                </a>');
                        }

                        $shortened = strtoupper(Str::limit($state, 20, '...'));
                        return "<span title='{$state}'>{$state}</span>";
                    })
                    ->html(),

                TextColumn::make('reminder_date')
                    ->label('Reminder Date')
                    ->date('d F Y')
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->badge()
                    ->color('success'),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->hiddenLabel()
                    ->modalHeading('View Reminder Details')
                    ->modalContent(fn ($record) => view('components.todo-list-view', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->recordAction('view')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10);
    }

    public function render()
    {
        return view('livewire.salesperson-dashboard.todo-list-completed');
    }
}
