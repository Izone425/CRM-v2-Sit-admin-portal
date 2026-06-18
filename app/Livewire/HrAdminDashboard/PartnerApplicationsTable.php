<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\PartnerApplication;
use App\Services\ResellerApprovalService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PartnerApplicationsTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public string $type = 'reseller';

    public function mount(string $type = 'reseller'): void
    {
        $this->type = $type;
    }

    public function table(Table $table): Table
    {
        $type = $this->type;

        return $table
            ->query(
                PartnerApplication::query()
                    ->where('partner_type', $type)
                    ->where('status', 'pending')
            )
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),

                TextColumn::make('partner_type')
                    ->label('Program')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => strtoupper($state))
                    ->color(fn (string $state) => $state === 'distributor' ? 'warning' : 'info')
                    ->toggleable(),

                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('full_name')
                    ->label('Contact')
                    ->getStateUsing(fn (PartnerApplication $r) => trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')))
                    ->searchable(query: function ($query, string $search) {
                        $query->where('first_name', 'like', "%{$search}%")
                              ->orWhere('last_name', 'like', "%{$search}%");
                    }),

                TextColumn::make('designation')
                    ->label('Designation')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('mobile_phone')
                    ->label('Mobile')
                    ->toggleable(),

                TextColumn::make('telephone')
                    ->label('Telephone')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('country')
                    ->label('Country')
                    ->toggleable(),

                TextColumn::make('industry')
                    ->label('Industry')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('existing_fingertec_reseller')
                    ->label('Existing FT')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('view_detail')
                    ->label('View Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading('Application Detail')
                    ->modalWidth('3xl')
                    ->slideOver()
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (PartnerApplication $record): View => view(
                        'livewire.hr-admin-dashboard.partner-application-detail',
                        ['application' => $record]
                    )),

                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve application')
                    ->modalDescription('This creates the reseller account and provisions a CRM trial database from the application modules and headcount.')
                    ->form([
                        TextInput::make('buffer_months')
                            ->label('Trial duration (months)')
                            ->helperText('Length of the CRM trial/buffer license.')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->required(),
                        Textarea::make('review_remark')
                            ->label('Remark (optional)')
                            ->rows(3),
                    ])
                    ->action(function (PartnerApplication $record, array $data): void {
                        try {
                            $service = $record->partner_type === 'distributor'
                                ? app(\App\Services\DistributorApprovalService::class)
                                : app(ResellerApprovalService::class);
                            $result = $service->approve(
                                $record,
                                (int) ($data['buffer_months'] ?? 1),
                                $data['review_remark'] ?? null,
                            );
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Approval failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $reseller = $result['reseller'];

                        if (! $result['dbProvisioned']) {
                            Notification::make()
                                ->title('Approved — reseller created, CRM DB pending')
                                ->body("Reseller {$reseller->email} created and listed, but CRM database provisioning failed: {$result['dbError']}. You can retry the DB creation later.")
                                ->warning()
                                ->persistent()
                                ->send();

                            return;
                        }

                        $body = "Reseller {$reseller->email} created, listed, and CRM trial database provisioned."
                            . " HR-v2 Account ID: {$result['accountId']}"
                            . " | Company ID: {$result['companyId']}"
                            . " | CRM login password: {$result['crmPassword']}";

                        Notification::make()
                            ->title('Application approved')
                            ->body($body)
                            ->success()
                            ->persistent()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Reason')
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (PartnerApplication $record, array $data): void {
                        $record->update([
                            'status' => 'rejected',
                            'reviewed_at' => now(),
                            'reviewed_by' => auth()->id(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);

                        Notification::make()
                            ->title('Application rejected')
                            ->success()
                            ->send();
                    }),
            ])
            ->striped()
            ->emptyStateHeading('No pending applications')
            ->emptyStateDescription('When applicants submit the public form, they will appear here for review.');
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.partner-applications-table');
    }
}
