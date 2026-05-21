<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFe;
use Illuminate\Support\Facades\Auth;

class ResellerHandoverFePendingConfirmation extends Component
{
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showConfirmModal = false;
    public $showCancelModal = false;
    public $selectedHandoverId = null;
    public $showRemarkModal = false;
    public $remarkContent = '';
    public $showAdminRemarkModal = false;
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];

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

        $query = ResellerHandoverFe::query()
            ->where('status', 'pending_quotation_confirmation')
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FE', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
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
            $handover = ResellerHandoverFe::find($this->selectedHandoverId);
            if ($handover) {
                $handover->update(['status' => 'inactive']);
                session()->flash('message', 'Order has been cancelled.');
            }
        }
        $this->closeCancelModal();
        $this->dispatch('fe-handover-updated');
    }

    public function proceedConfirmation()
    {
        if ($this->selectedHandoverId) {
            $handover = ResellerHandoverFe::find($this->selectedHandoverId);
            if ($handover) {
                $handover->update([
                    'status' => 'pending_timetec_invoice',
                    'confirmed_proceed_at' => now(),
                ]);

                // Send email notification
                if (\App\Mail\ResellerHandoverFeStatusUpdate::shouldSend($handover->status)) {
                    try {
                        \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFeStatusUpdate($handover));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send FE handover email', [
                            'handover_id' => $handover->id,
                            'status' => 'pending_timetec_invoice',
                            'error' => $e->getMessage()
                        ]);
                    }
                }

                session()->flash('message', 'Handover confirmed and sent to TimeTec for invoicing.');
            }
        }
        $this->closeConfirmModal();
        $this->dispatch('fe-handover-updated');
    }

    public function render()
    {
        return view('livewire.reseller-handover-fe-pending-confirmation', [
            'handovers' => $this->handovers
        ]);
    }
}
