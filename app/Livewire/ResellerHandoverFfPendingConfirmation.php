<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFf;
use Illuminate\Support\Facades\Auth;

class ResellerHandoverFfPendingConfirmation extends Component
{
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showCancelModal = false;
    public $selectedHandoverId = null;
    public $showPaymentReceivedModal = false;
    public $paymentReceivedName = '';
    public $checkingHandoverIds = [];
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;

    protected $listeners = ['ff-handover-updated' => '$refresh'];

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

        $query = ResellerHandoverFf::query()
            ->where('status', 'pending_quotation_confirmation')
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FF', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function markCheckingPayment($handoverId)
    {
        $handover = ResellerHandoverFf::find($handoverId);
        if ($handover) {
            $handover->update(['payment_clicked_at' => now()]);
            if (!in_array($handoverId, $this->checkingHandoverIds)) {
                $this->checkingHandoverIds[] = $handoverId;
            }
        }
    }

    public function checkPaymentStatus()
    {
        if ($this->showPaymentReceivedModal) {
            return;
        }

        if (empty($this->checkingHandoverIds)) {
            $reseller = Auth::guard('reseller')->user();
            if ($reseller) {
                $checking = ResellerHandoverFf::where('reseller_id', $reseller->reseller_id)
                    ->where('status', 'pending_quotation_confirmation')
                    ->whereNotNull('payment_clicked_at')
                    ->where('payment_clicked_at', '>=', now()->subMinutes(1))
                    ->pluck('id')
                    ->toArray();
                $this->checkingHandoverIds = $checking;
            }
        }

        if (empty($this->checkingHandoverIds)) {
            return;
        }

        $completed = ResellerHandoverFf::whereIn('id', $this->checkingHandoverIds)
            ->where('status', 'completed')
            ->first();

        if ($completed) {
            $this->paymentReceivedName = $completed->subscriber_name;
            $this->showPaymentReceivedModal = true;
            $this->checkingHandoverIds = array_values(array_diff($this->checkingHandoverIds, [$completed->id]));
            $this->dispatch('ff-handover-updated');
        }
    }

    public function closePaymentReceivedModal()
    {
        $this->showPaymentReceivedModal = false;
        $this->paymentReceivedName = '';
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
            $handover = ResellerHandoverFf::find($this->selectedHandoverId);
            if ($handover) {
                $handover->update(['status' => 'inactive']);
                session()->flash('message', 'Order has been cancelled.');
            }
        }
        $this->closeCancelModal();
        $this->dispatch('ff-handover-updated');
    }

    public function openFilesModal($handoverId)
    {
        $this->selectedHandover = ResellerHandoverFf::find($handoverId);
        if ($this->selectedHandover) {
            $this->showFilesModal = true;
        }
    }

    public function closeFilesModal()
    {
        $this->showFilesModal = false;
        $this->selectedHandover = null;
    }

    public function render()
    {
        return view('livewire.reseller-handover-ff-pending-confirmation', [
            'handovers' => $this->handovers
        ]);
    }
}
