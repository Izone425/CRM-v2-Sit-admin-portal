<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrSalesInvoice;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Livewire\Component;

abstract class HrRenewalBaseTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    abstract protected function getPaymentFilter(): ?string;

    protected function getBaseQuery()
    {
        $today = now()->format('Y-m-d');
        $threeMonths = now()->addMonths(3)->format('Y-m-d');

        $query = HrSalesInvoice::query()
            ->whereHas('softwareHandover', function ($q) use ($today, $threeMonths) {
                $q->whereHas('hrLicenses', function ($lq) use ($today, $threeMonths) {
                    $lq->where('type', 'PAID')
                       ->where('status', 'Enabled')
                       ->whereBetween('end_date', [$today, $threeMonths]);
                });
            });

        $filter = $this->getPaymentFilter();
        if ($filter !== null) {
            $query->where('payment_status', $filter);
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getBaseQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->poll('300s')
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(fn($state) => strtoupper($state ?? '')),

                TextColumn::make('handover_id')
                    ->label('SW ID')
                    ->sortable(),

                TextColumn::make('currency')
                    ->label('Currency')
                    ->badge()
                    ->color(fn($state) => $state === 'MYR' ? 'info' : 'warning'),

                TextColumn::make('invoice_amount')
                    ->label('Amount')
                    ->sortable()
                    ->money(fn($record) => $record->currency ?? 'MYR')
                    ->alignEnd(),

                TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state ?? 'unknown'))
                    ->color(fn($state) => match($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        default => 'warning',
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => ucfirst($state ?? 'unknown'))
                    ->color(fn($state) => match($state) {
                        'paid' => 'success',
                        'pending' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y')
                    ->sortable(),
            ]);
    }
}
