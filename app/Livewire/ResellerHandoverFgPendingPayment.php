<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFg;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class ResellerHandoverFgPendingPayment extends Component
{
    use WithFileUploads;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showCompleteModal = false;
    public $showFilesModal = false;
    public $selectedHandoverId = null;
    public $selectedHandover = null;
    public $handoverFiles = [];
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;
    public $paymentSlip;
    public $sortOverdue = false;

    protected $listeners = ['fg-handover-updated' => '$refresh'];

    public function updatedSearch() {}

    public function sortBy($field)
    {
        if ($field === 'overdue') {
            $this->sortOverdue = !$this->sortOverdue;
            return;
        }

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
            ->where('status', 'pending_reseller_payment')
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FG', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        $handovers = $query->orderBy($this->sortField, $this->sortDirection)->get();

        if ($this->sortOverdue) {
            $handovers = $handovers->sortByDesc(function ($handover) {
                $today = now()->startOfDay();
                $updatedAt = $handover->updated_at->startOfDay();
                return $today->diffInDays($updatedAt);
            });
        }

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

    public function openCompleteModal($handoverId)
    {
        $this->selectedHandoverId = $handoverId;
        $this->selectedHandover = ResellerHandoverFg::find($handoverId);
        $this->showCompleteModal = true;
        $this->paymentSlip = null;
    }

    public function closeCompleteModal()
    {
        $this->showCompleteModal = false;
        $this->selectedHandoverId = null;
        $this->selectedHandover = null;
        $this->paymentSlip = null;
    }

    public function removePaymentSlipFile()
    {
        $this->paymentSlip = null;
    }

    public function completeTask()
    {
        if (!$this->selectedHandover) {
            session()->flash('error', 'Handover not found.');
            return;
        }

        $this->validate([
            'paymentSlip' => 'required|mimes:pdf,jpg,jpeg,png|max:10240',
        ], [
            'paymentSlip.required' => 'Payment slip is required.',
            'paymentSlip.mimes' => 'The file must be a PDF, JPG, JPEG, or PNG.',
            'paymentSlip.max' => 'The file size must not exceed 10MB.',
        ]);

        $paymentSlipPath = $this->paymentSlip->store('reseller-handover-fg/payment-slips', 'public');

        $newStatus = $this->selectedHandover->reseller_option === 'cash_term'
            ? 'pending_timetec_license'
            : 'pending_timetec_finance';

        $this->selectedHandover->update([
            'reseller_payment_slip' => $paymentSlipPath,
            'status' => $newStatus,
            'completed_at' => now(),
        ]);

        $this->selectedHandover->refresh();

        // Send email notification
        if (\App\Mail\ResellerHandoverFgStatusUpdate::shouldSend($this->selectedHandover->status)) {
            try {
                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFgStatusUpdate($this->selectedHandover));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send FG handover email', [
                    'handover_id' => $this->selectedHandover->id,
                    'status' => $newStatus,
                    'error' => $e->getMessage()
                ]);
            }
        }

        session()->flash('message', 'Payment slip uploaded successfully!');
        $this->closeCompleteModal();
        $this->dispatch('fg-handover-updated');
    }

    public function render()
    {
        return view('livewire.reseller-handover-fg-pending-payment', [
            'handovers' => $this->handovers
        ]);
    }
}
