<?php

namespace App\Livewire\SalespersonDashboard;

use App\Models\CompanyDetail;
use App\Models\EInvoiceHandover;
use App\Models\Subsidiary;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class EInvoiceHandoverNew extends Component implements HasForms, HasTable
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

        \Filament\Notifications\Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    public function getNewEInvoiceHandovers()
    {
        $user = auth()->user();

        return EInvoiceHandover::query()
            ->where('status', 'New')
            ->whereHas('lead', function (Builder $query) use ($user) {
                $query->where('salesperson', $user->id);
            })
            ->with(['lead.companyDetail', 'createdBy']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getNewEInvoiceHandovers())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->columns([
                TextColumn::make('project_code')
                    ->label('ID')
                    ->sortable()
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewEInvoiceDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (EInvoiceHandover $record) {
                                return view('components.einvoice-handover-details', [
                                    'record' => $record
                                ]);
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        $displayName = $state;
                        $company = null;

                        // Check if there's a subsidiary_id and get subsidiary company name
                        if (!empty($record->subsidiary_id)) {
                            $subsidiary = Subsidiary::find($record->subsidiary_id);
                            if ($subsidiary) {
                                $displayName = $subsidiary->company_name;
                                $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                            }
                        } else {
                            // Fall back to regular company lookup
                            $company = CompanyDetail::where('company_name', $state)->first();

                            if (!empty($record->lead_id)) {
                                $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                            }
                        }

                        if ($company) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($displayName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $displayName . '
                                </a>');
                        }

                        return "<span title='{$displayName}'>{$displayName}</span>";
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="background-color: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                        default => new HtmlString('<span style="padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 500; text-transform: uppercase;">' . $state . '</span>'),
                    }),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public function render()
    {
        return view('livewire.salesperson-dashboard.e-invoice-handover-new');
    }
}
