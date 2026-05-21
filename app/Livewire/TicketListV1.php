<?php

namespace App\Livewire;

use App\Models\Ticket;
use App\Models\TicketPriority;
use Livewire\Component;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Carbon\Carbon;

class TicketListV1 extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public string $scope = 'all';
    public array $productIds = [1];

    protected $listeners = ['ticket-status-updated' => '$refresh'];

    public function render()
    {
        return view('livewire.ticket-list-v1');
    }

    public function table(Table $table): Table
    {
        $query = Ticket::whereIn('product_id', $this->productIds);

        if ($this->scope === 'my') {
            $email = auth()->user()?->email;
            $myId = $email ? \App\Models\TicketingUser::where('email', $email)->value('id') : null;
            if ($myId) {
                $query->where('requestor_id', $myId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $table
            ->query($query)
            ->paginated([50])
            ->paginationPageOptions([50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->tooltip('View Details')
                    ->extraAttributes(fn (Ticket $record): array => [
                        'x-tooltip.html' => new \Illuminate\Support\HtmlString(''),
                        'x-tooltip.raw' => new \Illuminate\Support\HtmlString(
                            '<div><strong>Module:</strong> ' . ($record->module?->name ?? 'N/A') . '</div>' .
                            '<div><strong>Company:</strong> ' . strtoupper($record->company_name ?? '') . '</div>' .
                            '<div><strong>Title:</strong> ' . htmlspecialchars($record->title ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</div>'
                        ),
                    ]),

                Tables\Columns\TextColumn::make('requestor.name')
                    ->label('Front End')
                    ->searchable()
                    ->sortable()
                    ->default('Unknown User')
                    ->formatStateUsing(fn ($state) => implode(' ', array_slice(explode(' ', $state ?? 'Unknown User'), 0, 2))),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => strtoupper(substr($state ?? '', 0, 15))),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->default('N/A')
                    ->formatStateUsing(fn ($state) =>
                        $state === 'TimeTec HR - Version 1' ? 'V1' :
                        ($state === 'TimeTec HR - Version 2' ? 'V2' : $state)
                    ),

                Tables\Columns\TextColumn::make('module.name')
                    ->label('Module')
                    ->sortable()
                    ->badge()
                    ->default('N/A'),

                Tables\Columns\BadgeColumn::make('priority.name')
                    ->label('Priority')
                    ->colors([
                        'danger' => fn ($state) => str_contains(strtolower($state ?? ''), 'bug') || str_contains(strtolower($state ?? ''), 'software'),
                        'warning' => fn ($state) => str_contains(strtolower($state ?? ''), 'backend') || str_contains(strtolower($state ?? ''), 'assistance'),
                        'primary' => fn ($state) => str_contains(strtolower($state ?? ''), 'critical enhancement'),
                        'info' => fn ($state) => str_contains(strtolower($state ?? ''), 'paid') || str_contains(strtolower($state ?? ''), 'customization'),
                        'success' => fn ($state) => str_contains(strtolower($state ?? ''), 'non-critical'),
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'New',
                        'warning' => 'In Progress',
                        'success' => 'Completed',
                        'danger' => 'Closed',
                    ]),

                Tables\Columns\TextColumn::make('pending_party')
                    ->label('Comment')
                    ->badge()
                    ->getStateUsing(fn (Ticket $record) => in_array($record->status, ['New', 'In Progress']) ? ($record->pending_party ?? '-') : '-')
                    ->color(fn ($state) => match ($state) {
                        'Pending RND' => 'warning',
                        'Pending FE' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->color(fn ($state) => $state === 'Mobile' ? 'info' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('zoho_id')
                    ->label('Zoho Ticket')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->formatStateUsing(fn ($state) => $state ? $state->addHours(8)->format('d M Y, H:i') : null),

                Tables\Columns\TextColumn::make('overdue')
                    ->label('Overdue')
                    ->getStateUsing(function (Ticket $record): ?string {
                        $isP1OrP2 = in_array($record->priority?->sort_order, [1, 2], true);
                        $isActiveStatus = in_array($record->status, ['New', 'In Progress'], true);

                        if (!$isP1OrP2 || !$isActiveStatus) {
                            return null;
                        }

                        $days = Carbon::now()->diffInDays($record->created_at);
                        return $days > 0 ? '-' . $days . ' Days' : '0 Day';
                    })
                    ->html()
                    ->formatStateUsing(fn ($state) => $state !== null ? '<span style="color: #dc2626; font-weight: 700;">' . $state . '</span>' : '')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderByRaw("CASE WHEN status IN ('Completed', 'Closed') THEN 1 ELSE 0 END, DATEDIFF(NOW(), created_at) {$direction}");
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('requestor_id')
                    ->label('Front End')
                    ->multiple()
                    ->options(function () {
                        return \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('users')
                            ->whereIn('id', function ($query) {
                                $query->select('requestor_id')
                                    ->from('tickets')
                                    ->whereIn('product_id', $this->productIds)
                                    ->whereNotNull('requestor_id')
                                    ->distinct();
                            })
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable(),

                Tables\Filters\SelectFilter::make('module_id')
                    ->label('Module')
                    ->multiple()
                    ->options(function () {
                        return \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('product_has_modules')
                            ->join('modules', 'product_has_modules.module_id', '=', 'modules.id')
                            ->whereIn('product_has_modules.product_id', $this->productIds)
                            ->where('modules.is_active', true)
                            ->orderBy('modules.name')
                            ->pluck('modules.name', 'modules.id')
                            ->toArray();
                    }),

                Tables\Filters\SelectFilter::make('status')
                    ->multiple()
                    ->options(function () {
                        return \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('tickets')
                            ->whereIn('product_id', $this->productIds)
                            ->whereNotNull('status')
                            ->distinct()
                            ->pluck('status', 'status')
                            ->toArray();
                    }),

                Tables\Filters\SelectFilter::make('pending_party')
                    ->label('Comment')
                    ->multiple()
                    ->options([
                        'Pending RND' => 'Pending RND',
                        'Pending FE' => 'Pending FE',
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['values']) && Ticket::hasPendingPartyColumn()) {
                            $query->whereIn('status', ['New', 'In Progress'])
                                  ->whereIn('pending_party', $data['values']);
                        }
                    }),

                Tables\Filters\SelectFilter::make('priority_id')
                    ->label('Priority')
                    ->multiple()
                    ->options(function () {
                        $usedIds = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('tickets')
                            ->whereIn('product_id', $this->productIds)
                            ->whereNotNull('priority_id')
                            ->distinct()
                            ->pluck('priority_id')
                            ->all();

                        return TicketPriority::where('is_active', true)
                            ->whereIn('id', $usedIds)
                            ->orderBy('sort_order')
                            ->pluck('name', 'id')
                            ->toArray();
                    }),

                Tables\Filters\SelectFilter::make('created_more_than')
                    ->label('Created More Than')
                    ->options([
                        '2_weeks' => '2 Weeks',
                        '1_month' => '1 Month',
                        '2_months' => '2 Months',
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['value'])) {
                            $date = match ($data['value']) {
                                '2_weeks' => Carbon::now()->subWeeks(2),
                                '1_month' => Carbon::now()->subMonth(),
                                '2_months' => Carbon::now()->subMonths(2),
                                default => null,
                            };
                            if ($date) {
                                $query->where('created_at', '<=', $date);
                            }
                        }
                    }),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DateRangePicker::make('created_date_range')
                            ->label('Created Date Range')
                            ->placeholder('Select date range'),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['created_date_range'])) {
                            [$start, $end] = explode(' - ', $data['created_date_range']);
                            $query->whereDate('created_at', '>=', Carbon::createFromFormat('d/m/Y', $start))
                                  ->whereDate('created_at', '<=', Carbon::createFromFormat('d/m/Y', $end));
                        }
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (!empty($data['created_date_range'])) {
                            [$start, $end] = explode(' - ', $data['created_date_range']);
                            return 'Created: ' . Carbon::createFromFormat('d/m/Y', $start)->format('d M Y')
                                . ' - ' . Carbon::createFromFormat('d/m/Y', $end)->format('d M Y');
                        }
                        return null;
                    }),
            ])
            ->recordAction('view')
            ->recordUrl(null)
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    /**
     * Handle row click - dispatch event to TicketModal component
     */
    public function view($recordId): void
    {
        $this->dispatch('openTicketModal', $recordId);
    }
}
