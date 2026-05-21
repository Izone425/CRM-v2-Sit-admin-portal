<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFe;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class ResellerHandoverFePendingPayment extends Component
{
    use WithFileUploads;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showFilesModal = false;
    public $selectedHandover = null;
    public $handoverFiles = [];
    public $showCompleteModal = false;
    public $selectedHandoverId = null;
    public $paymentSlip;
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;
    public $sortOverdue = false;

    protected $listeners = ['fe-handover-updated' => '$refresh'];

    public function updatedSearch()
    {
        // Search updated
    }

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

        $query = ResellerHandoverFe::query()
            ->where('status', 'pending_reseller_payment')
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FE', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        $handovers = $query->orderBy($this->sortField, $this->sortDirection)->get();

        // Apply overdue sorting if needed
        if ($this->sortOverdue) {
            $handovers = $handovers->sortByDesc(function ($handover) {
                $today = now()->startOfDay();
                $updatedAt = $handover->updated_at->startOfDay();
                return $today->diffInDays($updatedAt);
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

    public function openCompleteModal($handoverId)
    {
        $this->selectedHandoverId = $handoverId;
        $this->selectedHandover = ResellerHandoverFe::find($handoverId);
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

        // Store single payment slip file
        $paymentSlipPath = $this->paymentSlip->store('reseller-handover-fe/payment-slips', 'public');

        $newStatus = $this->selectedHandover->reseller_option === 'cash_term'
            ? 'pending_timetec_license'
            : 'pending_finance_payment';

        $this->selectedHandover->update([
            'reseller_payment_slip' => $paymentSlipPath,
            'status' => $newStatus,
            'completed_at' => now(),
        ]);

        // Refresh the model to get updated status
        $this->selectedHandover->refresh();

        // Send email notification
        if (\App\Mail\ResellerHandoverFeStatusUpdate::shouldSend($this->selectedHandover->status)) {
            try {
                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFeStatusUpdate($this->selectedHandover));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send FE handover email', [
                    'handover_id' => $this->selectedHandover->id,
                    'status' => $newStatus,
                    'error' => $e->getMessage()
                ]);
            }
        }

        session()->flash('message', 'Payment slip uploaded successfully!');
        $this->closeCompleteModal();

        // Emit event to refresh
        $this->dispatch('fe-handover-updated');
    }

    public function render()
    {
        return view('livewire.reseller-handover-fe-pending-payment', [
            'handovers' => $this->handovers
        ]);
    }
}
