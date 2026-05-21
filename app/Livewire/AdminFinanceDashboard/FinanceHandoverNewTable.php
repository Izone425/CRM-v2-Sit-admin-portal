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
use Illuminate\Support\Facades\Mail;

class FinanceHandoverNewTable extends Component implements HasForms, HasTable
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

    public function getNewFinanceHandovers()
    {
        return FinanceHandover::query()
            ->where('status', 'New')
            ->orderBy('submitted_at', 'desc')
            ->with(['lead', 'lead.companyDetail', 'reseller', 'creator']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewFinanceHandovers())
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

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->getStateUsing(function (FinanceHandover $record) {
                        if (!$record->created_at) {
                            return null;
                        }
                        $days = (int) $record->created_at->startOfDay()->diffInDays(now()->startOfDay());
                        return $days > 0 ? '-' . $days . ' Days' : null;
                    })
                    ->formatStateUsing(fn ($state) => $state ? new HtmlString('<span style="color:#dc2626;font-weight:bold;">' . e($state) . '</span>') : '')
                    ->html(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (FinanceHandover $record): View {
                            return view('components.finance-handover-details', [
                                'record' => $record,
                            ]);
                        }),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(): bool => auth()->user()->role_id === 3)
                        ->requiresConfirmation()
                        ->action(function (FinanceHandover $record): void {
                            $record->update([
                                'status' => 'Pending Payment',
                            ]);

                            // Send approval email
                            $salesperson = User::find($record->created_by);
                            $year = $record->created_at ? $record->created_at->format('y') : now()->format('y');
                            $formattedId = 'FN_' . $year . str_pad($record->id, 4, '0', STR_PAD_LEFT);
                            $companyName = $record->lead?->companyDetail?->company_name ?? $record->lead?->name ?? 'N/A';
                            $resellerName = $record->reseller?->company_name ?? 'N/A';

                            $ensureArray = function($value) {
                                if (is_null($value)) return [];
                                if (is_array($value)) return $value;
                                if (is_string($value)) {
                                    $decoded = json_decode($value, true);
                                    return is_array($decoded) ? $decoded : [];
                                }
                                return [];
                            };

                            $invoiceResellerFiles = collect($ensureArray($record->invoice_by_reseller))->map(fn($file) => [
                                'name' => basename($file),
                                'url' => asset('storage/' . $file),
                            ])->toArray();

                            $invoiceCustomerFiles = collect($ensureArray($record->invoice_by_customer))->map(fn($file) => [
                                'name' => basename($file),
                                'url' => asset('storage/' . $file),
                            ])->toArray();

                            $recipients = ['ap.ttcl@timeteccloud.com', 'faiz@timeteccloud.com'];
                            if ($salesperson && $salesperson->email) {
                                $recipients[] = $salesperson->email;
                            }

                            $subject = 'FINANCE HANDOVER | ' . $formattedId . ' | ' . $resellerName;

                            Mail::send('emails.finance-handover-approved', [
                                'handoverId' => $formattedId,
                                'salesperson' => $salesperson?->name ?? 'N/A',
                                'companyName' => $companyName,
                                'resellerName' => $resellerName,
                                'invoiceResellerFiles' => $invoiceResellerFiles,
                                'invoiceCustomerFiles' => $invoiceCustomerFiles,
                            ], function ($message) use ($recipients, $subject) {
                                $message->to($recipients)
                                    ->subject($subject);
                            });

                            Notification::make()
                                ->title('Finance handover approved - pending payment')
                                ->success()
                                ->send();
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn(): bool => auth()->user()->role_id === 3)
                        ->form([
                            \Filament\Forms\Components\Textarea::make('reject_reason')
                                ->label('Reason for Rejection')
                                ->required()
                                ->maxLength(500)
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
                        ])
                        ->action(function (FinanceHandover $record, array $data): void {
                            $record->update([
                                'status' => 'Rejected',
                                'remarks' => $data['reject_reason'],
                            ]);

                            // Send rejection email to the submitter
                            $salesperson = User::find($record->created_by);
                            if ($salesperson && $salesperson->email) {
                                $year = $record->created_at ? $record->created_at->format('y') : now()->format('y');
                                $formattedId = 'FN_' . $year . str_pad($record->id, 4, '0', STR_PAD_LEFT);
                                $companyName = $record->lead?->companyDetail?->company_name ?? $record->lead?->name ?? 'N/A';
                                $resellerName = $record->reseller?->company_name ?? 'N/A';

                                Mail::send('emails.finance-handover-rejected', [
                                    'salesperson' => $salesperson,
                                    'companyName' => $companyName,
                                    'resellerName' => $resellerName,
                                    'handoverId' => $formattedId,
                                    'rejectReason' => $data['reject_reason'],
                                ], function ($message) use ($salesperson, $formattedId, $resellerName) {
                                    $message->to($salesperson->email)
                                        ->subject('FINANCE HANDOVER REJECTED | ' . $formattedId . ' | ' . $resellerName);
                                });
                            }

                            Notification::make()
                                ->title('Finance handover rejected')
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
        return view('livewire.admin-finance-dashboard.finance-handover-new-table');
    }
}
