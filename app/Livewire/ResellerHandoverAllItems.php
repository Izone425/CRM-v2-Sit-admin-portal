<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandover;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResellerHandoverAllItems extends Component
{
    public $search = '';
    public $sortField = 'updated_at';
    public $sortDirection = 'desc';
    public $statusFilter = ''; // Single status filter
    public $activeFilter = 'all'; // 'all', 'active', 'inactive'
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;

    protected $listeners = ['handover-updated' => '$refresh'];

    public function updatedSearch()
    {
        // Search updated
    }

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

        // Get customer statuses from frontenddb
        $customerStatuses = DB::connection('frontenddb')
            ->table('crm_customer')
            ->pluck('f_status', 'f_company_name')
            ->toArray();

        $query = ResellerHandover::query()
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FB_', SUBSTRING(YEAR(created_at), 3, 2), LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $handovers = $query->orderBy($this->sortField, $this->sortDirection)->get();

        // Apply active/inactive filter based on crm_customer.f_status
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
        $this->selectedHandover = ResellerHandover::find($handoverId);

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
        return view('livewire.reseller-handover-all-items', [
            'handovers' => $this->handovers
        ]);
    }
}
