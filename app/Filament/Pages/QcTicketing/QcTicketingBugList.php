<?php

namespace App\Filament\Pages\QcTicketing;

use App\Models\Ticketing\Bug;
use App\Models\Ticketing\Module;
use App\Models\Ticketing\Product;
use App\Models\Ticketing\Solution;
use App\Models\Ticketing\SubModule;
use App\Models\Ticketing\TicketingUser;
use Filament\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QcTicketingBugList extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';
    protected static string $view = 'filament.pages.qc-ticketing.bug-list';
    protected static ?string $navigationLabel = 'Bug List';
    protected static ?string $title = 'Bug List';
    protected static ?string $slug = 'qc-ticketing/bug-list';

    protected static bool $shouldRegisterNavigation = false;

    public string $activeTab = 'All';
    public string $viewMode = 'all';
    public string $version = 'all';

    public function mount(): void
    {
        $this->viewMode = session('qc_bug_view_mode', 'all');
        $this->activeTab = session('qc_bug_active_tab', 'All');
        $this->version = session('qc_bug_version', 'all');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createBug')
                ->label('Create Bug')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->action(fn () => $this->dispatch('openCreateBugModal')),
        ];
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        session(['qc_bug_active_tab' => $tab]);
        $this->resetTable();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        session(['qc_bug_view_mode' => $mode]);
        $this->resetTable();
    }

    public function setVersion(string $version): void
    {
        $this->version = $version;
        session(['qc_bug_version' => $version]);
        $this->resetTable();
    }

    protected const HR_PRODUCT_IDS = [1, 2];

    protected function tableQuery()
    {
        $productIds = match ($this->version) {
            'v1' => [1],
            'v2' => [2],
            default => self::HR_PRODUCT_IDS,
        };

        $query = Bug::query()
            ->whereIn('product_id', $productIds)
            ->with(['product:id,name', 'module:id,name', 'subModule:id,name', 'relatedTask:id,task_id', 'reporter:id,name,email']);

        $tabMap = [
            'New' => 'New',
            'Reopen' => 'Reopen',
            'QC-In Progress' => 'QC - In Progress',
            'Ready For Testing' => 'Ready for Testing',
            'Ready For Live' => 'Ready For Live',
            'Live' => 'Live',
            'Closed' => 'Closed',
            'Rejected' => 'Rejected',
        ];

        if (isset($tabMap[$this->activeTab])) {
            $query->where('status', $tabMap[$this->activeTab]);
        }

        if ($this->viewMode === 'my') {
            $email = auth()->user()?->email;
            $ticketUserId = $email
                ? TicketingUser::where('email', $email)->value('id')
                : null;

            if ($ticketUserId) {
                $query->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $ticketUserId)]);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        return $query;
    }

    public function getTabCountsProperty(): array
    {
        $productIds = match ($this->version) {
            'v1' => [1],
            'v2' => [2],
            default => self::HR_PRODUCT_IDS,
        };

        $base = Bug::query()->whereIn('product_id', $productIds);

        if ($this->viewMode === 'my') {
            $email = auth()->user()?->email;
            $ticketUserId = $email
                ? TicketingUser::where('email', $email)->value('id')
                : null;
            if ($ticketUserId) {
                $base->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $ticketUserId)]);
            } else {
                return array_fill_keys(['All', 'New', 'Reopen', 'Ready For Testing', 'QC-In Progress', 'Ready For Live', 'Live', 'Closed', 'Rejected'], 0);
            }
        }

        return [
            'All' => (clone $base)->count(),
            'New' => (clone $base)->where('status', 'New')->count(),
            'Reopen' => (clone $base)->where('status', 'Reopen')->count(),
            'QC-In Progress' => (clone $base)->where('status', 'QC - In Progress')->count(),
            'Ready For Testing' => (clone $base)->where('status', 'Ready for Testing')->count(),
            'Ready For Live' => (clone $base)->where('status', 'Ready For Live')->count(),
            'Live' => (clone $base)->where('status', 'Live')->count(),
            'Closed' => (clone $base)->where('status', 'Closed')->count(),
            'Rejected' => (clone $base)->where('status', 'Rejected')->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->tableQuery())
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::where('is_active', 1)->whereIn('id', self::HR_PRODUCT_IDS)->orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('solution_id')
                    ->label('Solution')
                    ->options(fn () => Solution::where('is_active', 1)
                        ->whereIn('id', \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('product_has_solutions')->whereIn('product_id', self::HR_PRODUCT_IDS)->pluck('solution_id'))
                        ->orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('module_id')
                    ->label('Module')
                    ->options(fn () => Module::where('is_active', 1)
                        ->whereIn('id', \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('product_has_modules')->whereIn('product_id', self::HR_PRODUCT_IDS)->pluck('module_id'))
                        ->orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('sub_module_id')
                    ->label('Sub-Module')
                    ->options(fn () => SubModule::where('is_active', 1)
                        ->whereIn('id', \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('product_module_has_sub_modules')->whereIn('product_id', self::HR_PRODUCT_IDS)->pluck('sub_module_id'))
                        ->orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('severity')
                    ->label('Severity')
                    ->options(fn () => Bug::query()->select('severity')->whereNotNull('severity')->distinct()->pluck('severity', 'severity')->toArray()),
            ])
            ->columns([
                TextColumn::make('bug_id')
                    ->label('Bug ID')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->size('sm')
                    ->tooltip(fn ($record) => $record->title)
                    ->extraAttributes(fn ($record): array => [
                        'x-tooltip.html' => new \Illuminate\Support\HtmlString(''),
                        'x-tooltip.raw' => new \Illuminate\Support\HtmlString(
                            '<div><strong>Title:</strong> ' . htmlspecialchars($record->title ?? 'N/A', ENT_QUOTES, 'UTF-8') . '</div>'
                        ),
                    ])
                    ->action(
                        \Filament\Tables\Actions\Action::make('openBugModal')
                            ->action(fn ($record) => $this->dispatch('openBugModal', $record->id))
                    ),

                TextColumn::make('reporter.name')
                    ->label('Reporter')
                    ->placeholder('-')
                    ->size('sm')
                    ->searchable(),

                TextColumn::make('relatedTask.task_id')
                    ->label('Related Task')
                    ->placeholder('-')
                    ->size('sm'),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->placeholder('-')
                    ->size('sm'),

                TextColumn::make('module.name')
                    ->label('Module')
                    ->placeholder('-')
                    ->size('sm'),

                TextColumn::make('subModule.name')
                    ->label('Sub Module')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size('sm'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'New' => 'warning',
                        'Closed', 'Live', 'Ready For Live' => 'success',
                        'Reopen', 'Rejected' => 'danger',
                        'In Progress', 'QC - In Progress', 'Testing', 'Ready for Testing' => 'info',
                        default => 'gray',
                    })
                    ->size('sm'),

                TextColumn::make('assignee_names')
                    ->label('Assignee')
                    ->size('sm')
                    ->wrap(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->date('Y-m-d')
                    ->toggleable()
                    ->size('sm'),

                TextColumn::make('overdue')
                    ->label('Overdue')
                    ->size('sm')
                    ->getStateUsing(function (Bug $record): ?string {
                        if (in_array($record->status, ['Closed', 'Live'])) {
                            return null;
                        }
                        $days = \Carbon\Carbon::now()->diffInDays($record->created_at);
                        return $days > 0 ? '-' . $days . ' Days' : '0 Day';
                    })
                    ->html()
                    ->formatStateUsing(fn ($state) => $state !== null
                        ? '<span style="color:#dc2626; font-weight:700;">' . $state . '</span>'
                        : ''
                    )
                    ->sortable(query: fn ($query, string $direction) =>
                        $query->orderByRaw("CASE WHEN status IN ('Closed', 'Live') THEN 1 ELSE 0 END, DATEDIFF(NOW(), created_at) {$direction}")
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
