<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerHandoverFe;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

class ResellerHandoverFePendingInvoiceConfirmation extends Component
{
    use WithFileUploads;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $showCompleteModal = false;
    public $selectedHandoverId = null;
    public $selectedHandover = null;
    public $resellerNormalInvoice;
    public $paymentSlip;
    public $showFilesModal = false;
    public $handoverFiles = [];
    public $showRemarkModal = false;
    public $showAdminRemarkModal = false;

    protected $listeners = ['fe-handover-updated' => '$refresh'];

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

        $query = ResellerHandoverFe::query()
            ->where('status', 'pending_invoice_confirmation')
            ->where('reseller_id', $reseller->reseller_id);

        if ($this->search) {
            $query->where(function($q) {
                $q->where('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhereRaw("CONCAT('FE', LPAD(MONTH(created_at), 2, '0'), '-', LPAD(id, 4, '0')) LIKE ?", ['%' . $this->search . '%']);
            });
        }

        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    public function openCompleteModal($handoverId)
    {
        $this->selectedHandoverId = $handoverId;
        $this->selectedHandover = ResellerHandoverFe::find($handoverId);
        $this->showCompleteModal = true;
        $this->resellerNormalInvoice = null;
        $this->paymentSlip = null;
    }

    public function closeCompleteModal()
    {
        $this->showCompleteModal = false;
        $this->selectedHandoverId = null;
        $this->selectedHandover = null;
        $this->resellerNormalInvoice = null;
        $this->paymentSlip = null;
    }

    public function openFilesModal($handoverId)
    {
        $handover = ResellerHandoverFe::find($handoverId);

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

    public function removeInvoiceFile()
    {
        $this->resellerNormalInvoice = null;
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

        // If cash_term_without_payment, just update status without any file requirements
        if ($this->selectedHandover->reseller_option === 'cash_term_without_payment') {
            $this->selectedHandover->update([
                'status' => 'pending_timetec_license',
                'rni_submitted_at' => now(),
            ]);

            // Send email notification
            if (\App\Mail\ResellerHandoverFeStatusUpdate::shouldSend($this->selectedHandover->status)) {
                try {
                    \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFeStatusUpdate($this->selectedHandover));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send FE handover email', [
                        'handover_id' => $this->selectedHandover->id,
                        'status' => 'pending_timetec_license',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            session()->flash('message', 'Task completed successfully!');
            $this->closeCompleteModal();
            $this->dispatch('fe-handover-updated');
            return;
        }

        // For cash_term, require payment slip
        if ($this->selectedHandover->reseller_option === 'cash_term') {
            $rules['paymentSlip'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:10240';
            $messages['paymentSlip.required'] = 'Payment slip is required.';
            $messages['paymentSlip.file'] = 'The upload must be a valid file.';
            $messages['paymentSlip.mimes'] = 'The file must be a PDF, JPG, JPEG, or PNG.';
            $messages['paymentSlip.max'] = 'The file size must not exceed 10MB.';

            $this->validate($rules, $messages);

            $paymentSlipPath = $this->paymentSlip->store('reseller-handover-fe/payment-slips', 'public');

            $this->selectedHandover->update([
                'reseller_payment_slip' => $paymentSlipPath,
                'status' => 'pending_reseller_payment',
                'rni_submitted_at' => now(),
                'completed_at' => now(),
            ]);

            // Send email notification
            if (\App\Mail\ResellerHandoverFeStatusUpdate::shouldSend($this->selectedHandover->status)) {
                try {
                    \Illuminate\Support\Facades\Mail::send(new \App\Mail\ResellerHandoverFeStatusUpdate($this->selectedHandover));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to send FE handover email', [
                        'handover_id' => $this->selectedHandover->id,
                        'status' => 'pending_reseller_payment',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            session()->flash('message', 'Task completed successfully!');
            $this->closeCompleteModal();
            $this->dispatch('fe-handover-updated');
        }
    }

    public function render()
    {
        return view('livewire.reseller-handover-fe-pending-invoice-confirmation', [
            'handovers' => $this->handovers
        ]);
    }
}
