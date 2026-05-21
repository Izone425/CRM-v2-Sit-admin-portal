<?php

namespace App\Livewire;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketLog;
use App\Models\TicketingUser;
use App\Models\TicketPriority;
use App\Services\TicketNotificationService;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithFileUploads;

class CreateTicketDrawer extends Component implements HasForms
{
    use InteractsWithForms;
    use WithFileUploads;

    public bool $showDrawer = false;

    public ?int $priority_id = null;
    public ?int $product_id = null;
    public ?int $module_id = null;
    public ?int $parent_ticket_id = null;
    public ?string $device_type = null;
    public ?string $mobile_type = null;
    public ?string $browser_type = null;
    public $version_screenshot = null;
    public string $zoho_id = '';
    public string $company_name = '';
    public $invoice = null;
    public $payment_slip = null;
    public string $title = '';
    public string $description = '';

    protected $listeners = [
        'openCreateTicketModal' => 'openDrawer',
    ];

    public function render()
    {
        return view('livewire.create-ticket-drawer');
    }

    public function openDrawer(): void
    {
        $this->reset([
            'priority_id', 'product_id', 'module_id', 'parent_ticket_id',
            'device_type', 'mobile_type', 'browser_type', 'version_screenshot',
            'zoho_id', 'company_name', 'invoice', 'payment_slip', 'title', 'description',
        ]);
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function updatedProductId(): void
    {
        $this->module_id = null;
    }

    public function updatedPriorityId(): void
    {
        $this->parent_ticket_id = null;
        $this->device_type = null;
        $this->mobile_type = null;
        $this->browser_type = null;
        $this->version_screenshot = null;
        $this->invoice = null;
        $this->payment_slip = null;
    }

    public function updatedDeviceType(): void
    {
        $this->mobile_type = null;
        $this->browser_type = null;
        $this->version_screenshot = null;
    }

    public function getPrioritiesProperty()
    {
        $authUser = auth()->user();
        $priorities = TicketPriority::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('sort_order_suffix')
            ->get();

        if ($authUser && $authUser->role_id == 2) {
            $restricted = ['SOFTWARE BUGS', 'BACK END ASSISTANCE', 'PAID CUSTOMIZATION'];
            $priorities = $priorities->filter(function ($p) use ($restricted) {
                foreach ($restricted as $r) {
                    if (str_contains(strtoupper($p->name), $r)) return false;
                }
                return true;
            });
        }

        $priorities = $priorities->filter(fn ($p) => ! str_ends_with(strtoupper((string) $p->sort_order_suffix), 'F'));

        return $priorities->map(function ($p) {
            $label = 'P' . $p->sort_order . ($p->sort_order_suffix ?: '') . ' - ' . $p->name;
            return (object) ['id' => $p->id, 'label' => $label];
        })->values();
    }

    public function getProductOptionsProperty(): array
    {
        return [1 => 'TimeTec HR - Version 1', 2 => 'TimeTec HR - Version 2'];
    }

    public function getModulesProperty()
    {
        if (! $this->product_id) return collect();
        return DB::connection('ticketingsystem_live')
            ->table('product_has_modules')
            ->join('modules', 'product_has_modules.module_id', '=', 'modules.id')
            ->where('product_has_modules.product_id', $this->product_id)
            ->where('modules.is_active', true)
            ->orderBy('product_has_modules.id')
            ->get(['modules.id', 'modules.name']);
    }

    public function getParentTicketsProperty()
    {
        $p4a = TicketPriority::where('is_active', true)
            ->where('sort_order', 4)
            ->where('sort_order_suffix', 'a')
            ->first();
        if (! $p4a) return collect();
        return Ticket::where('priority_id', $p4a->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get(['id', 'ticket_id', 'title']);
    }

    public function getCompanyOptionsProperty()
    {
        return DB::connection('frontenddb')
            ->table('crm_expiring_license')
            ->select('f_company_name')
            ->groupBy('f_company_name')
            ->orderBy('f_company_name')
            ->limit(500)
            ->pluck('f_company_name')
            ->map(fn ($n) => ['value' => $n, 'label' => strtoupper($n)])
            ->values();
    }

    public function getCurrentPriorityProperty(): ?TicketPriority
    {
        return $this->priority_id ? TicketPriority::find($this->priority_id) : null;
    }

    public function getIsP4bProperty(): bool
    {
        $p = $this->currentPriority;
        return $p && $p->sort_order == 4 && $p->sort_order_suffix == 'b';
    }

    public function getIsBackEndAssistanceProperty(): bool
    {
        $p = $this->currentPriority;
        return $p && str_contains(strtolower($p->name), 'back end assistance');
    }

    public function getIsSoftwareBugsProperty(): bool
    {
        $p = $this->currentPriority;
        return $p && str_contains(strtolower($p->name), 'software bugs');
    }

    public function getDeviceTypeVisibleProperty(): bool
    {
        return $this->isBackEndAssistance || $this->isSoftwareBugs;
    }

    public function getMobileFieldsVisibleProperty(): bool
    {
        return $this->isSoftwareBugs && $this->device_type === 'Mobile';
    }

    public function getBrowserFieldsVisibleProperty(): bool
    {
        return $this->isSoftwareBugs && $this->device_type === 'Browser';
    }

    public function submit(): void
    {
        $rules = [
            'priority_id' => 'required|integer',
            'product_id' => 'required|integer|in:1,2',
            'module_id' => 'required|integer',
            'zoho_id' => 'required|string|max:255',
            'company_name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ];

        if ($this->isP4b) {
            $rules['parent_ticket_id'] = 'required|integer';
            $rules['invoice'] = 'required|file|max:10240';
            $rules['payment_slip'] = 'required|file|max:10240';
        }

        if ($this->isBackEndAssistance) {
            $rules['device_type'] = 'required|in:Mobile,Browser';
        }

        if ($this->mobileFieldsVisible) {
            $rules['mobile_type'] = 'required|in:iOS,Android,Huawei';
            $rules['version_screenshot'] = 'required|image|max:5120';
        }

        if ($this->browserFieldsVisible) {
            $rules['browser_type'] = 'required|in:Chrome,Firefox,Safari,Edge,Opera';
        }

        $this->validate($rules);

        try {
            $authUser = auth()->user();
            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where(function ($q) use ($authUser) {
                        $q->where('name', $authUser->name)
                          ->orWhere('name', 'LIKE', '%' . $authUser->name . '%')
                          ->orWhere('email', $authUser->email);
                    })
                    ->first();
            }
            $requestorId = $ticketSystemUser?->id ?? 22;

            $productCode = $this->product_id == 1 ? 'HR1' : 'HR2';
            $lastTicket = Ticket::where('ticket_id', 'like', "TC-{$productCode}-%")
                ->orderBy('id', 'desc')
                ->first();
            if ($lastTicket && $lastTicket->ticket_id) {
                preg_match('/TC-' . $productCode . '-(\d+)/', $lastTicket->ticket_id, $m);
                $next = (isset($m[1]) ? (int) $m[1] : 0) + 1;
            } else {
                $next = 1;
            }
            $ticketId = sprintf('TC-%s-%04d', $productCode, $next);

            $screenshotPath = null;
            if ($this->mobileFieldsVisible && $this->version_screenshot) {
                $screenshotPath = $this->version_screenshot->store('version_screenshot', 's3-ticketing');
            }

            $invoicePath = null;
            $invoiceMime = null;
            $invoiceSize = null;
            $paymentSlipPath = null;
            $paymentSlipMime = null;
            $paymentSlipSize = null;
            if ($this->isP4b) {
                if ($this->invoice) {
                    $invoiceMime = $this->invoice->getMimeType();
                    $invoiceSize = $this->invoice->getSize();
                    $invoicePath = $this->invoice->store('ticket-attachments', 's3-ticketing');
                }
                if ($this->payment_slip) {
                    $paymentSlipMime = $this->payment_slip->getMimeType();
                    $paymentSlipSize = $this->payment_slip->getSize();
                    $paymentSlipPath = $this->payment_slip->store('ticket-attachments', 's3-ticketing');
                }
            }

            $ticket = Ticket::create([
                'ticket_id' => $ticketId,
                'status' => 'New',
                'requestor_id' => $requestorId,
                'created_date' => now()->subHours(8)->toDateString(),
                'isPassed' => 0,
                'is_internal' => false,
                'priority_id' => $this->priority_id,
                'product_id' => $this->product_id,
                'module_id' => $this->module_id,
                'parent_ticket_id' => $this->isP4b ? $this->parent_ticket_id : null,
                'device_type' => $this->deviceTypeVisible ? $this->device_type : null,
                'mobile_type' => $this->mobileFieldsVisible ? $this->mobile_type : null,
                'browser_type' => $this->browserFieldsVisible ? $this->browser_type : null,
                'version_screenshot' => $screenshotPath,
                'zoho_id' => strtoupper($this->zoho_id),
                'company_name' => $this->company_name,
                'title' => strtoupper($this->title),
                'description' => $this->description,
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(8),
            ]);

            if ($this->isP4b) {
                if ($invoicePath) {
                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'type' => 'invoice',
                        'original_filename' => basename($invoicePath),
                        'stored_filename' => null,
                        'file_path' => $invoicePath,
                        'file_size' => $invoiceSize,
                        'mime_type' => $invoiceMime,
                        'file_hash' => null,
                        'uploaded_by' => $requestorId,
                        'created_at' => now()->subHours(8),
                        'updated_at' => now()->subHours(8),
                    ]);
                }
                if ($paymentSlipPath) {
                    TicketAttachment::create([
                        'ticket_id' => $ticket->id,
                        'type' => 'payment_slip',
                        'original_filename' => basename($paymentSlipPath),
                        'stored_filename' => null,
                        'file_path' => $paymentSlipPath,
                        'file_size' => $paymentSlipSize,
                        'mime_type' => $paymentSlipMime,
                        'file_hash' => null,
                        'uploaded_by' => $requestorId,
                        'created_at' => now()->subHours(8),
                        'updated_at' => now()->subHours(8),
                    ]);
                }
            }

            $priority = $this->currentPriority;
            $priorityName = $priority?->name ?? 'Unknown';
            $details = "Ticket {$ticketId}\nTitle: " . strtoupper($this->title) . "\nPriority: {$priorityName}\nCategory: {$priorityName}\nRequester: " . ($ticketSystemUser?->name ?? 'HRcrm User');

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'old_value' => 'No existing ticket',
                'new_value' => $details,
                'action' => "Created new ticket {$ticketId}",
                'field_name' => null,
                'change_reason' => null,
                'old_eta' => null,
                'new_eta' => null,
                'updated_by' => $requestorId,
                'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                'user_role' => $ticketSystemUser->role ?? 'Internal Staff',
                'change_type' => 'ticket_creation',
                'source' => 'crm',
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(8),
            ]);

