<?php

namespace App\Livewire\LeadownerDashboard;

use App\Classes\Encryptor;
use App\Filament\Actions\LeadActions;
use App\Models\Lead;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class HrChecklistCallPendingTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

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

    #[On('refresh-leadowner-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        if ($selectedUser) {
            $this->selectedUser = $selectedUser;
            session(['selectedUser' => $selectedUser]); // Store selected user
        } else {
            // Reset to "Your Own Dashboard" (value = 7)
            $this->selectedUser = 7;
            session(['selectedUser' => 7]);
        }

        $this->resetTable(); // Refresh the table
    }

    public function getCallPendingQuery()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        $query = Lead::query()
            ->whereIn('lead_code', ['HR Checklist', 'HR Checklist (CN)', 'HR Checklist (BM)'])
            ->where('done_call', 0)
            ->where('call_attempt', '!=', 0)
            ->where('categories', '!=', 'Inactive')
            ->selectRaw('*, DATEDIFF(NOW(), created_at) as pending_days')
            ->whereNull('salesperson');

        // Handle filtering by user or group
        if ($this->selectedUser === 'all-lead-owners') {
            $leadOwnerNames = User::where('role_id', 1)->pluck('name');
            $query->whereIn('lead_owner', $leadOwnerNames);
        } elseif (is_numeric($this->selectedUser)) {
            $user = User::find($this->selectedUser);

            if ($user) {
                $query->where('lead_owner', $user->name);
            }
        } else {
            // fallback to current user
            $query->where('lead_owner', auth()->user()->name);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getCallPendingQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            // ->heading(fn () => 'Prospect Reminder (Today) - ' . $this->getCallPendingQuery()->count() . ' Records') // Display count
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('company_size_label') // Use the correct filter key
                    ->label('')
                    ->options([
                        'Small' => 'Small',
                        'Medium' => 'Medium',
                        'Large' => 'Large',
                        'Enterprise' => 'Enterprise',
                    ])
                    ->multiple() // Enables multi-selection
                    ->placeholder('Select Company Size')
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
                        if (!empty($data['values'])) { // 'values' stores multiple selections
                            $sizeMap = [
                                'Small' => '1-24',
                                'Small' => '20-24',
                                'Medium' => '25-99',
                                'Large' => '100-500',
                                'Enterprise' => '501 and Above',
                            ];

                            // Convert selected sizes to DB values
                            $dbValues = collect($data['values'])->map(fn ($size) => $sizeMap[$size] ?? null)->filter();

                            if ($dbValues->isNotEmpty()) {
                                $query->whereHas('companyDetail', function ($query) use ($dbValues) {
                                    $query->whereIn('company_size', $dbValues);
                                });
                            }
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        return !empty($data['values'])
                            ? 'Company Size: ' . implode(', ', $data['values'])
                            : null;
                    }),
                SelectFilter::make('lead_owner')
                    ->label('')
                    ->multiple()
                    ->options(\App\Models\User::where('role_id', 1)->pluck('name', 'name')->toArray())
                    ->placeholder('Select Lead Owner')
                    ->hidden(fn () => auth()->user()->role_id !== 3),
            ])
            ->columns([
                TextColumn::make('companyDetail.company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),
                TextColumn::make('lead_code')
                    ->label('Lead Source')
                    ->sortable(),
                TextColumn::make('company_size_label')
                    ->label('Company Size')
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("
                            CASE
                                WHEN company_size = '1-24' THEN 1
                                WHEN company_size = '25-99' THEN 2
                                WHEN company_size = '100-500' THEN 3
                                WHEN company_size = '501 and Above' THEN 4
                                ELSE 5
                            END $direction
                        ");
                    }),
                // TextColumn::make('created_at')
                //     ->label('Created Time')
                //     ->sortable()
                //     ->dateTime('d M Y, h:i A')
                //     ->formatStateUsing(fn ($state) => Carbon::parse($state)->setTimezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A')),
                TextColumn::make('call_attempt')
                    ->label('Call Attempt')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->actions([
                ActionGroup::make([
                    LeadActions::getViewAction(),
                    LeadActions::getMarkAsGenuineAction(),
                    LeadActions::getPendingCallAction(),
                    LeadActions::getMarkAsJunkAction(),
                ])
                ->button()
                ->color(fn (Lead $record) => $record->follow_up_needed ? 'warning' : 'danger'),
            ]);
    }

    public function render()
    {
        return view('livewire.leadowner_dashboard.hr-checklist-call-pending-table');
    }
}
