<?php
namespace App\Livewire\SalespersonDashboard;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use App\Models\HRDFHandover;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class HrdfRejectedTableSalesperson extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $lastRefreshTime;
    public $selectedUser;

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

    #[On('refresh-hrdf-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getNewHrdfHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = HRDFHandover::query()
            ->whereIn('status', ['Rejected','Draft'])
            ->orderBy('submitted_at', 'desc')
            ->with(['lead', 'lead.companyDetail', 'creator']);

        if ($this->selectedUser === 'all-salespersons') {
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();

            if ($userExists) {
                $selectedUser = $this->selectedUser;
                $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                    $leadQuery->where('salesperson', $selectedUser);
                });
            } else {
                $query->whereHas('lead', function ($leadQuery) {
                    $leadQuery->where('salesperson', auth()->id());
                });
            }
        } else {
            if (auth()->user()->role_id === 2) {
                $userId = auth()->id();
                $query->whereHas('lead', function ($leadQuery) use ($userId) {
                    $leadQuery->where('salesperson', $userId);
                });
            }
        }

        return $query;
    }

    public function getEditForm(): array
    {
        return [
            Grid::make(3)
                ->schema([
                    Select::make('hrdf_grant_id')
                        ->label('Select HRDF Grant')
                        ->searchable()
                        ->preload(false)
                        ->live()
                        ->placeholder('Search HRDF Grant ID')
                        ->options(function (?HRDFHandover $record = null) {
                            if ($record && $record->hrdf_grant_id) {
                                $claim = \App\Models\HrdfClaim::where('hrdf_grant_id', $record->hrdf_grant_id)->first();
                                if ($claim) {
                                    return [
                                        $claim->hrdf_grant_id => "{$claim->hrdf_grant_id} - {$claim->company_name}"
                                    ];
                                }
                            }
                            return [];
                        })
                        ->getSearchResultsUsing(function (string $search, ?HRDFHandover $record = null) {
                            if (empty(trim($search))) {
                                return [];
                            }

                            $results = \App\Models\HrdfClaim::where(function ($query) use ($search) {
                                $query->where('hrdf_grant_id', 'like', "%{$search}%")
                                    ->orWhere('company_name', 'like', "%{$search}%");
                            })
                            ->whereIn('claim_status', ['PENDING']);

                            if ($record && $record->hrdf_grant_id) {
                                $results->where(function ($query) use ($record) {
                                    $query->whereDoesntHave('hrdfHandover')
                                        ->orWhere('hrdf_grant_id', $record->hrdf_grant_id);
                                });
                            } else {
                                $results->whereDoesntHave('hrdfHandover');
                            }

                            return $results->limit(20)
                                ->get()
                                ->mapWithKeys(function ($claim) {
                                    return [
                                        $claim->hrdf_grant_id => "{$claim->hrdf_grant_id} - {$claim->company_name}"
                                    ];
                                })
                                ->toArray();
                        })
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                $claim = \App\Models\HrdfClaim::where('hrdf_grant_id', $state)->first();
                                if ($claim) {
                                    $set('autocount_invoice_number', $claim->invoice_number);
                                }
                            }
                        })
                        ->default(fn (?HRDFHandover $record = null) => $record?->hrdf_grant_id ?? null)
                        ->required(),

                    TextInput::make('autocount_invoice_number')
                        ->label('AutoCount Invoice Number')
                        ->required()
                        ->maxLength(13)
                        ->extraAlpineAttributes([
                            'x-on:input' => '
                                const start = $el.selectionStart;
                                const end = $el.selectionEnd;
                                const value = $el.value;
                                $el.value = value.toUpperCase();
                                $el.setSelectionRange(start, end);
                            '
                        ])
                        ->default(fn (?HRDFHandover $record = null) => $record?->autocount_invoice_number ?? null)
                        ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                    Select::make('subsidiary_id')
                        ->label('Subsidiary Company')
                        ->placeholder('Select subsidiary company')
                        ->options(function (?HRDFHandover $record = null) {
                            if (!$record || !$record->lead_id) {
                                return [];
                            }
                            return \App\Models\Subsidiary::where('lead_id', $record->lead_id)
                                ->pluck('company_name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->default(fn (?HRDFHandover $record = null) => $record?->subsidiary_id ?? null),
                ]),
            Grid::make(3)
                ->schema([
                    FileUpload::make('jd14_form_files')
                        ->label('JD14 Form + 3 Days Attendance Logs')
                        ->disk('public')
                        ->directory('handovers/hrdf/jd14_forms')
                        ->visibility('public')
                        ->multiple()
                        ->maxFiles(4)
                        ->required()
                        ->acceptedFileTypes(['application/pdf'])
                        ->helperText('(Maximum 4 PDF files)')
                        ->openable()
                        ->downloadable()
                        ->default(function (?HRDFHandover $record = null) {
                            if (!$record || !$record->jd14_form_files) return [];
                            if (is_string($record->jd14_form_files)) return json_decode($record->jd14_form_files, true) ?? [];
                            return is_array($record->jd14_form_files) ? $record->jd14_form_files : [];
                        }),

                    FileUpload::make('autocount_invoice_file')
                        ->label('AutoCount Invoice')
                        ->disk('public')
                        ->directory('handovers/hrdf/autocount_invoices')
                        ->visibility('public')
                        ->multiple()
                        ->maxFiles(1)
                        ->required()
                        ->acceptedFileTypes(['application/pdf'])
                        ->helperText('(Maximum 1 PDF file)')
                        ->openable()
                        ->downloadable()
                        ->default(function (?HRDFHandover $record = null) {
                            if (!$record || !$record->autocount_invoice_file) return [];
                            if (is_string($record->autocount_invoice_file)) return json_decode($record->autocount_invoice_file, true) ?? [];
                            return is_array($record->autocount_invoice_file) ? $record->autocount_invoice_file : [];
                        }),

                    FileUpload::make('hrdf_grant_approval_file')
                        ->label('HRDF Grant Approval Letter')
                        ->disk('public')
                        ->directory('handovers/hrdf/grant_approvals')
                        ->visibility('public')
                        ->multiple()
                        ->maxFiles(1)
                        ->required()
                        ->acceptedFileTypes(['application/pdf'])
                        ->helperText('(Maximum 1 PDF file)')
                        ->openable()
                        ->downloadable()
                        ->default(function (?HRDFHandover $record = null) {
                            if (!$record || !$record->hrdf_grant_approval_file) return [];
                            if (is_string($record->hrdf_grant_approval_file)) return json_decode($record->hrdf_grant_approval_file, true) ?? [];
                            return is_array($record->hrdf_grant_approval_file) ? $record->hrdf_grant_approval_file : [];
                        }),
                ]),
            Grid::make(1)
                ->schema([
                    Textarea::make('salesperson_remark')
                        ->label('SalesPerson Remark')
                        ->rows(2)
                        ->maxLength(1000)
                        ->default(fn (?HRDFHandover $record = null) => $record?->salesperson_remark ?? null)
                        ->extraAlpineAttributes([
                            'x-on:input' => '
                                const start = $el.selectionStart;
                                const end = $el.selectionEnd;
                                const value = $el.value;
                                $el.value = value.toUpperCase();
                                $el.setSelectionRange(start, end);
                            '
                        ])
                        ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                ])->columnSpan(1),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewHrdfHandovers())
            ->defaultSort('submitted_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15)
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple()
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $salespersonNames = $data['value'];
                            $salespersonIds = User::whereIn('name', $salespersonNames)
                                ->where('role_id', '2')
                                ->pluck('id')
                                ->toArray();

                            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                                $leadQuery->whereIn('salesperson', $salespersonIds);
                            });
                        }
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HRDFHandover $record) {
                        if (!$state) return 'Unknown';
                        return $record->formatted_handover_id;
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HRDFHandover $record): View {
                                return view('components.hrdf-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('submitted_at')
                    ->label('Date Submitted')
                    ->dateTime('d M Y, g:ia')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, HRDFHandover $record) {
                        $displayName = $state ?? 'N/A';
                        if (!empty($record->subsidiary_id)) {
                            $subsidiary = \App\Models\Subsidiary::find($record->subsidiary_id);
                            if ($subsidiary && !empty($subsidiary->company_name)) {
                                $displayName = $subsidiary->company_name . ' (Subsidiary)';
                            }
                        }
                        $shortened = strtoupper(Str::limit($displayName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);
                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($displayName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('hrdf_grant_id')
                    ->label('HRDF Grant ID')
                    ->searchable()
                    ->weight('bold')
                    ->color('success'),

                TextColumn::make('lead.salesperson')
                    ->label('Salesperson')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'No Salesperson';
                        $user = User::find($state);
                        return $user ? $user->name : 'Unknown';
                    })
                    ->searchable()
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Completed' => new HtmlString('<span style="color: green;">Completed</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HRDFHandover $record): View {
                            return view('components.hrdf-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('view_reason')
                        ->label('View Reason')
                        ->visible(fn (HRDFHandover $record): bool => $record->status === 'Rejected')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Rejection Reason')
                        ->modalContent(fn (HRDFHandover $record) => view('components.view-reason', [
                            'reason' => $record->reject_reason,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth('3xl')
                        ->color('warning'),

                    Action::make('convert_to_draft')
                        ->label('Convert to Draft')
                        ->icon('heroicon-o-document')
                        ->color('warning')
                        ->visible(fn (HRDFHandover $record): bool => $record->status === 'Rejected')
                        ->action(function (HRDFHandover $record): void {
                            $record->update([
                                'status' => 'Draft'
                            ]);

                            Notification::make()
                                ->title('HRDF handover converted to draft')
                                ->success()
                                ->send();
                        }),

                    Action::make('edit_hrdf_handover')
                        ->modalHeading(fn (HRDFHandover $record): string => "Edit HRDF Handover {$record->formatted_handover_id}")
                        ->label('Edit HRDF Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Submit')
                        ->visible(fn (HRDFHandover $record): bool => $record->status === 'Draft')
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->slideOver()
                        ->form($this->getEditForm())
                        ->action(function (HRDFHandover $record, array $data): void {
                            foreach (['jd14_form_files', 'autocount_invoice_file', 'hrdf_grant_approval_file'] as $field) {
                                if (isset($data[$field]) && is_array($data[$field])) {
                                    $data[$field] = json_encode($data[$field]);
                                }
                            }

                            $data['status'] = 'New';
                            $data['submitted_at'] = now();

                            $record->update($data);

                            Notification::make()
                                ->title('HRDF handover updated successfully')
                                ->success()
                                ->send();
                        }),
                ])->button()
                ->label('Actions')
                ->color('primary'),
            ]);
    }

    public function render()
    {
        return view('livewire.salesperson-dashboard.hrdf-rejected-table-salesperson');
    }
}