            try {
                $ticket->load('priority');
                $actionBy = $ticketSystemUser ? TicketingUser::find($ticketSystemUser->id) : null;
                $action = match ($priorityName) {
                    'Software Bugs' => 'created_p1',
                    'Back End Assistance' => 'created_p2',
                    'RFQ Customization' => 'created_p4a',
                    default => 'created_p3_p5',
                };
                app(TicketNotificationService::class)->handleAction($action, $ticket, $actionBy);
            } catch (\Exception $e) {
                Log::error('Ticket notification failed: ' . $e->getMessage());
            }

            Notification::make()
                ->title('Ticket Created')
                ->body("Ticket {$ticketId} (ID: #{$ticket->id}) has been created successfully.")
                ->success()
                ->send();

            $this->closeDrawer();
            $this->dispatch('ticket-created');
        } catch (\Exception $e) {
            Log::error('Create ticket failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            Notification::make()->title('Failed to create ticket')->body($e->getMessage())->danger()->send();
        }
    }

    protected function getForms(): array
    {
        return [
            'descriptionForm' => $this->makeForm()->schema([
                RichEditor::make('description')
                    ->label('')
                    ->placeholder('Describe the ticket in detail')
                    ->required()
                    ->toolbarButtons(['attachFiles', 'bold', 'italic', 'underline', 'strike', 'bulletList', 'orderedList', 'h2', 'h3', 'link', 'undo', 'redo'])
                    ->fileAttachmentsDisk('s3-ticketing')
                    ->fileAttachmentsDirectory('ticket_desc_images')
                    ->fileAttachmentsVisibility('private'),
            ]),
        ];
    }
}
