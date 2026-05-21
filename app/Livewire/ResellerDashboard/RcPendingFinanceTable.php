<?php

namespace App\Livewire\ResellerDashboard;

use Livewire\Component;
use App\Models\ResellerCommissionHandover;
use Illuminate\Support\Facades\Auth;

class RcPendingFinanceTable extends Component
{
    public $search = '';
    public $sortField = 'reseller_proceeded_at';
    public $sortDirection = 'desc';
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
            ->where('status', 'pending_finance')
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

    public function render()
    {
        return view('livewire.reseller-dashboard.rc-pending-finance-table', [
            'handovers' => $this->handovers,
        ]);
    }
}
