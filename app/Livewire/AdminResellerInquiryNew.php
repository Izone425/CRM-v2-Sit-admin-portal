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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ResellerInquiryStatusUpdate;
use Illuminate\View\View;
use Livewire\Attributes\On;

class AdminResellerInquiryNew extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;
    public $lastRefreshTime;

    public $showDetailModal = false;
    public $selectedInquiry = null;
    public $showTitleModal = false;
    public $showDescriptionModal = false;
    public $showRemarkModal = false;
    public $showRejectReasonModal = false;

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
            ->query(ResellerInquiry::query()->where('status', 'new'))
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
                TextColumn::make('created_at')
                    ->label('Submitted At')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->form([
                        Textarea::make('admin_remark')
                            ->label('Admin Remark')
                            ->rows(4)
                            ->required(),
                        FileUpload::make('admin_attachment')
                            ->label('Attachment')
                            ->disk('public')
                            ->multiple()
                            ->directory('inquiry-attachments/admin')
                            ->acceptedFileTypes(['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'image/jpeg', 'image/png'])
                            ->maxSize(10240),
                    ])
                    ->action(function (ResellerInquiry $record, array $data): void {
                        $record->update([
                            'status' => 'completed',
                            'admin_remark' => !empty($data['admin_remark']) ? strtoupper($data['admin_remark']) : null,
                            'admin_attachment_path' => $data['admin_attachment'] ?? null,
                            'completed_at' => now(),
                        ]);

                        try {
                            Mail::send(new ResellerInquiryStatusUpdate($record));
                        } catch (\Exception $e) {
                            Log::error('Failed to send reseller inquiry status email', [
                                'error' => $e->getMessage(),
                                'inquiry_id' => $record->id,
                            ]);
                        }

                        Notification::make()
                            ->title('Inquiry completed successfully!')
                            ->success()
                            ->send();
                    })
                    ->modalHeading(false)
                    ->modalSubmitActionLabel('Complete Task')
                    ->modalWidth('2xl'),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('reject_reason')
                            ->label('Reject Reason')
                            ->required()
                            ->rows(4)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->maxLength(1000),
                        FileUpload::make('reject_attachment')
                            ->label('Attachment')
                            ->disk('public')
                            ->multiple()
                            ->directory('inquiry-attachments/reject')
                            ->acceptedFileTypes(['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'image/jpeg', 'image/png'])
                            ->maxSize(10240),
                    ])
                    ->action(function (ResellerInquiry $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'reject_reason' => strtoupper($data['reject_reason']),
                            'reject_attachment_path' => $data['reject_attachment'] ?? null,
                            'rejected_at' => now(),
                        ]);

                        try {
                            Mail::send(new ResellerInquiryStatusUpdate($record));
                        } catch (\Exception $e) {
                            Log::error('Failed to send reseller inquiry status email', [
                                'error' => $e->getMessage(),
                                'inquiry_id' => $record->id,
                            ]);
                        }

                        Notification::make()
                            ->title('Inquiry rejected successfully!')
                            ->success()
                            ->send();
                    })
                    ->modalHeading(false)
                    ->modalSubmitActionLabel('Reject')
                    ->modalWidth('2xl'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyState(fn () => view('components.empty-state-question'));
    }

    public function render()
    {
        return view('livewire.admin-reseller-inquiry-new');
    }
}
