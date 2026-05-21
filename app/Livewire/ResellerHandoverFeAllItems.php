<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFe;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResellerHandoverFeAllItems extends Component
{
    public $search = '';
    public $sortField = 'updated_at';
    public $sortDirection = 'desc';
    public $statusFilter = '';
    public $activeFilter = 'all';
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;

    protected $listeners = ['fe-handover-updated' => '$refresh'];

    public function updatedSearch() {}

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function getHandoversProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $customerStatuses = DB::connection('frontenddb')
            ->table('crm_customer')
            ->pluck('f_status', 'f_company_name')
            ->toArray();

        $query = ResellerHandoverFe::query()
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FE', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $handovers = $query->orderBy($this->sortField, $this->sortDirection)->get();

        if ($this->activeFilter === 'active') {
            $handovers = $handovers->filter(function($handover) use ($customerStatuses) {
                return isset($customerStatuses[$handover->subscriber_name]) &&
                       $customerStatuses[$handover->subscriber_name] === 'A';
            });
        } elseif ($this->activeFilter === 'inactive') {
            $handovers = $handovers->filter(function($handover) use ($customerStatuses) {
                return isset($customerStatuses[$handover->subscriber_name]) &&
                       in_array($customerStatuses[$handover->subscriber_name], ['D', 'I', 'T']);
            });
        }

        return $handovers;
    }

    public function openFilesModal($handoverId)
    {
        $this->selectedHandover = ResellerHandoverFe::find($handoverId);
        if ($this->selectedHandover) {
            $this->handoverFiles = $this->selectedHandover->getCategorizedFilesForModal();
            $this->showFilesModal = true;
        }
    }

    public function closeFilesModal()
    {
        $this->showFilesModal = false;
        $this->selectedHandover = null;
        $this->handoverFiles = [];
    }

    public function render()
    {
        return view('livewire.reseller-handover-fe-all-items', [
            'handovers' => $this->handovers
        ]);
    }
}
