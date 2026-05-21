<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ResellerHandoverFg;

class ResellerRenewalRequestFg extends Component
{
    public $showModal = false;
    public $search = '';
    public $selectedSubscriber = null;
    public $subscriberStatus = 'active';
    public $attendance = 0;
    public $leave = 0;
    public $claim = 0;
    public $payroll = 0;
    public $qf_master = 0;
    public $category = '';
    public $resellerRemark = '';
    public $headcountError = '';
    public $showLicenseModal = false;
    public $licenseDetails = [];
    public $licenseCompanyName = '';

    public function updatedResellerRemark($value)
    {
        $this->resellerRemark = strtoupper($value);
    }

    public function openModal()
    {
        $this->showModal = true;
        $this->resetFields();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetFields();
    }

    public function resetFields()
    {
        $this->search = '';
        $this->selectedSubscriber = null;
        $this->subscriberStatus = 'active';
        $this->attendance = 0;
        $this->leave = 0;
        $this->claim = 0;
        $this->payroll = 0;
        $this->qf_master = 0;
        $this->category = '';
        $this->resellerRemark = '';
        $this->headcountError = '';
    }

    public function selectSubscriber($fId, $companyName)
    {
        $this->selectedSubscriber = [
            'f_id' => $fId,
            'company_name' => strtoupper($companyName)
        ];
        $this->search = strtoupper($companyName);
    }

    public function clearSubscriber()
    {
        $this->selectedSubscriber = null;
        $this->search = '';
    }

    public function getSubscribersProperty()
    {
        $reseller = Auth::guard('reseller')->user();

        if (!$reseller || !$reseller->reseller_id) {
            return collect([]);
        }

        $query = DB::connection('frontenddb')
            ->table('crm_reseller_link')
            ->join('crm_customer', 'crm_reseller_link.f_backend_companyid', '=', 'crm_customer.f_backend_companyid')
            ->select(
                'crm_reseller_link.f_id',
                'crm_reseller_link.f_company_name',
                'crm_customer.f_status'
            )
            ->where('crm_reseller_link.reseller_id', $reseller->reseller_id)
            ->where('crm_reseller_link.f_type', 'SUBSCRIBER');

        if (strlen($this->search) > 0) {
            $query->where('crm_reseller_link.f_company_name', 'like', '%' . $this->search . '%');
        }

        if ($this->subscriberStatus === 'active') {
            $query->where('crm_customer.f_status', 'A');
        } else {
            $query->whereIn('crm_customer.f_status', ['D', 'I', 'T']);
        }

        return $query->orderBy('crm_reseller_link.f_company_name', 'asc')->limit(50)->get();
    }

    public function viewLicense()
    {
        if (!$this->selectedSubscriber) {
            return;
        }

        $fId = (int) $this->selectedSubscriber['f_id'];
        $this->licenseCompanyName = $this->selectedSubscriber['company_name'];

        $reseller = DB::connection('frontenddb')->table('crm_reseller_link')
            ->select('reseller_name', 'f_rate', 'f_id')
            ->where('f_id', $fId)
            ->first();

        $licenses = DB::connection('frontenddb')
            ->table('crm_company_license')
            ->where('f_company_id', $fId)
            ->where('f_type', 'PAID')
            ->where('status', 'Active')
            ->where(function($q) {
                $q->where('f_name', 'like', '%TA%')
                  ->orWhere('f_name', 'like', '%leave%')
                  ->orWhere('f_name', 'like', '%claim%')
                  ->orWhere('f_name', 'like', '%payroll%')
                  ->orWhere('f_name', 'like', '%Face & QR Code%');
            })
            ->orderBy('f_expiry_date', 'asc')
            ->get(['f_name', 'f_total_user', 'f_start_date', 'f_expiry_date', 'f_invoice_no', 'f_billing_cycle', 'status']);

        $invoiceGroups = [];
        $licenseSummary = [
            'attendance' => 0,
            'leave' => 0,
            'claim' => 0,
            'payroll' => 0,
        ];

        foreach ($licenses as $license) {
            $invoiceNo = $license->f_invoice_no ?? 'No Invoice';
            $quantity = $license->f_total_user;

            if (strpos($license->f_name, 'TimeTec TA') !== false) {
                $licenseSummary['attendance'] += $quantity;
            }
            if (strpos($license->f_name, 'TimeTec Leave') !== false) {
                $licenseSummary['leave'] += $quantity;
            }
            if (strpos($license->f_name, 'TimeTec Claim') !== false) {
                $licenseSummary['claim'] += $quantity;
            }
            if (strpos($license->f_name, 'TimeTec Payroll') !== false) {
                $licenseSummary['payroll'] += $quantity;
            }

            if (!isset($invoiceGroups[$invoiceNo])) {
                $invoiceGroups[$invoiceNo] = [
                    'products' => [],
                ];
            }

            $invoiceGroups[$invoiceNo]['products'][] = [
                'f_name' => $license->f_name,
                'f_total_user' => $quantity,
                'f_start_date' => $license->f_start_date,
                'f_expiry_date' => $license->f_expiry_date,
                'billing_cycle' => $license->f_billing_cycle ?? 0,
                'status' => $license->status ?? 'Active',
            ];
        }

        $invoiceGroups['_summary'] = $licenseSummary;
        $this->licenseDetails = $invoiceGroups;
        $this->showLicenseModal = true;
    }

    public function closeLicenseModal()
    {
        $this->showLicenseModal = false;
        $this->licenseDetails = [];
        $this->licenseCompanyName = '';
    }

    public function submitRequest()
    {
        $this->headcountError = '';

        $this->validate([
            'selectedSubscriber' => 'required',
            'category' => 'required|in:renewal_subscription,addon_headcount',
            'attendance' => 'required|integer|min:0',
            'leave' => 'required|integer|min:0',
            'claim' => 'required|integer|min:0',
            'payroll' => 'required|integer|min:0',
            'qf_master' => 'required|integer|min:0',
            'resellerRemark' => 'nullable|string|max:1000',
        ]);

        if ($this->attendance == 0 && $this->leave == 0 && $this->claim == 0 && $this->payroll == 0 && $this->qf_master == 0) {
            $this->headcountError = 'Please enter at least 1 headcount for any product (Attendance, Leave, Claim, Payroll, or QF Master).';
            return;
        }

        $reseller = Auth::guard('reseller')->user();

        ResellerHandoverFg::create([
            'reseller_id' => $reseller->reseller_id,
            'reseller_name' => $reseller->name,
            'reseller_company_name' => $reseller->company_name ?? '',
            'subscriber_id' => $this->selectedSubscriber['f_id'],
            'subscriber_name' => $this->selectedSubscriber['company_name'],
            'subscriber_status' => $this->subscriberStatus === 'active' ? 'A' : 'I',
            'category' => $this->category,
            'attendance_qty' => $this->attendance,
            'leave_qty' => $this->leave,
            'claim_qty' => $this->claim,
            'payroll_qty' => $this->payroll,
            'qf_master_qty' => $this->qf_master,
            'reseller_remark' => $this->resellerRemark,
            'reseller_option' => 'cash_term_without_payment',
            'status' => 'new',
        ]);

        $this->dispatch('fg-handover-updated');
        $this->dispatch('notify', message: 'FG request submitted successfully!', type: 'success');
        $this->closeModal();
    }

    public function render()
    {
        return view('livewire.reseller-renewal-request-fg', [
            'subscribers' => $this->subscribers
        ]);
    }
}
