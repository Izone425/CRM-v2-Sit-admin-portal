<?php

namespace App\Filament\Pages;

use App\Models\CustomPublicHoliday;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Livewire\Attributes\Computed;

class CustomPublicHolidayPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Custom Public Holiday';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.custom-public-holiday';
    protected static ?string $slug = 'custom-public-holiday';

    public bool $showAddModal = false;
    public bool $isAddMode = false;
    public array $holidaysByYear = [];
    public array $yearOrder = [];
    public int $focusYear = 0;
    public ?int $errorYear = null;
    public ?int $errorIndex = null;
    public ?string $errorField = null;
    public ?string $errorMessage = null;

    #[Computed]
    public function yearsList(): array
    {
        return CustomPublicHoliday::orderBy('date')->get()
            ->groupBy(fn ($h) => (int) Carbon::parse($h->date)->year)
            ->map(fn ($holidays, $year) => [
                'year' => (int) $year,
                'count' => $holidays->count(),
            ])
            ->sortByDesc('year')
            ->values()
            ->toArray();
    }

    public function openAddModal(?int $year = null): void
    {
        $byYear = CustomPublicHoliday::orderBy('date')->get()
            ->groupBy(fn ($h) => (int) Carbon::parse($h->date)->year);

        $currentYear = (int) now()->year;

        if ($year !== null) {
            // Edit mode: show only the requested year
            $allYears = [$year];
        } else {
            // Add mode: show the first year that doesn't yet have holidays
            $allYears = [];
            $candidate = $currentYear;
            while (empty($allYears) && $candidate <= $currentYear + 10) {
                if (! $byYear->has($candidate)) {
                    $allYears[] = $candidate;
                }
                $candidate++;
            }
            if (empty($allYears)) {
                $allYears = [$currentYear];
            }
        }

        $data = [];
        foreach ($allYears as $y) {
            $rows = $byYear->get($y, collect())
                ->map(fn ($h) => [
                    'uid' => 'db-' . $h->id,
                    'id' => $h->id,
                    'date' => Carbon::parse($h->date)->format('Y-m-d'),
                    'name' => $h->name,
                ])->values()->toArray();

            while (count($rows) < 25) {
                $rows[] = $this->makeEmptyRow();
            }

            $data[(int) $y] = $rows;
        }

        $this->holidaysByYear = $data;
        $this->yearOrder = $allYears;
        $this->focusYear = $year ?? $allYears[0];
        $this->isAddMode = ($year === null);
        $this->showAddModal = true;
    }

    public function closeAddModal(): void
    {
        $this->showAddModal = false;
        $this->isAddMode = false;
        $this->holidaysByYear = [];
        $this->yearOrder = [];
        $this->focusYear = 0;
        $this->errorYear = null;
        $this->errorIndex = null;
        $this->errorField = null;
        $this->errorMessage = null;
    }

    private function makeEmptyRow(): array
    {
        return [
            'uid' => 'new-' . uniqid('', true),
            'id' => null,
            'date' => null,
            'name' => null,
        ];
    }

    public function addRow(int $year): void
    {
        $this->holidaysByYear[$year][] = $this->makeEmptyRow();
    }

    public function addRowAt(int $year, int $position): void
    {
        if (! isset($this->holidaysByYear[$year])) {
            return;
        }
        array_splice(
            $this->holidaysByYear[$year],
            $position,
            0,
            [$this->makeEmptyRow()]
        );
    }

    public function reorderRows(int $year, array $newOrder): void
    {
        if (! isset($this->holidaysByYear[$year])) {
            return;
        }

        $byUid = collect($this->holidaysByYear[$year])->keyBy('uid');
        $reordered = [];
        foreach ($newOrder as $uid) {
            if ($byUid->has($uid)) {
                $reordered[] = $byUid->get($uid);
            }
        }

        // Append any rows not present in the new order (safety)
        foreach ($this->holidaysByYear[$year] as $row) {
            if (! in_array($row['uid'], $newOrder, true)) {
                $reordered[] = $row;
            }
        }

        $this->holidaysByYear[$year] = $reordered;
    }

    public function removeRow(int $year, int $index): void
    {
        unset($this->holidaysByYear[$year][$index]);
        $this->holidaysByYear[$year] = array_values($this->holidaysByYear[$year]);
    }

    public function saveHolidays(): void
    {
        $this->errorYear = null;
        $this->errorIndex = null;
        $this->errorField = null;
        $this->errorMessage = null;

        // Completeness validation: a row with a date must also have a name (and vice versa)
        foreach ($this->holidaysByYear as $year => $rows) {
            foreach ($rows as $idx => $row) {
                $hasDate = ! empty($row['date']);
                $hasName = ! empty($row['name']);

                if ($hasDate && ! $hasName) {
                    $this->errorYear = (int) $year;
                    $this->errorIndex = $idx;
                    $this->errorField = 'name';
                    $this->errorMessage = "Row " . ($idx + 1) . ": Holiday name is required.";

                    Notification::make()
                        ->title('Missing holiday name')
                        ->body($this->errorMessage)
                        ->danger()
                        ->send();
                    return;
                }

                if (! $hasDate && $hasName) {
                    $this->errorYear = (int) $year;
                    $this->errorIndex = $idx;
                    $this->errorField = 'date';
                    $this->errorMessage = "Row " . ($idx + 1) . ": Date is required.";

                    Notification::make()
                        ->title('Missing date')
                        ->body($this->errorMessage)
                        ->danger()
                        ->send();
                    return;
                }
            }
        }

        // Chronological-order validation: within each year, every filled row's
        // date must be >= the previous filled row's date.
        foreach ($this->holidaysByYear as $year => $rows) {
            $previousDate = null;
            $previousIdx = null;
            foreach ($rows as $idx => $row) {
                if (empty($row['date'])) {
                    continue;
                }

                if ($previousDate !== null && $row['date'] < $previousDate) {
                    $this->errorYear = (int) $year;
                    $this->errorIndex = $idx;
                    $this->errorField = 'date';
                    $this->errorMessage = "Row " . ($idx + 1) . " ({$row['date']}) cannot be earlier than row " . ($previousIdx + 1) . " ({$previousDate}). Please reorder.";

                    Notification::make()
                        ->title('Invalid date order')
                        ->body($this->errorMessage)
                        ->danger()
                        ->send();
                    return;
                }

                $previousDate = $row['date'];
                $previousIdx = $idx;
            }
        }

        $created = 0;
        $updated = 0;
        $deleted = 0;

        // Only touch the years currently loaded in the modal. Other years stay untouched.
        foreach ($this->holidaysByYear as $year => $rows) {
            $year = (int) $year;

            $filledRows = array_filter(
                $rows,
                fn ($r) => ! empty($r['date']) && ! empty($r['name'])
            );

            $submittedIds = collect($filledRows)
                ->pluck('id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            // Delete records for THIS year that weren't submitted
            $deleteQuery = CustomPublicHoliday::whereYear('date', $year);
            if (! empty($submittedIds)) {
                $deleteQuery->whereNotIn('id', $submittedIds);
            }
            $deleted += $deleteQuery->delete();

            // Upsert the rows
            foreach ($filledRows as $row) {
                $payload = [
                    'date' => $row['date'],
                    'name' => strtoupper((string) $row['name']),
                    'day_of_week' => Carbon::parse($row['date'])->dayOfWeekIso,
                ];

                if (! empty($row['id'])) {
                    CustomPublicHoliday::where('id', $row['id'])->update($payload);
                    $updated++;
                } else {
                    CustomPublicHoliday::create($payload);
                    $created++;
                }
            }
        }

        Notification::make()
            ->title('Holidays saved')
            ->body("Created: {$created}, Updated: {$updated}, Deleted: {$deleted}")
            ->success()
            ->send();

        $this->closeAddModal();
        unset($this->yearsList);
    }
}
