<?php

namespace App\Livewire;

use App\Models\Ticketing\Product;
use App\Models\Ticketing\Release;
use App\Models\Ticketing\Solution;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class CreateReleaseDrawer extends Component
{
    public bool $showDrawer = false;
    public ?int $product_id = null;
    public ?int $solution_id = null;
    public ?int $module_id = null;
    public ?string $platform = null;
    public string $version = '';
    public string $status = 'Planned';
    public ?string $planned_live_date = null;

    protected $listeners = ['openCreateReleaseModal' => 'openDrawer'];

    public function render()
    {
        return view('livewire.create-release-drawer');
    }

    public function openDrawer(): void
    {
        $this->reset(['product_id', 'solution_id', 'module_id', 'platform', 'version', 'planned_live_date']);
        $this->status = 'Planned';
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function getProductsProperty() { return Product::where('is_active', 1)->whereIn('id', [1, 2])->orderBy('name')->get(['id', 'name']); }
    public function getSolutionsProperty() { return Solution::where('is_active', 1)->orderBy('name')->get(['id', 'name']); }

    public function updatedProductId(): void
    {
        $this->module_id = null;
    }

    public function getModulesProperty()
    {
        if (!$this->product_id) {
            return collect();
        }
        return DB::connection('ticketingsystem_live')
            ->table('product_has_modules')
            ->join('modules', 'product_has_modules.module_id', '=', 'modules.id')
            ->where('product_has_modules.product_id', $this->product_id)
            ->where('modules.is_active', 1)
            ->orderBy('modules.name')
            ->get(['modules.id', 'modules.name']);
    }
    public function getPlatformOptionsProperty(): array { return ['Web', 'App', 'Others']; }
    public function getStatusOptionsProperty(): array { return ['Planned', 'In Development', 'Live']; }

    public function submit(): void
    {
        $this->validate([
            'product_id' => 'required|integer',
            'module_id' => 'required|integer',
            'platform' => 'required|string',
            'version' => 'required|string|max:50',
            'planned_live_date' => 'required|date',
        ], [
            'product_id.required' => 'Product is required.',
            'module_id.required' => 'Module is required.',
            'platform.required' => 'Platform is required.',
            'version.required' => 'Version is required.',
            'planned_live_date.required' => 'Planned live date is required.',
        ]);

        try {
            $release = Release::create([
                'product_id' => $this->product_id,
                'solution_id' => $this->solution_id,
                'module_id' => $this->module_id,
                'platform' => $this->platform,
                'version' => $this->version,
                'status' => $this->status,
                'is_locked' => false,
                'planned_live_date' => $this->planned_live_date ?: null,
            ]);

            Notification::make()->title("Release {$release->version} created")->success()->send();
            $this->closeDrawer();
            $this->dispatch('release-created', $release->id);
        } catch (\Exception $e) {
            Log::error('Create release failed: ' . $e->getMessage());
            Notification::make()->title('Failed to create release')->body($e->getMessage())->danger()->send();
        }
    }
}
