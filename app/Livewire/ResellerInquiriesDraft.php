<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerInquiry;
use Illuminate\Support\Facades\Auth;

class ResellerInquiriesDraft extends Component
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

    public function resubmitInquiry($inquiryId)
    {
        $inquiry = ResellerInquiry::find($inquiryId);
        if ($inquiry) {
            $this->dispatch('open-inquiry-modal-with-data', [
                'subscriberType' => $inquiry->subscriber_type,
                'subscriberId' => $inquiry->subscriber_id,
                'subscriberName' => $inquiry->subscriber_name,
                'title' => $inquiry->title,
                'description' => $inquiry->description,
                'attachmentPath' => $inquiry->attachment_path,
                'draftId' => $inquiry->id,
                'rejectReason' => $inquiry->reject_reason,
                'rejectAttachmentPath' => $inquiry->reject_attachment_path,
                'rejectedAt' => $inquiry->rejected_at
            ]);
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
            ->where('status', 'draft');

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
        return view('livewire.reseller-inquiries-draft', [
            'inquiries' => $this->inquiries
        ]);
    }
}
