<?php

namespace App\Filament\Pages;

use App\Models\QcTask;
use App\Models\QcTaskLabelOption;
use App\Models\QcTaskPrompt;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class QCPromptTaskCreate extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    protected static ?string $slug = 'qc-prompt-task/create';
    protected static string $view = 'filament.pages.qc-prompt-task-form';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $title = '';

    public bool $isEdit = false;
    public ?int $editingTaskId = null;

    public string $hrVersion = 'v1';
    public string $module = '';
    public string $taskTitle = '';
    public string $labelTier1 = '';
    public string $labelTier2 = '';
    public string $labelTier3 = '';

    public array $prompts = [''];
    public array $promptLocks = [false]; // true = locked (completed) — blade reads this to disable textarea
    public array $promptIds = [null];

    // Create-option flow state
    public string $creatingTier = ''; // 'tier1' | 'tier2' | 'tier3'
    public string $newOptionValue = '';

    public function mount(): void
    {
        $this->hrVersion = request()->query('version', 'v1');
        if (!in_array($this->hrVersion, QcTask::HR_VERSIONS, true)) {
            $this->hrVersion = 'v1';
        }

        $this->module = request()->query('module', '');
        $allowedModules = QcTask::modulesFor($this->hrVersion);
        if (!in_array($this->module, $allowedModules, true)) {
            $this->module = $allowedModules[0] ?? '';
        }
    }

    public function updatedHrVersion(string $value): void
    {
        $allowed = QcTask::modulesFor($value);
        if (!in_array($this->module, $allowed, true)) {
            $this->module = $allowed[0] ?? '';
        }
        $this->clearTierSelections();
    }

    public function updatedModule(): void
    {
        $this->clearTierSelections();
    }

    protected function clearTierSelections(): void
    {
        $this->labelTier1 = '';
        $this->labelTier2 = '';
        $this->labelTier3 = '';
        $this->creatingTier = '';
        $this->newOptionValue = '';
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
        $data = $this->validate([
            'hrVersion'  => 'required|in:v1,v2',
            'module'     => 'required|string',
            'taskTitle'  => 'required|string|max:255',
            'labelTier1' => 'nullable|string|max:255',
            'labelTier2' => 'nullable|string|max:255',
            'labelTier3' => 'nullable|string|max:255',
            'prompts'    => 'required|array|min:1',
            'prompts.*'  => 'required|string',
        ], [
            'prompts.*.required' => 'Each prompt is required.',
        ]);

        $allowedModules = QcTask::modulesFor($data['hrVersion']);
        if (!in_array($data['module'], $allowedModules, true)) {
            Notification::make()
                ->title('Invalid module for selected version')
                ->danger()
                ->send();
            return null;
        }

        $task = DB::transaction(function () use ($data) {
            $task = QcTask::create([
                'hr_version'  => $data['hrVersion'],
                'module'      => $data['module'],
                'title'       => strtoupper($data['taskTitle']),
                'label_tier1' => $data['labelTier1'] ? strtoupper($data['labelTier1']) : null,
                'label_tier2' => $data['labelTier2'] ? strtoupper($data['labelTier2']) : null,
                'label_tier3' => $data['labelTier3'] ? strtoupper($data['labelTier3']) : null,
                'created_by'  => auth()->id(),
            ]);

            foreach ($data['prompts'] as $index => $prompt) {
                QcTaskPrompt::create([
                    'task_id' => $task->id,
                    'prompt'  => $prompt,
                    'order'   => $index,
                ]);
            }

            return $task;
        });

        Notification::make()
            ->title('Task created')
            ->body("{$task->title} created with " . count($data['prompts']) . ' prompt(s).')
            ->success()
            ->send();

        return redirect(QCPromptTask::getUrl());
    }
}
