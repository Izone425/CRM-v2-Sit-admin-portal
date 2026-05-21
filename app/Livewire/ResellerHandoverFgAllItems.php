<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFg;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ResellerHandoverFgAllItems extends Component
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

    protected $listeners = ['fg-handover-updated' => '$refresh'];

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

        $query = ResellerHandoverFg::query()
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FG', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $handovers = $query->orderBy($this->sortField, $this->sortDirection)->get();

        return $handovers;
    }

    public function openFilesModal($recordId)
    {
        $this->selectedHandover = ResellerHandoverFg::find($recordId);
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
        return view('livewire.reseller-handover-fg-all-items', [
            'handovers' => $this->handovers
        ]);
    }
}
