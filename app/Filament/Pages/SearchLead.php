<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;
use App\Models\Lead;
use App\Models\User;
use App\Enums\LeadStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Livewire\Attributes\Validate;
use Illuminate\Support\Carbon;
use Filament\Actions\Action;

class SearchLead extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Search Lead';
    protected static ?string $title = 'Search Lead';
    protected static string $view = 'filament.pages.search-lead';

    // Search properties
    #[Validate('nullable|string|max:255')]
    public string $companySearchTerm = '';

    #[Validate('nullable|string|max:255')]
    public string $phoneSearchTerm = '';

    public bool $hasSearched = false;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Modal properties for time since creation
    public $showTimeSinceModal = false;
    public $selectedLead = null;

    // Modal properties for duplicate lead
    public $showDuplicateModal = false;
    public $leadToDuplicate = null;
    public $duplicateSalesperson = null;
    public $duplicateLeadCode = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.search-lead');
    }

    public function mount(): void
    {
        $this->hasSearched = false;
    }

    public function updatingCompanySearchTerm(): void
    {
        $this->hasSearched = false;
    }

    public function updatingPhoneSearchTerm(): void
    {
        $this->hasSearched = false;
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
    }

    // Add this method to open the time since creation modal
    public function openTimeSinceModal($leadId)
    {
        $this->selectedLead = Lead::find($leadId);
        $this->showTimeSinceModal = true;
    }

    public function closeTimeSinceModal()
    {
        $this->showTimeSinceModal = false;
        $this->selectedLead = null;
    }

    // Duplicate lead methods
    public function openDuplicateModal($leadId)
    {
        $this->leadToDuplicate = Lead::find($leadId);
        $this->duplicateSalesperson = null;
        $this->duplicateLeadCode = null;
        $this->showDuplicateModal = true;
    }

    public function canDuplicateLead(): bool
    {
        $user = auth()->user();
        return $user && in_array($user->id, [1, 14]);
    }

    public function closeDuplicateModal()
    {
        $this->showDuplicateModal = false;
        $this->leadToDuplicate = null;
        $this->duplicateSalesperson = null;
        $this->duplicateLeadCode = null;
        $this->resetErrorBag();
    }

    public function duplicateLead()
    {
        try {
            // Validate required fields
            $this->validate([
                'duplicateSalesperson' => 'required',
                'duplicateLeadCode' => 'required|string',
            ], [
                'duplicateSalesperson.required' => 'Please select a salesperson.',
                'duplicateLeadCode.required' => 'Please select a lead code.',
            ]);

            if (!$this->leadToDuplicate) {
                throw new \Exception('Lead not found.');
            }

            $oldLead = $this->leadToDuplicate;

            // Create duplicate lead with all information except salesperson and lead_code
            $newLead = Lead::createQuietly([
                'name' => $oldLead->name,
                'email' => $oldLead->email,
                'country' => $oldLead->country,
                'company_size' => $oldLead->company_size,
                'phone' => $oldLead->phone,
                'lead_code' => $this->duplicateLeadCode,
                'salesperson' => $this->duplicateSalesperson,
                'products' => $oldLead->products,
                'categories' => 'Active',
                'stage' => 'Transfer',
                'lead_status' => 'RFQ-Transfer',
                'company_detail_id' => null, // Will be set after duplicating company detail
                'linkedin_url' => $oldLead->linkedin_url,
            ]);

            // Duplicate company detail if exists
            if ($oldLead->companyDetail) {
                $newCompanyDetail = \App\Models\CompanyDetail::create([
                    'company_name' => $oldLead->companyDetail->company_name,
                    'contact_no' => $oldLead->companyDetail->contact_no,
                    'lead_id' => $newLead->id,
                ]);

                $newLead->update([
                    'company_detail_id' => $newCompanyDetail->id,
                ]);
            }

            // Duplicate UTM details if exists
            if ($oldLead->utmDetail) {
                \App\Models\UtmDetail::create([
                    'lead_id' => $newLead->id,
                    'utm_campaign' => $oldLead->utmDetail->utm_campaign,
                    'utm_adgroup' => $oldLead->utmDetail->utm_adgroup,
                    'utm_creative' => $oldLead->utmDetail->utm_creative,
                    'utm_term' => $oldLead->utmDetail->utm_term,
                    'utm_source' => $oldLead->utmDetail->utm_source,
                    'utm_medium' => $oldLead->utmDetail->utm_medium,
                    'utm_matchtype' => $oldLead->utmDetail->utm_matchtype,
                    'referrername' => $oldLead->utmDetail->referrername,
                ]);
            }

            // Duplicate system questions if exists
            $systemQuestions = \App\Models\SystemQuestion::where('lead_id', $oldLead->id)->get();
            foreach ($systemQuestions as $question) {
                \App\Models\SystemQuestion::create([
                    'lead_id' => $newLead->id,
                    'modules' => $question->modules,
                    'existing_system' => $question->existing_system,
                    'causer_name' => $question->causer_name,
                ]);
            }

            // Duplicate referral detail if exists
            if ($oldLead->referralDetail) {
                \App\Models\ReferralDetail::create([
                    'lead_id' => $newLead->id,
                    'company' => $oldLead->referralDetail->company,
                    'name' => $oldLead->referralDetail->name,
                    'email' => $oldLead->referralDetail->email,
                    'contact_no' => $oldLead->referralDetail->contact_no,
                ]);
            }

            // Update old lead status
            $oldLead->update([
                'categories' => 'Inactive',
                'stage' => null,
                'lead_status' => 'On Hold',
            ]);

            // Create activity log for the new lead
            $salespersonName = User::find($this->duplicateSalesperson)?->name ?? 'Unknown';
            \App\Models\ActivityLog::create([
                'subject_type' => Lead::class,
                'subject_id' => $newLead->id,
                'causer_type' => \App\Models\User::class,
                'causer_id' => auth()->id(),
                'description' => "Lead duplicated from Lead #{$oldLead->id} and assigned to Salesperson: {$salespersonName}",
                'properties' => json_encode([
                    'old_lead_id' => $oldLead->id,
                    'attributes' => [
                        'salesperson' => $this->duplicateSalesperson,
                        'lead_code' => $this->duplicateLeadCode,
                    ],
                ]),
            ]);

            // Create activity log for the old lead
            \App\Models\ActivityLog::create([
                'subject_type' => Lead::class,
                'subject_id' => $oldLead->id,
                'causer_type' => \App\Models\User::class,
                'causer_id' => auth()->id(),
                'description' => "Lead duplicated to new Lead #{$newLead->id}. Status changed to Inactive/On Hold.",
                'properties' => json_encode([
                    'new_lead_id' => $newLead->id,
                    'old_attributes' => [
                        'categories' => 'Active',
                        'lead_status' => $oldLead->getOriginal('lead_status'),
                        'lead_owner' => $oldLead->getOriginal('lead_owner'),
                    ],
                    'attributes' => [
                        'categories' => 'Inactive',
                        'stage' => null,
                        'lead_status' => 'On Hold',
                        'lead_owner' => null,
                    ],
                ]),
            ]);

            $this->closeDuplicateModal();

            Notification::make()
                ->title('Lead Duplicated Successfully')
                ->body("New lead #{$newLead->id} has been created. Old lead #{$oldLead->id} has been marked as Inactive/On Hold.")
                ->success()
                ->send();

            // Refresh the search results
            $this->searchCompany();

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Notification::make()
                ->title('Duplication Error')
                ->body('An error occurred while duplicating the lead: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getSalespersonOptions()
    {
        return User::where('is_active', true)
            ->where('role_id', 2)
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getLeadCodeOptions()
    {
        return \App\Models\LeadSource::orderBy('lead_code')
            ->pluck('lead_code', 'lead_code')
            ->toArray();
    }

    public function searchCompany(): void
    {
        try {
            // Validate that there's something to search for
            if (empty($this->companySearchTerm) && empty($this->phoneSearchTerm)) {
                Notification::make()
                    ->title('Search Error')
                    ->body('Please enter a search term - either a company name or phone number to search.')
                    ->danger()
                    ->send();
                return;
            }

            // Set searched flag
            $this->hasSearched = true;

            Notification::make()
                ->title('Search Complete')
                ->body('Found ' . $this->getLeads()->count() . ' results.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Search Error')
                ->body('An error occurred while searching. Please try again.')
                ->danger()
                ->send();
        }
    }

    public function resetSearch(): void
    {
        try {
            $this->companySearchTerm = '';
            $this->phoneSearchTerm = '';
            $this->hasSearched = false;
            $this->resetErrorBag();

            Notification::make()
                ->title('Search Cleared')
                ->body('Search has been reset.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Reset Error')
                ->body('An error occurred while resetting search.')
                ->danger()
                ->send();
        }
    }

    public function getLeads()
    {
        if (!$this->hasSearched || (empty($this->companySearchTerm) && empty($this->phoneSearchTerm))) {
            return collect(); // Return empty collection
        }

        $query = Lead::query()->with(['companyDetail']);

        // Build search conditions
        $query->where(function (Builder $searchQuery) {
            // Company name search
            if (!empty($this->companySearchTerm)) {
                $searchQuery->whereHas('companyDetail', function (Builder $subQuery) {
                    $subQuery->where('company_name', 'like', "%{$this->companySearchTerm}%");
                });
            }

            // Phone number search (OR condition if both terms exist)
            if (!empty($this->phoneSearchTerm)) {
                if (!empty($this->companySearchTerm)) {
                    // If both searches exist, use OR
                    $searchQuery->orWhere(function (Builder $phoneQuery) {
                        $phoneQuery->where('phone', 'like', "%{$this->phoneSearchTerm}%")
                                 ->orWhereHas('companyDetail', function (Builder $contactQuery) {
                                     $contactQuery->where('contact_no', 'like', "%{$this->phoneSearchTerm}%");
                                 });
                    });
                } else {
                    // If only phone search exists
                    $searchQuery->where('phone', 'like', "%{$this->phoneSearchTerm}%")
                              ->orWhereHas('companyDetail', function (Builder $contactQuery) {
                                  $contactQuery->where('contact_no', 'like', "%{$this->phoneSearchTerm}%");
                              });
                }
            }
        });

        // Return all results without pagination
        return $query->orderBy($this->sortField, $this->sortDirection)->get();
    }

    // Helper methods to match the table structure
    public function getLeadOwner($lead)
    {
        return $lead->lead_owner ?? '-';
    }

    public function getSalesperson($lead)
    {
        return User::find($lead->salesperson)?->name ?? '-';
    }

    public function getLeadStatus($lead)
    {
        return $lead->lead_status;
    }

    public function getLeadStatusColor($lead)
    {
        $status = $lead->lead_status;
        $leadStatusEnum = LeadStatusEnum::tryFrom($status);
        return $leadStatusEnum ? $leadStatusEnum->getColor() : '#6b7280';
    }

    public function getCompanyName($lead)
    {
        return $lead->companyDetail?->company_name ?? '-';
    }

    // Add method to get time since creation data for the modal
    public function getTimeSinceCreationData($lead)
    {
        $createdAt = $lead->created_at;
        $now = now();

        $diffInDays = $createdAt->diffInDays($now);
        $diffInHours = $createdAt->copy()->addDays($diffInDays)->diffInHours($now);
        $diffInMinutes = $createdAt->copy()->addDays($diffInDays)->addHours($diffInHours)->diffInMinutes($now);

        $humanReadable = $createdAt->diffForHumans();

        // Format detailed time breakdown
        $detailedBreakdown = '';
        if ($diffInDays > 0) {
            $detailedBreakdown .= "{$diffInDays} day" . ($diffInDays > 1 ? 's' : '') . ", ";
        }
        if ($diffInHours > 0 || $diffInDays > 0) {
            $detailedBreakdown .= "{$diffInHours} hour" . ($diffInHours > 1 ? 's' : '') . ", ";
        }
        $detailedBreakdown .= "{$diffInMinutes} minute" . ($diffInMinutes > 1 ? 's' : '');

        return [
            'record' => $lead,
            'created_at' => $createdAt->format('d M Y, h:i A'),
            'human_readable' => $humanReadable,
            'detailed_breakdown' => $detailedBreakdown,
            'diff_in_days' => $diffInDays,
            'diff_in_hours' => $diffInHours,
            'diff_in_minutes' => $diffInMinutes,
        ];
    }
}
