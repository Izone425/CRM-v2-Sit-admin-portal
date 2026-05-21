<?php

namespace App\Filament\Pages;

use App\Models\QcTask;
use App\Models\QcTaskPrompt;
use App\Models\QcTaskPromptAttachment;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class QCPromptTask extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'QC Prompt Task';
    protected static ?string $title = 'QC Prompt Task';
    protected static ?string $slug = 'qc-prompt-task';
    protected static string $view = 'filament.pages.qc-prompt-task';

    public string $activeTab = 'v1';
    public string $activeModule = '';

    // Complete modal state
    public bool $showCompleteModal = false;
    public ?int $completingPromptId = null;
    public $completionAttachments = []; // array of uploaded files

    // View-task modal (shows all prompts + attachments)
    public bool $showTaskModal = false;
    public ?int $viewingTaskId = null;

    // View-prompt modal (shows one prompt + its attachment)
    public bool $showPromptModal = false;
    public ?int $viewingPromptId = null;

    public function mount(): void
    {
        $this->activeTab = session('qc_prompt_active_tab', 'v1');
        if (!in_array($this->activeTab, ['v1', 'v2'], true)) {
            $this->activeTab = 'v1';
        }

        $allowedModules = QcTask::modulesFor($this->activeTab);
        $this->activeModule = session('qc_prompt_active_module_' . $this->activeTab, $allowedModules[0] ?? '');
        if (!in_array($this->activeModule, $allowedModules, true)) {
            $this->activeModule = $allowedModules[0] ?? '';
        }
    }

    public function setActiveTab(string $tab): void
    {
        $this->activeTab = in_array($tab, ['v1', 'v2'], true) ? $tab : 'v1';
        session(['qc_prompt_active_tab' => $this->activeTab]);

        $allowedModules = QcTask::modulesFor($this->activeTab);
        $saved = session('qc_prompt_active_module_' . $this->activeTab, $allowedModules[0] ?? '');
        $this->activeModule = in_array($saved, $allowedModules, true) ? $saved : ($allowedModules[0] ?? '');
    }

    public function setActiveModule(string $module): void
    {
        $allowed = QcTask::modulesFor($this->activeTab);
        if (in_array($module, $allowed, true)) {
            $this->activeModule = $module;
            session(['qc_prompt_active_module_' . $this->activeTab => $module]);
        }
    }

    public function openCompleteModal(int $promptId): void
    {
        $prompt = QcTaskPrompt::find($promptId);
        if (!$prompt) {
            return;
        }

        $this->completingPromptId = $promptId;
        $this->completionAttachments = [];
        $this->showCompleteModal = true;
    }

    public function closeCompleteModal(): void
    {
        $this->showCompleteModal = false;
        $this->completingPromptId = null;
        $this->completionAttachments = [];
    }

    public function submitComplete(): void
    {
        if (!$this->completingPromptId) {
            return;
        }

        $this->validate([
            'completionAttachments' => 'nullable|array',
            'completionAttachments.*' => 'file|max:10240',
        ]);

        $prompt = QcTaskPrompt::with('attachments')->find($this->completingPromptId);
        if (!$prompt) {
            $this->closeCompleteModal();
            return;
        }

        $prompt->update([
            'status' => QcTaskPrompt::STATUS_COMPLETED,
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        if (!empty($this->completionAttachments)) {
            $nextOrder = (int) ($prompt->attachments()->max('order') ?? -1) + 1;

            foreach ($this->completionAttachments as $file) {
                if (!$file) {
                    continue;
                }
                $original = $file->getClientOriginalName();
                $path = $file->store('qc_task_prompt_attachments/' . date('Y/m'), 'public');

                QcTaskPromptAttachment::create([
                    'prompt_id'     => $prompt->id,
                    'file_path'     => $path,
                    'original_name' => $original,
                    'order'         => $nextOrder++,
                ]);
            }
        }

        $this->closeCompleteModal();

        Notification::make()
            ->title('Prompt completed')
            ->success()
            ->send();
    }

    public function reopenPrompt(int $promptId): void
    {
        $prompt = QcTaskPrompt::find($promptId);
        if (!$prompt) {
            return;
        }

        $prompt->update([
            'status' => QcTaskPrompt::STATUS_PENDING,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        Notification::make()
            ->title('Reopened')
            ->body('Prompt marked as pending.')
            ->success()
            ->send();
    }

    public function openTaskModal(int $taskId): void
    {
        $task = QcTask::find($taskId);
        if (!$task) {
            return;
        }
        $this->viewingTaskId = $taskId;
        $this->showTaskModal = true;
    }

    public function closeTaskModal(): void
    {
        $this->showTaskModal = false;
        $this->viewingTaskId = null;
    }

    public function openPromptModal(int $promptId): void
    {
        $prompt = QcTaskPrompt::find($promptId);
        if (!$prompt) {
            return;
        }
        $this->viewingPromptId = $promptId;
        $this->showPromptModal = true;
    }

    public function closePromptModal(): void
    {
        $this->showPromptModal = false;
        $this->viewingPromptId = null;
    }

    public function deleteTask(int $taskId): void
    {
        $task = QcTask::with('prompts.attachments')->find($taskId);
        if (!$task) {
            return;
        }

        foreach ($task->prompts as $prompt) {
            foreach ($prompt->attachments as $attachment) {
                if ($attachment->file_path && Storage::disk('public')->exists($attachment->file_path)) {
                    Storage::disk('public')->delete($attachment->file_path);
                }
            }
        }

        $task->delete();

        Notification::make()
            ->title('Task deleted')
            ->success()
            ->send();
    }

    public function getAvailableModulesProperty(): array
    {
        return QcTask::modulesFor($this->activeTab);
    }

    public function getCurrentModuleTasksProperty()
    {
        if (!$this->activeModule) {
            return collect();
        }

        return QcTask::with(['prompts.attachments'])
            ->where('hr_version', $this->activeTab)
            ->where('module', $this->activeModule)
            ->orderByDesc('id')
            ->get();
    }

    public function getModuleStatsProperty(): array
    {
        $tasks = QcTask::with('prompts')
            ->where('hr_version', $this->activeTab)
            ->get();

        $stats = [];
        foreach (QcTask::modulesFor($this->activeTab) as $module) {
            $moduleTasks = $tasks->where('module', $module);
            $total = $moduleTasks->sum(fn($t) => $t->prompts->count());
            $done = $moduleTasks->sum(fn($t) => $t->prompts->where('status', 'completed')->count());

            $stats[$module] = [
                'tasks' => $moduleTasks->count(),
                'total_prompts' => $total,
                'done_prompts' => $done,
                'percent' => $total > 0 ? round(($done / $total) * 100) : 0,
            ];
        }

        return $stats;
    }
}
