<?php

namespace App\Livewire\AdminFinanceDashboard;

use App\Models\OffsetPaymentHandover;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class OffsetPaymentCompletedTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(OffsetPaymentHandover::where('status', 'completed'))
            ->defaultSort('completed_at', 'desc')
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
                    ->sortable(),

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

                TextColumn::make('completed_at')
                    ->label('Completed At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ]);
    }

    public function render()
    {
        return view('livewire.admin-finance-dashboard.offset-payment-completed-table');
    }
}
