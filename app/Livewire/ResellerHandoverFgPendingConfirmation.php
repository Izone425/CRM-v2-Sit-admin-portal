<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFg;
use Illuminate\Support\Facades\Auth;

class ResellerHandoverFgPendingConfirmation extends Component
{
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showConfirmModal = false;
    public $showCancelModal = false;
    public $showFilesModal = false;
    public $selectedHandoverId = null;
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
            ->where('status', 'pending_quotation_confirmation')
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FG', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
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

    public function openConfirmModal($handoverId)
    {
        $this->selectedHandoverId = $handoverId;
        $this->showConfirmModal = true;
    }

    public function closeConfirmModal()
    {
        $this->showConfirmModal = false;
        $this->selectedHandoverId = null;
    }

    public function openCancelModal($handoverId)
    {
        $this->selectedHandoverId = $handoverId;
        $this->showCancelModal = true;
    }

    public function closeCancelModal()
    {
        $this->showCancelModal = false;
        $this->selectedHandoverId = null;
    }

    public function cancelOrder()
    {
        if ($this->selectedHandoverId) {
            $handover = ResellerHandoverFg::find($this->selectedHandoverId);
            if ($handover) {
                $handover->update(['status' => 'inactive']);
                session()->flash('message', 'Order has been cancelled.');
            }
        }
        $this->closeCancelModal();
        $this->dispatch('fg-handover-updated');
    }

    public function proceedConfirmation()
    {
        if ($this->selectedHandoverId) {
            $handover = ResellerHandoverFg::find($this->selectedHandoverId);
            if ($handover) {
                $handover->update([
                    'status' => 'pending_timetec_invoice',
                    'confirmed_proceed_at' => now(),
                ]);

                session()->flash('message', 'Handover confirmed and sent to TimeTec for invoicing.');
            }
        }
        $this->closeConfirmModal();
        $this->dispatch('fg-handover-updated');
    }

    public function render()
    {
        return view('livewire.reseller-handover-fg-pending-confirmation', [
            'handovers' => $this->handovers
        ]);
    }
}
