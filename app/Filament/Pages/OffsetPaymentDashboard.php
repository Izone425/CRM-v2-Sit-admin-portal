<?php

namespace App\Filament\Pages;

use App\Models\OffsetPaymentHandover;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class OffsetPaymentDashboard extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static string $view = 'filament.pages.offset-payment-dashboard';
    protected static ?string $navigationLabel = 'Offset Payment';
    protected static ?string $title = 'Offset Payment';
    protected static ?int $navigationSort = 11;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'offset-payment-dashboard';

    public function table(Table $table): Table
    {
        return $table
            ->query(OffsetPaymentHandover::query())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50])
            ->poll('300s')
            ->headerActions([
                Action::make('create_handover')
                    ->label('Create')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->form([
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    $el.value = $el.value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->maxLength(255),

                        TextInput::make('invoice_no')
                            ->label('Invoice No')
                            ->required()
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    $el.value = $el.value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->maxLength(13)
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (empty($value)) return;
                                        $exists = OffsetPaymentHandover::where('invoice_no', strtoupper($value))->exists();
                                        if ($exists) {
                                            $fail('This invoice number already exists.');
                                        }
                                    };
                                },
                            ]),

                        FileUpload::make('payment_slip')
                            ->label('Payment Slip')
                            ->disk('public')
                            ->required()
                            ->directory('offset-payment/payment-slips')
                            ->visibility('public')
                            ->multiple()
                            ->maxFiles(5)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->openable(),
                    ])
                    ->modalHeading('Offset Payment')
                    ->modalSubmitActionLabel('Create')
                    ->closeModalByClickingAway(false)
                    ->action(function (array $data): void {
                        OffsetPaymentHandover::create([
                            'requestor_id' => auth()->id(),
                            'company_name' => strtoupper($data['company_name']),
                            'invoice_no' => !empty($data['invoice_no']) ? strtoupper($data['invoice_no']) : null,
                            'payment_slip' => $data['payment_slip'] ?? null,
                            'status' => 'new',
                        ]);

                        Notification::make()
                            ->title('Offset Payment Handover created successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'new' => 'New',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                    ]),

                SelectFilter::make('requestor_id')
                    ->label('Requestor')
                    ->options(fn () => \App\Models\User::whereIn('id', OffsetPaymentHandover::pluck('requestor_id')->unique())->pluck('name', 'id')->toArray())
                    ->searchable(),

                Filter::make('month_year')
                    ->form([
                        Select::make('month')
                            ->label('Month')
                            ->options([
                                1 => 'January', 2 => 'February', 3 => 'March',
                                4 => 'April', 5 => 'May', 6 => 'June',
                                7 => 'July', 8 => 'August', 9 => 'September',
                                10 => 'October', 11 => 'November', 12 => 'December',
                            ]),
                        Select::make('year')
                            ->label('Year')
                            ->options(fn () => collect(range(now()->year, 2025, -1))->mapWithKeys(fn ($y) => [$y => $y])->toArray()),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['month'] ?? null, fn ($q, $month) => $q->whereMonth('created_at', $month))
                            ->when($data['year'] ?? null, fn ($q, $year) => $q->whereYear('created_at', $year));
                    }),
            ])
            ->columns([
                TextColumn::make('formatted_id')
                    ->label('ID')
                    ->sortable(query: fn ($query, string $direction) => $query->orderBy('id', $direction))
                    ->weight('bold')
                    ->color('primary')
                    ->searchable(query: fn ($query, string $search) =>
                        $query->where('id', 'like', "%{$search}%")
                    ),

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
                    ->wrap()
                    ->formatStateUsing(fn (string $state): string => strtoupper($state)),

                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-')
                    ->limit(13),

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

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('reject_reason')
                    ->label('Reject Reason')
                    ->wrap()
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),

                TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->actions([
                Action::make('resubmit')
                    ->label('Resubmit')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (OffsetPaymentHandover $record): bool => $record->status === 'rejected')
                    ->form([
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->required()
                            ->default(fn (OffsetPaymentHandover $record) => $record->company_name)
                            ->extraAlpineAttributes([
                                'x-on:input' => '$el.value = $el.value.toUpperCase();'
                            ]),

                        TextInput::make('invoice_no')
                            ->label('Invoice No')
                            ->default(fn (OffsetPaymentHandover $record) => $record->invoice_no)
                            ->extraAlpineAttributes([
                                'x-on:input' => '$el.value = $el.value.toUpperCase();'
                            ]),

                        FileUpload::make('payment_slip')
                            ->label('Payment Slip')
                            ->disk('public')
                            ->directory('offset-payment/payment-slips')
                            ->visibility('public')
                            ->multiple()
                            ->maxFiles(5)
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->openable()
                            ->default(fn (OffsetPaymentHandover $record) => $record->payment_slip ?? []),
                    ])
                    ->modalHeading('Resubmit Offset Payment')
                    ->action(function (OffsetPaymentHandover $record, array $data) {
                        $record->update([
                            'status' => 'new',
                            'reject_reason' => null,
                            'company_name' => strtoupper($data['company_name']),
                            'invoice_no' => !empty($data['invoice_no']) ? strtoupper($data['invoice_no']) : $record->invoice_no,
                            'payment_slip' => $data['payment_slip'] ?? $record->payment_slip,
                        ]);

                        Notification::make()
                            ->title('Offset payment resubmitted')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
