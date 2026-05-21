<?php

namespace App\Livewire\ResellerDashboard;

use Livewire\Component;
use App\Models\ResellerCommissionHandover;
use Illuminate\Support\Facades\Auth;

class RcPendingResellerTable extends Component
{
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showConfirmModal = false;
    public $selectedHandoverId = null;
    public $showDetailsModal = false;
    public $selectedHandover = null;

    protected $listeners = ['rc-commission-updated' => '$refresh'];

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

        $query = ResellerCommissionHandover::query()
            ->where('status', 'pending_reseller')
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('ap_invoice_no', 'like', '%' . $this->search . '%')
                  ->orWhere('tt_invoice_no', 'like', '%' . $this->search . '%')
                  ->orWhere('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhere('autocount_inv_no', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function openDetailsModal($id)
    {
        $this->selectedHandover = ResellerCommissionHandover::find($id);
        if ($this->selectedHandover) {
            $this->showDetailsModal = true;
        }
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedHandover = null;
    }

    public function openConfirmModal($id)
    {
        $this->selectedHandoverId = $id;
        $this->showConfirmModal = true;
    }

    public function closeConfirmModal()
    {
        $this->showConfirmModal = false;
        $this->selectedHandoverId = null;
    }

    public function proceedConfirmation()
    {
        $handover = ResellerCommissionHandover::find($this->selectedHandoverId);

        if ($handover) {
            $handover->update([
                'status' => 'pending_finance',
                'reseller_proceeded_at' => now(),
            ]);

            session()->flash('message', 'PI No ' . $handover->ap_invoice_no . ' has been proceeded to finance.');
            $this->dispatch('rc-commission-updated');
        }

        $this->closeConfirmModal();
    }

    public function render()
    {
        return view('livewire.reseller-dashboard.rc-pending-reseller-table', [
            'handovers' => $this->handovers,
        ]);
    }
}
