<?php

namespace App\Livewire\SalespersonDashboard;

use App\Models\CompanyDetail;
use App\Models\SalespersonTodoList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\On;

class TodoListPending extends Component implements HasForms, HasTable
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
            ->where('status', 'pending')
            ->orderBy('reminder_date', 'asc');

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
                    ->color('primary'),

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

                TextColumn::make('days_left')
                    ->label('Days Left')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        $daysLeft = $record->days_left;

                        if ($daysLeft < 0) {
                            return abs($daysLeft) . ' days overdue';
                        } elseif ($daysLeft === 0) {
                            return 'Today';
                        } else {
                            return $daysLeft . ' days left';
                        }
                    })
                    ->color(fn ($record) => $record->days_left_color),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Add New Reminder')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        DatePicker::make('reminder_date')
                            ->label('Reminder Date')
                            ->required()
                            ->native(false)
                            ->displayFormat('d F Y')
                            ->minDate(today()),

                        \Filament\Forms\Components\Select::make('lead_id')
                            ->label('Company Name')
                            ->required()
                            ->searchable()
                            ->options(function () {
                                return \App\Models\Lead::where('salesperson', Auth::id())
                                    ->with('companyDetail')
                                    ->get()
                                    ->filter(fn($lead) => $lead->companyDetail && $lead->companyDetail->company_name)
                                    ->sortBy('companyDetail.company_name')
                                    ->pluck('companyDetail.company_name', 'id')
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search) {
                                return \App\Models\Lead::where('salesperson', Auth::id())
                                    ->with('companyDetail')
                                    ->whereHas('companyDetail', function ($query) use ($search) {
                                        $query->where('company_name', 'like', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->pluck('companyDetail.company_name', 'id')
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(fn ($value): ?string => \App\Models\Lead::find($value)?->companyDetail?->company_name),

                        Textarea::make('remark')
                            ->label('Remark')
                            ->required()
                            ->rows(3)
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                            ->maxLength(1000),
                    ])
                    ->action(function (array $data): void {
                        $todoId = SalespersonTodoList::generateTodoId();

                        // Get company name from lead's company detail
                        $lead = \App\Models\Lead::with('companyDetail')->find($data['lead_id']);
                        $companyName = $lead && $lead->companyDetail ? $lead->companyDetail->company_name : '';

                        SalespersonTodoList::create([
                            'todo_id' => $todoId,
                            'salesperson_id' => Auth::id(),
                            'lead_id' => $data['lead_id'],
                            'company_name' => $companyName,
                            'reminder_date' => $data['reminder_date'],
                            'remark' => $data['remark'],
                            'status' => 'pending',
                        ]);

                        Notification::make()
                            ->title('Reminder Created Successfully')
                            ->body("Todo ID: {$todoId}")
                            ->success()
                            ->send();

                        $this->dispatch('refresh-todo-list');
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('View')
                    ->modalHeading(fn ($record) => 'View Reminder Details | ' . $record->todo_id . ' | Created: ' . $record->created_at->format('d M Y'))
                    ->modalContent(fn ($record) => view('components.todo-list-view', ['record' => $record]))
                    ->modalSubmitAction(false)
                    ->hiddenLabel()
                    ->modalCancelActionLabel('Close'),

                Action::make('complete')
                    ->label('Complete Task')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    // ->requiresConfirmation()
                    // ->modalHeading('Complete Task')
                    ->modalWidth('md')
                    ->modalDescription('Are you sure you want to mark this task as completed?')
                    ->action(function (SalespersonTodoList $record): void {
                        $record->update([
                            'status' => 'completed',
                            'completed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Task Completed')
                            ->success()
                            ->send();

                        $this->dispatch('refresh-todo-list');
                    }),
            ])
            ->recordAction('view')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10);
    }

    public function render()
    {
        return view('livewire.salesperson-dashboard.todo-list-pending');
    }
}
