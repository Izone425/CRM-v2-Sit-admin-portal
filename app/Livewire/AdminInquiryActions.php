<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ResellerInquiry;
use Illuminate\Support\Facades\Storage;

class AdminInquiryActions extends Component
{
    use WithFileUploads;

    public $inquiry;
    public $showCompleteModal = false;
    public $showRejectModal = false;
    public $adminRemark = '';
    public $adminAttachment;
    public $rejectReason = '';

    protected $listeners = ['openCompleteModal', 'openRejectModal'];

    public function mount($inquiryId = null)
    {
        if ($inquiryId) {
            $this->inquiry = ResellerInquiry::find($inquiryId);
        }
    }

    public function openCompleteModal($inquiryId)
    {
        $this->inquiry = ResellerInquiry::find($inquiryId);
        $this->adminRemark = $this->inquiry->admin_remark ?? '';
        $this->showCompleteModal = true;
    }

    public function closeCompleteModal()
    {
        $this->showCompleteModal = false;
        $this->adminRemark = '';
        $this->adminAttachment = null;
        $this->resetValidation();
    }

    public function openRejectModal($inquiryId)
    {
        $this->inquiry = ResellerInquiry::find($inquiryId);
        $this->rejectReason = '';
        $this->showRejectModal = true;
    }

    public function closeRejectModal()
    {
        $this->showRejectModal = false;
        $this->rejectReason = '';
        $this->resetValidation();
    }

    public function completeInquiry()
    {
        $this->validate([
            'adminRemark' => 'nullable|string',
            'adminAttachment' => 'nullable|file|mimes:pdf,xlsx,xls,jpg,jpeg,png|max:10240',
        ]);

        $attachmentPath = null;
        if ($this->adminAttachment) {
            $attachmentPath = $this->adminAttachment->store('inquiry-attachments/admin', 'public');
        }

        $this->inquiry->update([
            'status' => 'completed',
            'admin_remark' => strtoupper($this->adminRemark),
            'admin_attachment_path' => $attachmentPath ?? $this->inquiry->admin_attachment_path,
            'completed_at' => now(),
        ]);

        $this->closeCompleteModal();

        $this->dispatch('inquiry-updated');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Inquiry completed successfully!'
        ]);
    }

    public function rejectInquiry()
    {
        $this->validate([
            'rejectReason' => 'required|string|max:1000',
        ]);

        $this->inquiry->update([
            'status' => 'rejected',
            'reject_reason' => strtoupper($this->rejectReason),
            'rejected_at' => now(),
        ]);

        $this->closeRejectModal();

        $this->dispatch('inquiry-updated');
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Inquiry rejected successfully!'
        ]);
    }

    public function render()
    {
        return view('livewire.admin-inquiry-actions');
    }
}
