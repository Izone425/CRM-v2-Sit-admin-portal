<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerInquiry;
use Illuminate\Support\Facades\Auth;

class ResellerInquiriesRejected extends Component
{
    public $search = '';
    public $showDetailModal = false;
    public $selectedInquiry = null;
    public $showTitleModal = false;
    public $showDescriptionModal = false;
    public $showRemarkModal = false;
    public $showRejectReasonModal = false;

    protected $listeners = [
        'handover-updated' => '$refresh'
    ];

    public function updatedSearch()
    {
        // Search updated
    }

    public function openDetailModal($inquiryId)
    {
        $this->selectedInquiry = ResellerInquiry::find($inquiryId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedInquiry = null;
    }

    public function convertToDraft($inquiryId)
    {
        $inquiry = ResellerInquiry::find($inquiryId);

        if ($inquiry && $inquiry->status === 'rejected') {
            $inquiry->update([
                'status' => 'draft',
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Inquiry converted to draft successfully!'
            ]);

            $this->dispatch('handover-updated');
        }
    }

    public function getInquiriesProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $query = ResellerInquiry::query()
            ->where('reseller_id', $reseller->reseller_id)
            ->where('status', 'rejected');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function render()
    {
        return view('livewire.reseller-inquiries-rejected', [
            'inquiries' => $this->inquiries
        ]);
    }
}
