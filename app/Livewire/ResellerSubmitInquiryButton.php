<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ResellerInquiry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResellerSubmitInquiryButton extends Component
{
    use WithFileUploads;

    public $showModal = false;
    public $subscriberType = 'active';
    public $subscriberId = '';
    public $subscriberName = '';
    public $title = '';
    public $description = '';
    public $attachments = [];
    public $existingAttachments = [];
    public $search = '';
    public $subscribers = [];
    public $draftId = null;
    public $rejectReason = null;
    public $rejectAttachmentPath = null;
    public $rejectedAt = null;

    protected $listeners = [
        'open-inquiry-modal-with-data' => 'openModalWithData'
    ];

    protected $rules = [
        'subscriberType' => 'required|in:active,inactive,internal',
        'title' => 'required|string|max:255',
        'description' => 'required|string',
        'attachments.*' => 'nullable|file|mimes:pdf,xlsx,xls,jpg,jpeg,png|max:10240',
    ];

    public function updatedSubscriberType()
    {
        $this->subscriberId = '';
        $this->subscriberName = '';
        $this->search = '';

        if ($this->subscriberType === 'internal') {
            $reseller = Auth::guard('reseller')->user();
            $this->subscriberName = $reseller->company_name ?? '';
        } else {
            $this->loadSubscribers();
        }
    }

    public function updatedSearch()
    {
        if ($this->subscriberType !== 'internal') {
            $this->loadSubscribers();
        }
    }

    public function loadSubscribers()
    {
        if ($this->subscriberType === 'internal') {
            $this->subscribers = collect([]);
            return;
        }

        $reseller = Auth::guard('reseller')->user();
        if (!$reseller || !$reseller->reseller_id) {
            $this->subscribers = collect([]);
            return;
        }

        $status = $this->subscriberType === 'active' ? 'A' : 'I';

        $query = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->join('crm_customer', 'crm_reseller_link.f_id', '=', 'crm_customer.company_id')
            ->where('crm_reseller_link.reseller_id', $reseller->reseller_id)
            ->where('crm_reseller_link.f_type', 'SUBSCRIBER')
            ->where('crm_customer.f_status', $status);

        if ($this->search) {
            $query->where('crm_reseller_link.f_company_name', 'like', '%' . $this->search . '%');
        }

        $this->subscribers = $query->select('crm_reseller_link.f_id', 'crm_reseller_link.f_company_name')
            ->limit(50)
            ->get();
    }

    public function selectSubscriber($fId, $companyName)
    {
        $this->subscriberId = $fId;
        $this->subscriberName = $companyName;
        $this->search = '';
        $this->subscribers = collect([]);
    }

    public function clearSubscriber()
    {
        $this->subscriberId = '';
        $this->subscriberName = '';
        $this->search = '';
        $this->loadSubscribers();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;

        if ($this->subscriberType === 'internal') {
            $reseller = Auth::guard('reseller')->user();
            $this->subscriberName = $reseller->company_name ?? '';
        } else {
            $this->loadSubscribers();
        }
    }

    public function openModalWithData($data)
    {
        $this->subscriberType = $data['subscriberType'] ?? 'active';
        $this->subscriberId = $data['subscriberId'] ?? '';
        $this->subscriberName = $data['subscriberName'] ?? '';
        $this->title = $data['title'] ?? '';
        $this->description = $data['description'] ?? '';
        $this->draftId = $data['draftId'] ?? null;
        $this->rejectReason = $data['rejectReason'] ?? null;
        $this->rejectAttachmentPath = $data['rejectAttachmentPath'] ?? null;
        $this->rejectedAt = $data['rejectedAt'] ?? null;

        // Handle existing attachments as array
        $attachmentPath = $data['attachmentPath'] ?? null;
        if ($attachmentPath) {
            $this->existingAttachments = is_array(json_decode($attachmentPath, true)) ? json_decode($attachmentPath, true) : [$attachmentPath];
        } else {
            $this->existingAttachments = [];
        }

        $this->attachments = [];
        $this->search = '';
        $this->subscribers = collect([]);
        $this->showModal = true;

        if ($this->subscriberType !== 'internal') {
            $this->loadSubscribers();
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function removeExistingAttachment($index)
    {
        if (isset($this->existingAttachments[$index])) {
            unset($this->existingAttachments[$index]);
            $this->existingAttachments = array_values($this->existingAttachments);
        }
    }

    public function removeAttachment($index)
    {
        if (isset($this->attachments[$index])) {
            unset($this->attachments[$index]);
            $this->attachments = array_values($this->attachments);
        }
    }

    public function resetForm()
    {
        $this->subscriberType = 'active';
        $this->subscriberId = '';
        $this->subscriberName = '';
        $this->title = '';
        $this->description = '';
        $this->attachments = [];
        $this->existingAttachments = [];
        $this->search = '';
        $this->subscribers = collect([]);
        $this->draftId = null;
        $this->rejectReason = null;
        $this->rejectAttachmentPath = null;
        $this->rejectedAt = null;
        $this->resetValidation();
    }

    public function submitInquiry()
    {
        $this->validate();

        if ($this->subscriberType !== 'internal' && empty($this->subscriberId)) {
            $this->addError('subscriberId', 'Please select a subscriber.');
            return;
        }

        $reseller = Auth::guard('reseller')->user();

        // Handle multiple file uploads
        $attachmentPaths = [];

        // Add existing attachments
        if (!empty($this->existingAttachments)) {
            $attachmentPaths = array_merge($attachmentPaths, $this->existingAttachments);
        }

        // Upload new attachments
        if (!empty($this->attachments)) {
            foreach ($this->attachments as $attachment) {
                if ($attachment) {
                    $attachmentPaths[] = $attachment->store('inquiry-attachments', 'public');
                }
            }
        }

        // Store as JSON if multiple, single string if one, null if none
        $attachmentPath = null;
        if (count($attachmentPaths) > 1) {
            $attachmentPath = json_encode($attachmentPaths);
        } elseif (count($attachmentPaths) === 1) {
            $attachmentPath = $attachmentPaths[0];
        }

        // If resubmitting a draft, delete the draft
        if ($this->draftId) {
            $draftInquiry = ResellerInquiry::find($this->draftId);
            if ($draftInquiry && $draftInquiry->status === 'draft') {
                // Delete old attachments that are not in existing attachments
                if ($draftInquiry->attachment_path) {
                    $oldPaths = is_array(json_decode($draftInquiry->attachment_path, true))
                        ? json_decode($draftInquiry->attachment_path, true)
                        : [$draftInquiry->attachment_path];

                    foreach ($oldPaths as $oldPath) {
                        if (!in_array($oldPath, $this->existingAttachments)) {
                            Storage::disk('public')->delete($oldPath);
                        }
                    }
                }
                $draftInquiry->delete();
            }
        }

        // Store inquiry in database using Eloquent model
        ResellerInquiry::create([
            'reseller_id' => $reseller->reseller_id,
            'reseller_name' => $reseller->name,
            'subscriber_type' => $this->subscriberType,
            'subscriber_id' => $this->subscriberType === 'internal' ? null : $this->subscriberId,
            'subscriber_name' => $this->subscriberName,
            'title' => strtoupper($this->title),
            'description' => $this->description,
            'attachment_path' => $attachmentPath,
            'status' => 'new',
            'reject_reason' => $this->rejectReason,
            'reject_attachment_path' => $this->rejectAttachmentPath,
            'rejected_at' => $this->rejectedAt,
        ]);

        $this->closeModal();

        // Dispatch global event to refresh all components (same event as handover)
        $this->dispatch('handover-updated');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Inquiry submitted successfully!'
        ]);
    }

    public function render()
    {
        return view('livewire.reseller-submit-inquiry-button');
    }
}
