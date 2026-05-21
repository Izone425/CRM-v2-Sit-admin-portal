<?php

namespace App\Livewire\AdminFinanceDashboard;

use App\Models\OffsetPaymentHandover;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class OffsetPaymentNewTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(OffsetPaymentHandover::where('status', 'new'))
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->poll('300s')
            ->columns([
                TextColumn::make('formatted_id')
                    ->label('ID')
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('id', $direction))
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('requestor.name')
                    ->label('Requestor')
                    ->sortable()
                    ->searchable(query: fn ($query, string $search) =>
                        $query->whereHas('requestor', fn ($q) => $q->where('name', 'like', "%{$search}%"))
                    ),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('payment_slip')
                    ->label('Payment Slip')
                    ->alignCenter()
                    ->formatStateUsing(function ($state, OffsetPaymentHandover $record) {
                        $files = $record->payment_slip;
                        if (empty($files)) return '-';

                        $html = '<div style="display: flex; gap: 6px; justify-content: center;">';
                        foreach ($files as $file) {
                            $url = asset('storage/' . $file);
                            $html .= '<a href="' . $url . '" target="_blank" title="' . basename($file) . '" style="color: #2563eb;">';
                            $html .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>';
                            $html .= '</a>';
                        }
                        $html .= '</div>';
                        return new HtmlString($html);
                    }),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->state(function ($record) {
                        if (!$record->created_at) {
                            return null;
                        }
                        $days = (int) $record->created_at->startOfDay()->diffInDays(now()->startOfDay());
                        return $days > 0 ? '-' . $days . ' Days' : null;
                    })
                    ->formatStateUsing(fn ($state) => $state ? '<span style="color:#dc2626;font-weight:bold;">' . e($state) . '</span>' : '')
                    ->html(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('mark_completed')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (OffsetPaymentHandover $record) {
                            $record->update([
                                'status' => 'completed',
                                'completed_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Offset payment marked as completed')
                                ->success()
                                ->send();
                        }),

                    Action::make('mark_rejected')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->modalHeading(false)
                        ->form([
                            Textarea::make('reject_reason')
                                ->label('Reason for Rejection')
                                ->required()
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->rows(3),
                        ])
                        ->action(function (OffsetPaymentHandover $record, array $data) {
                            $record->update([
                                'status' => 'rejected',
                                'reject_reason' => strtoupper($data['reject_reason']),
                            ]);

                            // Send rejection email to requestor
                            $requestor = $record->requestor;
                            if ($requestor && $requestor->email) {
                                $formattedId = $record->formatted_id;
                                $companyName = $record->company_name;

                                try {
                                    Mail::send('emails.offset-payment-rejected', [
                                        'requestorName' => $requestor->name,
                                        'formattedId' => $formattedId,
                                        'companyName' => $companyName,
                                        'invoiceNo' => $record->invoice_no,
                                        'rejectReason' => strtoupper($data['reject_reason']),
                                        'rejectedBy' => auth()->user()->name,
                                    ], function ($mail) use ($requestor, $formattedId, $companyName) {
                                        $mail->to($requestor->email, $requestor->name)
                                            ->subject("{$formattedId} | REJECT | {$companyName}");
                                    });
                                } catch (\Exception $e) {
                                    // Log but don't block the rejection
                                    Log::error('Failed to send offset payment rejection email: ' . $e->getMessage());
                                }
                            }

                            Notification::make()
                                ->title('Offset payment rejected')
                                ->danger()
                                ->send();
                        }),
                ])->button(),
            ]);
    }

    public function render()
    {
        return view('livewire.admin-finance-dashboard.offset-payment-new-table');
    }
}
