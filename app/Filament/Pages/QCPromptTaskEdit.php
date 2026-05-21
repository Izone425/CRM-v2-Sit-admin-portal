<?php

namespace App\Filament\Pages;

use App\Models\QcTask;
use App\Models\QcTaskLabelOption;
use App\Models\QcTaskPrompt;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;

class QCPromptTaskEdit extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';
    protected static ?string $slug = 'qc-prompt-task/{record}/edit';
    protected static string $view = 'filament.pages.qc-prompt-task-form';
    protected static bool $shouldRegisterNavigation = false;

    public bool $isEdit = true;
    public ?int $editingTaskId = null;

    public string $hrVersion = 'v1';
    public string $module = '';
    public string $taskTitle = '';
    public string $labelTier1 = '';
    public string $labelTier2 = '';
    public string $labelTier3 = '';

    public array $prompts = [''];
    public array $promptLocks = [false];
    public array $promptIds = [null];

    // Create-option flow state
    public string $creatingTier = '';
    public string $newOptionValue = '';

    public function mount(int|string $record): void
    {
        $task = QcTask::with('prompts')->findOrFail((int) $record);

        $this->editingTaskId = $task->id;
        $this->hrVersion = $task->hr_version;
        $this->module = $task->module;
        $this->taskTitle = $task->title;
        $this->labelTier1 = (string) $task->label_tier1;
        $this->labelTier2 = (string) $task->label_tier2;
        $this->labelTier3 = (string) $task->label_tier3;

        $sorted = $task->prompts->sortBy('order')->values();
        $this->prompts = [];
        $this->promptLocks = [];
        $this->promptIds = [];
        foreach ($sorted as $p) {
            $this->prompts[] = $p->prompt;
            $this->promptLocks[] = $p->status === 'completed';
            $this->promptIds[] = $p->id;
        }

        if (empty($this->prompts)) {
            $this->prompts = [''];
            $this->promptLocks = [false];
            $this->promptIds = [null];
        }
    }

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function addPrompt(): void
    {
        $this->prompts[] = '';
        $this->promptLocks[] = false;
        $this->promptIds[] = null;
    }

    public function removePrompt(int $index): void
    {
        if (count($this->prompts) <= 1) {
            return;
        }
        // Cannot remove a completed (locked) prompt
        if ($this->promptLocks[$index] ?? false) {
            Notification::make()
                ->title('Cannot remove a completed prompt')
                ->warning()
                ->send();
            return;
        }

        unset($this->prompts[$index], $this->promptLocks[$index], $this->promptIds[$index]);
        $this->prompts = array_values($this->prompts);
        $this->promptLocks = array_values($this->promptLocks);
        $this->promptIds = array_values($this->promptIds);
    }

    public function getAvailableModulesProperty(): array
    {
        return QcTask::modulesFor($this->hrVersion);
    }

    public function getLabelOptionsProperty(): array
    {
        return QcTaskLabelOption::groupedByTier($this->hrVersion, $this->module);
    }

    public function showCreateOption(string $tier): void
    {
        if (!in_array($tier, QcTaskLabelOption::TIERS, true)) {
            return;
        }
        $this->creatingTier = $tier;
        $this->newOptionValue = '';
    }

    public function cancelCreateOption(): void
    {
        $this->creatingTier = '';
        $this->newOptionValue = '';
    }

    public function saveNewOption(): void
    {
        $tier = $this->creatingTier;
        $value = trim($this->newOptionValue);

        if (!in_array($tier, QcTaskLabelOption::TIERS, true) || $value === '') {
            Notification::make()->title('Please enter a value')->warning()->send();
            return;
        }

        $option = QcTaskLabelOption::firstOrCreate(
            [
                'hr_version' => $this->hrVersion,
                'module' => $this->module,
                'tier' => $tier,
                'value' => strtoupper($value),
            ],
            ['created_by' => auth()->id()],
        );

        if ($tier === 'tier1') $this->labelTier1 = $option->value;
        if ($tier === 'tier2') $this->labelTier2 = $option->value;
        if ($tier === 'tier3') $this->labelTier3 = $option->value;

        $this->creatingTier = '';
        $this->newOptionValue = '';

        Notification::make()->title('Option added')->body($option->value)->success()->send();
    }

    public function save()
    {
        // In edit mode, hrVersion/module are locked in the UI — reload from DB to be safe
        $task = QcTask::with('prompts')->find($this->editingTaskId);
        if (!$task) {
            Notification::make()->title('Task not found')->danger()->send();
            return redirect(QCPromptTask::getUrl());
        }

        $this->hrVersion = $task->hr_version;
        $this->module = $task->module;

        // Build rules dynamically — locked prompts don't need content validation (we'll keep original)
        $rules = [
            'taskTitle'  => 'required|string|max:255',
            'labelTier1' => 'nullable|string|max:255',
            'labelTier2' => 'nullable|string|max:255',
            'labelTier3' => 'nullable|string|max:255',
            'prompts'    => 'required|array|min:1',
        ];
        foreach (array_keys($this->prompts) as $i) {
            $isLocked = $this->promptLocks[$i] ?? false;
            $rules["prompts.$i"] = $isLocked ? 'nullable|string' : 'required|string';
        }

        $data = $this->validate($rules, [
            'prompts.*.required' => 'Each prompt is required.',
        ]);

        DB::transaction(function () use ($task, $data) {
            $task->update([
                'title'       => strtoupper($data['taskTitle']),
                'label_tier1' => $data['labelTier1'] ? strtoupper($data['labelTier1']) : null,
                'label_tier2' => $data['labelTier2'] ? strtoupper($data['labelTier2']) : null,
                'label_tier3' => $data['labelTier3'] ? strtoupper($data['labelTier3']) : null,
            ]);

            $keptIds = [];

            foreach ($data['prompts'] as $index => $text) {
                $existingId = $this->promptIds[$index] ?? null;
                $isLocked = $this->promptLocks[$index] ?? false;

                if ($existingId) {
                    $existing = QcTaskPrompt::where('task_id', $task->id)->find($existingId);
                    if ($existing) {
                        $update = ['order' => $index];
                        // Only update text if NOT locked (completed)
                        if (!$isLocked) {
                            $update['prompt'] = $text;
                        }
                        $existing->update($update);
                        $keptIds[] = $existing->id;
                    }
                } else {
                    $created = QcTaskPrompt::create([
                        'task_id' => $task->id,
                        'prompt'  => $text,
                        'order'   => $index,
                    ]);
                    $keptIds[] = $created->id;
                }
            }

            // Delete prompts that were removed during edit, but never delete a completed one
            $toDelete = $task->prompts()
                ->whereNotIn('id', $keptIds ?: [0])
                ->where('status', '!=', 'completed')
                ->pluck('id')
                ->toArray();

            if (!empty($toDelete)) {
                QcTaskPrompt::whereIn('id', $toDelete)->delete();
            }
        });

        Notification::make()
            ->title('Task updated')
            ->success()
            ->send();

        return redirect(QCPromptTask::getUrl());
    }
}
