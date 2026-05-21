<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\ResellerInstallationPayment;
use App\Models\User;
use App\Mail\InstallationPaymentNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ResellerInstallationPaymentButton extends Component
{
    use WithFileUploads;

    public $showModal = false;
    public $attentionTo = '';
    public $customerName = '';
    public $installationDate = '';
    public $installationAddress = '';
    public $quotations = [];
    public $invoices = [];
    public $salespersons = [];

    protected $rules = [
        'attentionTo' => 'required',
        'customerName' => 'required|string|max:255',
        'installationDate' => 'required|date_format:d/m/Y',
        'installationAddress' => 'required|string',
        'quotations' => 'required|array|min:1',
        'quotations.*' => 'file|mimes:pdf,xlsx,xls,jpg,jpeg,png|max:10240',
        'invoices' => 'required|array|min:1',
        'invoices.*' => 'file|mimes:pdf,xlsx,xls,jpg,jpeg,png|max:10240',
    ];

    protected $messages = [
        'quotations.required' => 'Please upload at least one quotation file.',
        'quotations.min' => 'Please upload at least one quotation file.',
        'invoices.required' => 'Please upload at least one invoice file.',
        'invoices.min' => 'Please upload at least one invoice file.',
    ];

    public function mount()
    {
        $this->loadSalespersons();
    }

    public function loadSalespersons()
    {
        $this->salespersons = User::where('role_id', 2)
            ->where('is_active', 1)
            ->whereNotIn('id', [15, 21, 25])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function removeQuotation($index)
    {
        if (isset($this->quotations[$index])) {
            unset($this->quotations[$index]);
            $this->quotations = array_values($this->quotations);
        }
    }

    public function removeInvoice($index)
    {
        if (isset($this->invoices[$index])) {
            unset($this->invoices[$index]);
            $this->invoices = array_values($this->invoices);
        }
    }

    public function resetForm()
    {
        $this->attentionTo = '';
        $this->customerName = '';
        $this->installationDate = '';
        $this->installationAddress = '';
        $this->quotations = [];
        $this->invoices = [];
        $this->resetValidation();
    }

    public function submitPayment()
    {
        $this->validate();

        $reseller = Auth::guard('reseller')->user();

        // Upload quotation files
        $quotationPaths = [];
        foreach ($this->quotations as $file) {
            if ($file) {
                $quotationPaths[] = $file->store('installation-payment-attachments', 'public');
            }
        }

        // Upload invoice files
        $invoicePaths = [];
        foreach ($this->invoices as $file) {
            if ($file) {
                $invoicePaths[] = $file->store('installation-payment-attachments', 'public');
            }
        }

        $payment = ResellerInstallationPayment::create([
            'reseller_id' => $reseller->reseller_id,
            'reseller_name' => $reseller->name,
            'attention_to' => $this->attentionTo,
            'customer_name' => strtoupper($this->customerName),
            'installation_date' => \Carbon\Carbon::createFromFormat('d/m/Y', $this->installationDate)->format('Y-m-d'),
            'installation_address' => strtoupper($this->installationAddress),
            'quotation_path' => !empty($quotationPaths) ? json_encode($quotationPaths) : null,
            'invoice_path' => !empty($invoicePaths) ? json_encode($invoicePaths) : null,
            'status' => 'new',
        ]);

        // Send email to salesperson
        try {
            $salesperson = User::find($this->attentionTo);
            if ($salesperson && $salesperson->email) {
                $resellerCompanyName = DB::connection('frontenddb')
                    ->table('crm_reseller_link')
                    ->where('reseller_id', $reseller->reseller_id)
                    ->value('reseller_name') ?? 'N/A';

                Mail::to($salesperson->email)
                    ->send(new InstallationPaymentNotification($payment, $resellerCompanyName));
            }
        } catch (\Exception $e) {
            Log::error('Installation payment email failed: ' . $e->getMessage(), [
                'file' => $e->getFile() . ':' . $e->getLine(),
                'payment_id' => $payment->id ?? null,
                'attention_to' => $this->attentionTo,
            ]);
        }

        $this->closeModal();

        $this->dispatch('installation-payment-updated');

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Installation payment submitted successfully!'
        ]);
    }

    public function render()
    {
        return view('livewire.reseller-installation-payment-button');
    }
}
