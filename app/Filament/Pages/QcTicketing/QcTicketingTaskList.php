<?php

namespace App\Filament\Pages\QcTicketing;

use App\Models\Ticketing\Module;
use App\Models\Ticketing\Product;
use App\Models\Ticketing\Solution;
use App\Models\Ticketing\SubModule;
use App\Models\Ticketing\Task;
use App\Models\Ticketing\TaskPriority;
use App\Models\Ticketing\TicketingUser;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class QcTicketingTaskList extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';
    protected static string $view = 'filament.pages.qc-ticketing.task-list';
    protected static ?string $navigationLabel = 'Task List';
    protected static ?string $title = 'Task List';
    protected static ?string $slug = 'qc-ticketing/task-list';
    protected static bool $shouldRegisterNavigation = false;

    public string $activeTab = 'All';
    public string $viewMode = 'all';

    public function mount(): void
    {
        $this->viewMode = session('qc_task_view_mode', 'all');
        $this->activeTab = session('qc_task_active_tab', 'All');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        session(['qc_task_active_tab' => $tab]);
        $this->resetTable();
    }

    public function setViewMode(string $mode): void
    {
        $this->viewMode = $mode;
        session(['qc_task_view_mode' => $mode]);
        $this->resetTable();
    }

    protected const HR_PRODUCT_IDS = [1, 2];

    protected function tableQuery()
    {
        $query = Task::query()
            ->whereIn('product_id', self::HR_PRODUCT_IDS)
            ->with(['product:id,name', 'module:id,name', 'subModule:id,name', 'solution:id,name', 'priority:id,name,color,bg_color', 'ticket:id,ticket_id']);

        if ($this->activeTab === 'New') {
            $query->where('status', 'New');
        } elseif ($this->activeTab === 'Reopen') {
            $query->where('status', 'Reopen');
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
        $base = Task::query()->whereIn('product_id', self::HR_PRODUCT_IDS);

        if ($this->viewMode === 'my') {
            $email = auth()->user()?->email;
            $ticketUserId = $email
                ? TicketingUser::where('email', $email)->value('id')
                : null;
            if ($ticketUserId) {
                $base->whereRaw('JSON_CONTAINS(assignee_ids, ?)', [json_encode((int) $ticketUserId)]);
            } else {
                return ['All' => 0, 'New' => 0, 'Reopen' => 0];
            }
        }

        return [
            'All' => (clone $base)->count(),
            'New' => (clone $base)->where('status', 'New')->count(),
            'Reopen' => (clone $base)->where('status', 'Reopen')->count(),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->tableQuery())
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->searchable()
            ->filters([
                SelectFilter::make('priority_id')
                    ->label('Urgency')
                    ->options(fn () => TaskPriority::orderBy('id')->pluck('name', 'id')->toArray()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn () => Task::query()->select('status')->distinct()->orderBy('status')->pluck('status', 'status')->toArray()),
                SelectFilter::make('product_id')
                    ->label('Product')
                    ->options(fn () => Product::where('is_active', 1)->whereIn('id', self::HR_PRODUCT_IDS)->orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('module_id')
                    ->label('Module')
                    ->options(fn () => Module::where('is_active', 1)
                        ->whereIn('id', \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('product_has_modules')->whereIn('product_id', self::HR_PRODUCT_IDS)->pluck('module_id'))
                        ->orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('solution_id')
                    ->label('Solution')
                    ->options(fn () => Solution::where('is_active', 1)
                        ->whereIn('id', \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('product_has_solutions')->whereIn('product_id', self::HR_PRODUCT_IDS)->pluck('solution_id'))
                        ->orderBy('name')->pluck('name', 'id')->toArray()),
                SelectFilter::make('sub_module_id')
                    ->label('Sub-Module')
                    ->options(fn () => SubModule::where('is_active', 1)
                        ->whereIn('id', \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                            ->table('product_module_has_sub_modules')->whereIn('product_id', self::HR_PRODUCT_IDS)->pluck('sub_module_id'))
                        ->orderBy('name')->pluck('name', 'id')->toArray()),
            ])
            ->columns([
                TextColumn::make('task_id')
                    ->label('Task ID')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->size('sm')
                    ->action(
                        \Filament\Tables\Actions\Action::make('openTaskModal')
                            ->action(fn ($record) => $this->dispatch('openTaskModal', $record->id))
                    ),

                TextColumn::make('ticket.ticket_id')
                    ->label('Ticket ID')
                    ->placeholder('-')
                    ->color('info')
                    ->weight('bold')
                    ->size('sm')
                    ->action(
                        \Filament\Tables\Actions\Action::make('openTicketModal')
                            ->action(fn ($record) => $record->related_ticket_id
                                ? $this->dispatch('openTicketModal', $record->related_ticket_id)
                                : null)
                    ),

                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->title)
                    ->size('sm'),

                TextColumn::make('priority.name')
                    ->label('Urgency')
                    ->badge()
                    ->color(fn ($state) => match (strtolower((string) $state)) {
                        'highest', 'critical' => 'danger',
                        'high' => 'warning',
                        'medium' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    })
                    ->size('sm'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        'New' => 'warning',
                        'Closed' => 'success',
                        'Live' => 'success',
                        'Reopen' => 'danger',
                        'QC - In Progress', 'RND - In Progress' => 'info',
                        'Ready For Live', 'Ready For Testing' => 'primary',
                        default => 'gray',
                    })
                    ->size('sm'),

                TextColumn::make('assignee_names')
                    ->label('Assignee')
                    ->size('sm')
                    ->wrap(),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('Y-m-d')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('Y-m-d')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->toggleable()
                    ->size('sm'),

                TextColumn::make('solution.name')
                    ->label('Solution')
                    ->toggleable()
                    ->placeholder('-')
                    ->size('sm'),

                TextColumn::make('module.name')
                    ->label('Module')
                    ->toggleable()
                    ->placeholder('-')
                    ->size('sm'),

                TextColumn::make('subModule.name')
                    ->label('Sub Module')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-')
                    ->size('sm'),

                TextColumn::make('related_ticket_id')
                    ->label('Linked Ticket')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-')
                    ->size('sm'),

                TextColumn::make('created_at')
                    ->label('Created Date')
                    ->date('Y-m-d')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->size('sm'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
