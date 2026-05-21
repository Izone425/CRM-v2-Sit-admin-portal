<?php

namespace App\Filament\Pages;

use App\Models\Customer;
use App\Models\ImplementerAppointment;
use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class ProjectSessionList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Project Session';
    protected static ?string $title = 'Project Session';
    protected static ?string $slug = 'project-session-list';
    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.project-session-list';

    public function getTableRecordKey(Model $record): string
    {
        return (string) $record->software_handover_id;
    }

    /**
     * Get summary stats
     */
    public function getStats(): array
    {
        $sessionTypes = ['KICK OFF MEETING SESSION', 'REVIEW SESSION'];

        // SW IDs with a New appointment (already booked, not waiting)
        $swIdsWithNewAppointment = ImplementerAppointment::whereIn('type', $sessionTypes)
            ->where('status', 'New')
            ->whereNotNull('software_handover_id')
            ->pluck('software_handover_id')
            ->unique()
            ->toArray();

        // SW IDs that had a kick off meeting (Done or New with meeting_link)
        $swIdsWithKickOff = ImplementerAppointment::where('type', 'KICK OFF MEETING SESSION')
            ->whereNotNull('software_handover_id')
            ->whereIn('status', ['Done', 'New'])
            ->whereNotNull('meeting_link')
            ->where('meeting_link', '!=', '')
            ->pluck('software_handover_id')
            ->unique()
            ->toArray();

        // First time book (kick off) = activated, no New appointment, never had kick off
        $totalFirstTimeBook = SoftwareHandover::whereHas('customer', fn ($q) => $q->where('able_set_meeting', true))
            ->whereNotIn('id', $swIdsWithNewAppointment)
            ->whereNotIn('id', $swIdsWithKickOff)
            ->count();

        // Waiting to book (review session) = activated, no New appointment, already had kick off
        $totalWaitingToBook = SoftwareHandover::whereHas('customer', fn ($q) => $q->where('able_set_meeting', true))
            ->whereNotIn('id', $swIdsWithNewAppointment)
            ->whereIn('id', $swIdsWithKickOff)
            ->count();

        // Waiting to book SW IDs (activated, no New appointment)
        $waitingToBookSwIds = SoftwareHandover::whereHas('customer', fn ($q) => $q->where('able_set_meeting', true))
            ->whereNotIn('id', $swIdsWithNewAppointment)
            ->pluck('id')
            ->toArray();

        // Implementer ranking by waiting to book customers
        $implementerRanking = SoftwareHandover::whereIn('id', $waitingToBookSwIds)
            ->whereNotNull('implementer')
            ->where(function ($q) {
                $q->where('status_handover', '!=', 'Closed')
                    ->orWhereNull('status_handover');
            })
            ->select('implementer', DB::raw('COUNT(*) as count'))
            ->groupBy('implementer')
            ->orderByDesc('count')
            ->get()
            ->map(fn ($row) => ['name' => $row->implementer, 'count' => $row->count])
            ->toArray();

        return [
            'total_first_time_book' => $totalFirstTimeBook,
            'total_waiting_to_book' => $totalWaitingToBook,
            'implementer_ranking' => $implementerRanking,
        ];
    }

    public function table(Table $table): Table
    {
        $sessionTypes = ['KICK OFF MEETING SESSION', 'REVIEW SESSION'];

        // Get software_handover_ids that have appointments with status 'New'
        $swIdsWithNewAppointment = ImplementerAppointment::whereIn('type', $sessionTypes)
            ->where('status', 'New')
            ->whereNotNull('software_handover_id')
            ->pluck('software_handover_id')
            ->unique()
            ->toArray();

        return $table
            ->query(
                SoftwareHandover::query()
                    ->whereHas('customer', function ($q) {
                        $q->where('able_set_meeting', true);
                    })
                    ->whereNotIn('id', $swIdsWithNewAppointment)
                    ->whereIn('status_handover', ['Open', 'Delay'])
                    ->select([
                        'software_handovers.id',
                        'software_handovers.lead_id',
                        'software_handovers.company_name',
                        'software_handovers.implementer',
                        'software_handovers.status',
                        'software_handovers.status_handover',
                        'software_handovers.created_at',
                    ])
            )
            ->defaultSort('created_at', 'desc')
            ->poll('300s')
            ->columns([
                TextColumn::make('project_code')
                    ->label('Project Code')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('id', $direction))
                    ->searchable(query: fn (Builder $query, string $search) =>
                        $query->where('id', 'like', "%{$search}%")
                    )
                    ->color('primary')
                    ->weight('bold')
                    ->url(function (SoftwareHandover $record) {
                        if ($record->lead_id) {
                            return route('filament.admin.resources.leads.view', [
                                'record' => \App\Classes\Encryptor::encrypt($record->lead_id),
                            ]) . '?view=implementer';
                        }
                        return null;
                    })
                    ->openUrlInNewTab(),

                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('bold'),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('booked_sessions')
                    ->label('Booked')
                    ->alignCenter()
                    ->getStateUsing(function (SoftwareHandover $record) {
                        return ImplementerAppointment::where('software_handover_id', $record->id)
                            ->whereIn('type', ['KICK OFF MEETING SESSION', 'REVIEW SESSION'])
                            ->whereIn('status', ['Done', 'New'])
                            ->whereNotNull('meeting_link')
                            ->where('meeting_link', '!=', '')
                            ->count();
                    })
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'info' : 'gray'),

                TextColumn::make('latest_booking')
                    ->label('Latest Booking')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $latest = ImplementerAppointment::where('software_handover_id', $record->id)
                            ->whereIn('type', ['KICK OFF MEETING SESSION', 'REVIEW SESSION'])
                            ->whereIn('status', ['Done', 'New'])
                            ->whereNotNull('meeting_link')
                            ->where('meeting_link', '!=', '')
                            ->latest('date')
                            ->first();

                        return $latest?->date?->format('d M Y') ?? '-';
                    }),

                TextColumn::make('activated')
                    ->label('Activate Session')
                    ->alignCenter()
                    ->getStateUsing(function (SoftwareHandover $record) {
                        return \App\Models\ActivateSessionLog::where('software_handover_id', $record->id)->exists();
                    })
                    ->formatStateUsing(function ($state) {
                        return $state
                            ? new HtmlString('<i class="bi bi-check-circle-fill" style="font-size: 1.2rem; color:green;"></i>')
                            : new HtmlString('<i class="bi bi-x-circle-fill" style="font-size: 1.2rem; color:red;"></i>');
                    }),

                TextColumn::make('last_activation_sent')
                    ->label('Last Activation Sent')
                    ->getStateUsing(function (SoftwareHandover $record) {
                        $log = \App\Models\ActivateSessionLog::where('software_handover_id', $record->id)
                            ->latest('created_at')
                            ->first();

                        return $log?->created_at?->format('d M Y, H:i') ?? '-';
                    }),

                TextColumn::make('status_handover')
                    ->label('Project Status')
                    ->badge()
                    ->color(fn ($state) => match (strtolower($state ?? '')) {
                        'completed', 'closed' => 'success',
                        'in progress', 'pending' => 'warning',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('implementer')
                    ->label('Implementer')
                    ->options(fn () => SoftwareHandover::whereHas('customer', fn ($q) => $q->where('able_set_meeting', true))
                        ->whereNotNull('implementer')
                        ->distinct()
                        ->pluck('implementer', 'implementer')
                        ->toArray()
                    )
                    ->searchable(),

                SelectFilter::make('status_handover')
                    ->label('Status Handover')
                    ->options(fn () => SoftwareHandover::whereHas('customer', fn ($q) => $q->where('able_set_meeting', true))
                        ->whereNotNull('status_handover')
                        ->where('status_handover', '!=', '')
                        ->distinct()
                        ->pluck('status_handover', 'status_handover')
                        ->toArray()
                    )
                    ->searchable(),
            ])
            ->emptyStateHeading('No sessions found')
            ->emptyStateDescription('No software handovers have implementer sessions yet.');
    }
}
