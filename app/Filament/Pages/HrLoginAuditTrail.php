<?php

namespace App\Filament\Pages;

use App\Models\HrLoginAsUserLog;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class HrLoginAuditTrail extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static string $view = 'filament.pages.hr-login-audit-trail';
    protected static ?string $navigationLabel = 'Login Audit Trail';
    protected static ?string $title = 'Login Audit Trail';
    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-login-audit-trail';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HrLoginAsUserLog::query()
                    ->with(['causer', 'softwareHandover'])
            )
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'initiated' => 'Initiated',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ])
                    ->placeholder('All Status'),

                SelectFilter::make('causer_id')
                    ->label('CRM User')
                    ->options(function () {
                        return HrLoginAsUserLog::query()
                            ->whereNotNull('causer_id')
                            ->distinct()
                            ->pluck('causer_name', 'causer_id')
                            ->filter()
                            ->toArray();
                    })
                    ->searchable()
                    ->placeholder('All CRM Users'),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'From: ' . $data['from'];
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Until: ' . $data['until'];
                        }
                        return $indicators;
                    }),
            ])
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                TextColumn::make('causer_name')
                    ->label('CRM User')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($state, HrLoginAsUserLog $record) {
                        $name = $state ?: '-';
                        $id = $record->causer_id ? " (#{$record->causer_id})" : '';
                        return $name . $id;
                    }),

                TextColumn::make('target_email')
                    ->label('Target Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email copied!'),

                TextColumn::make('hr_user_id')
                    ->label('HR User ID')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('softwareHandover.company_name')
                    ->label('Company')
                    ->searchable()
                    ->wrap()
                    ->limit(40)
                    ->tooltip(fn (HrLoginAsUserLog $record) => $record->softwareHandover?->company_name)
                    ->formatStateUsing(function ($state, HrLoginAsUserLog $record) {
                        if ($state) {
                            return $state;
                        }
                        return $record->hr_company_id ? "Backend ID: {$record->hr_company_id}" : '-';
                    }),

                TextColumn::make('hr_company_id')
                    ->label('Backend ID')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match (strtolower($state)) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'initiated' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('error_message')
                    ->label('Error')
                    ->wrap()
                    ->limit(60)
                    ->tooltip(fn (HrLoginAsUserLog $record) => $record->error_message)
                    ->toggleable(),

                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(50)
                    ->tooltip(fn (HrLoginAsUserLog $record) => $record->user_agent)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc');
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}
