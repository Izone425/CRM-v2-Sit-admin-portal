<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFg;
use Illuminate\Support\Facades\Auth;
class ResellerHandoverFgPendingInvoiceConfirmation extends Component
{
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showCompleteModal = false;
    public $selectedHandoverId = null;
    public $selectedHandover = null;
    public $showFilesModal = false;
    public $handoverFiles = [];

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
            ->where('status', 'pending_invoice_confirmation')
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FG', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function openCompleteModal($handoverId)
    {
        $this->selectedHandoverId = $handoverId;
        $this->selectedHandover = ResellerHandoverFg::find($handoverId);
        $this->showCompleteModal = true;
    }

    public function closeCompleteModal()
    {
        $this->showCompleteModal = false;
        $this->selectedHandoverId = null;
        $this->selectedHandover = null;
    }

    public function openFilesModal($handoverId)
    {
        $handover = ResellerHandoverFg::find($handoverId);

        if ($handover) {
            $this->selectedHandover = $handover;
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

    public function completeTask()
    {
        if (!$this->selectedHandover) {
            session()->flash('error', 'Handover not found.');
            return;
        }

        // Determine next status based on reseller_option
        $nextStatus = match ($this->selectedHandover->reseller_option) {
            'cash_term_without_payment' => 'pending_timetec_license',
            default => 'pending_reseller_payment',
        };

        $this->selectedHandover->update([
            'status' => $nextStatus,
            'rni_submitted_at' => now(),
        ]);

        if (class_exists(\App\Mail\ResellerHandoverFgStatusUpdate::class) && \App\Mail\ResellerHandoverFgStatusUpdate::shouldSend($this->selectedHandover->status)) {
            try {
                \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFgStatusUpdate($this->selectedHandover));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send FG handover email', [
                    'handover_id' => $this->selectedHandover->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        session()->flash('message', 'Invoice confirmed successfully!');
        $this->closeCompleteModal();
        $this->dispatch('fg-handover-updated');
    }

    public function render()
    {
        return view('livewire.reseller-handover-fg-pending-invoice-confirmation', [
            'handovers' => $this->handovers
        ]);
    }
}
