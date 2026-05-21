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
use Filament\Tables\Actions\BulkAction;
use Filament\Notifications\Notification;
use App\Models\FinanceHandover;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Filament\Forms\Components\FileUpload;

class FinanceHandoverPendingPaymentTable extends Component implements HasForms, HasTable
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

    public function getPendingPaymentFinanceHandovers()
    {
        return FinanceHandover::query()
            ->where('status', 'Pending Payment')
            ->orderBy('submitted_at', 'desc')
            ->with(['lead', 'lead.companyDetail', 'reseller', 'creator']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getPendingPaymentFinanceHandovers())
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

                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->getStateUsing(function (FinanceHandover $record) {
                        if (!$record->updated_at) {
                            return null;
                        }
                        $days = (int) $record->updated_at->startOfDay()->diffInDays(now()->startOfDay());
                        return $days > 0 ? '-' . $days . ' Days' : null;
                    })
                    ->formatStateUsing(fn ($state) => $state ? new HtmlString('<span style="color:#dc2626;font-weight:bold;">' . e($state) . '</span>') : '')
                    ->html(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('download_reseller_invoice')
                        ->label('Download Reseller Invoice')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->visible(fn (FinanceHandover $record): bool => !empty($this->ensureArrayHelper($record->invoice_by_reseller)))
                        ->url(fn (FinanceHandover $record): string => route('finance-handover.download-reseller-invoice', $record->id))
                        ->openUrlInNewTab(),

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

                    Action::make('complete')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(): bool => in_array(auth()->user()->role_id, [3, 10]))
                        ->form([
                            FileUpload::make('payment_slip')
                                ->label('Payment Slip')
                                ->disk('public')
                                ->directory('finance_handovers/payment_slip')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->openable()
                                ->downloadable()
                                ->required(),
                        ])
                        ->modalHeading('Complete Finance Handover')
                        ->modalSubmitActionLabel('Complete')
                        ->modalWidth('lg')
                        ->action(function (FinanceHandover $record, array $data): void {
                            $updateData = [
                                'status' => 'Completed',
                                'completed_at' => now(),
                            ];

                            if (isset($data['payment_slip']) && is_array($data['payment_slip'])) {
                                $existing = $record->payment_slip ?? [];
                                $merged = array_merge($existing, $data['payment_slip']);
                                $updateData['payment_slip'] = array_values($merged);
                            }

                            $record->update($updateData);

                            // Send completed email to salesperson + reseller PIC email
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

                            $paymentSlipFiles = collect($ensureArray($record->payment_slip))->map(fn($file) => [
                                'name' => basename($file),
                                'url' => asset('storage/' . $file),
                            ])->toArray();

                            $recipients = [];
                            if ($salesperson && $salesperson->email) {
                                $recipients[] = $salesperson->email;
                            }
                            if ($record->pic_email) {
                                $recipients[] = $record->pic_email;
                            }

                            if (!empty($recipients)) {
                                $subject = 'PAID | FINANCE HANDOVER | ' . $formattedId . ' | ' . $resellerName;

                                Mail::send('emails.finance-handover-completed', [
                                    'handoverId' => $formattedId,
                                    'salesperson' => $salesperson?->name ?? 'N/A',
                                    'companyName' => $companyName,
                                    'resellerName' => $resellerName,
                                    'invoiceResellerFiles' => $invoiceResellerFiles,
                                    'paymentSlipFiles' => $paymentSlipFiles,
                                ], function ($message) use ($recipients, $subject) {
                                    $message->to($recipients)
                                        ->subject($subject);
                                });
                            }

                            Notification::make()
                                ->title('Finance handover marked as completed')
                                ->success()
                                ->send();
                        }),
                ])->button()
                ->label('Actions')
                ->color('primary'),
            ])
            ->bulkActions([
                BulkAction::make('batchDownloadResellerInvoice')
                    ->label('Batch Download Reseller Invoice')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records): void {
                        $urls = [];
                        foreach ($records as $record) {
                            $files = $this->ensureArrayHelper($record->invoice_by_reseller);
                            foreach ($files as $filePath) {
                                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($filePath)) {
                                    $urls[] = asset('storage/' . $filePath);
                                }
                            }
                        }

                        if (empty($urls)) {
                            Notification::make()->title('No reseller invoice files found.')->warning()->send();
                            return;
                        }

                        $this->js('
                            const urls = ' . json_encode($urls) . ';
                            urls.forEach((url, i) => {
                                setTimeout(() => {
                                    const a = document.createElement("a");
                                    a.href = url;
                                    a.download = "";
                                    a.target = "_blank";
                                    document.body.appendChild(a);
                                    a.click();
                                    document.body.removeChild(a);
                                }, i * 500);
                            });
                        ');
                    }),

                BulkAction::make('batchComplete')
                    ->label('Batch Mark as Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(): bool => in_array(auth()->user()->role_id, [3, 10]))
                    ->form([
                        FileUpload::make('payment_slip')
                            ->label('Payment Slip')
                            ->disk('public')
                            ->directory('finance_handovers/payment_slip')
                            ->visibility('public')
                            ->multiple()
                            ->maxFiles(5)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->openable()
                            ->downloadable()
                            ->required(),
                    ])
                    ->modalHeading('Batch Complete Finance Handovers')
                    ->modalDescription(fn(Collection $records) => 'You are about to mark ' . $records->count() . ' finance handover(s) as completed. The uploaded payment slip(s) will be stored for each record.')
                    ->modalSubmitActionLabel('Complete All')
                    ->modalWidth('lg')
                    ->deselectRecordsAfterCompletion()
                    ->action(function (Collection $records, array $data): void {
                        $completedCount = 0;

                        foreach ($records as $record) {
                            $updateData = [
                                'status' => 'Completed',
                                'completed_at' => now(),
                            ];

                            // Store the same uploaded files for each record
                            if (isset($data['payment_slip']) && is_array($data['payment_slip'])) {
                                $existing = $record->payment_slip ?? [];
                                $merged = array_merge($existing, $data['payment_slip']);
                                $updateData['payment_slip'] = array_values($merged);
                            }

                            $record->update($updateData);
                            $completedCount++;

                            // Send completed email for each record
                            try {
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

                                $paymentSlipFiles = collect($ensureArray($record->fresh()->payment_slip))->map(fn($file) => [
                                    'name' => basename($file),
                                    'url' => asset('storage/' . $file),
                                ])->toArray();

                                $recipients = [];
                                if ($salesperson && $salesperson->email) {
                                    $recipients[] = $salesperson->email;
                                }
                                if ($record->pic_email) {
                                    $recipients[] = $record->pic_email;
                                }

                                if (!empty($recipients)) {
                                    $subject = 'PAID | FINANCE HANDOVER | ' . $formattedId . ' | ' . $resellerName;

                                    Mail::send('emails.finance-handover-completed', [
                                        'handoverId' => $formattedId,
                                        'salesperson' => $salesperson?->name ?? 'N/A',
                                        'companyName' => $companyName,
                                        'resellerName' => $resellerName,
                                        'invoiceResellerFiles' => $invoiceResellerFiles,
                                        'paymentSlipFiles' => $paymentSlipFiles,
                                    ], function ($message) use ($recipients, $subject) {
                                        $message->to($recipients)
                                            ->subject($subject);
                                    });
                                }
                            } catch (\Exception $e) {
                                Log::error('Batch complete email failed for FH #' . $record->id . ': ' . $e->getMessage());
                            }
                        }

                        Notification::make()
                            ->title($completedCount . ' finance handover(s) marked as completed')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    private function ensureArrayHelper($value): array
    {
        if (is_null($value)) return [];
        if (is_array($value)) return $value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    public function render()
    {
        return view('livewire.admin-finance-dashboard.finance-handover-pending-payment-table');
    }
}
