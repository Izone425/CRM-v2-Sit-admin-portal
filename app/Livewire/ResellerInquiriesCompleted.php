<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\ResellerInquiry;
use Illuminate\Support\Facades\Auth;

class ResellerInquiriesCompleted extends Component
{
    public $search = '';
    public $countOnly = false;
    public $showDetailModal = false;
    public $selectedInquiry = null;
    public $showTitleModal = false;
    public $showDescriptionModal = false;
    public $showRemarkModal = false;
    public $showRejectReasonModal = false;

    protected $listeners = [
        'handover-updated' => '$refresh'
    ];

    public function mount($countOnly = false)
    {
        $this->countOnly = $countOnly;
    }

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

    public function getInquiriesProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $query = ResellerInquiry::query()
            ->where('reseller_id', $reseller->reseller_id)
            ->where('status', 'completed');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('subscriber_name', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getCompletedCountProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return 0;
        }

        return ResellerInquiry::where('reseller_id', $reseller->reseller_id)
            ->where('status', 'completed')
            ->count();
    }

    public function render()
    {
        if ($this->countOnly) {
            return view('livewire.reseller-inquiries-completed-count', [
                'count' => $this->completedCount
            ]);
        }

        return view('livewire.reseller-inquiries-completed', [
            'inquiries' => $this->inquiries
        ]);
    }
}
