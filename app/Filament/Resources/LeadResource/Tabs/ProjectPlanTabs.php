<?php
namespace App\Filament\Resources\LeadResource\Tabs;

use App\Models\EmailTemplate;
use App\Models\ProjectTask;
use App\Models\ProjectPlan;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use App\Models\ImplementerHandoverRequest;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\Enums\ActionSize;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Mail;
use App\Services\TemplateSelector;
use App\Services\MicrosoftGraphService;
use Carbon\Carbon;
use Filament\Forms\Components\RichEditor;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\FileUpload;

class ProjectPlanTabs
{
    public static function getSchema(): array
    {
        return [
            Section::make('Project Plan')
                ->headerActions([
                    \Filament\Forms\Components\Actions\Action::make('requestHandover')
                        ->label('Request Handover')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->size(ActionSize::Small)
                        ->requiresConfirmation()
                        ->modalHeading('Request Handover')
                        ->modalDescription('You are requesting handover as the project plan has reached 100% completion. This action will notify the relevant parties.')
                        ->visible(function (Get $get, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                return false;
                            }

                            // Get target software handovers
                            $allHandovers = SoftwareHandover::where('lead_id', $leadId)
                                ->orderBy('created_at', 'desc')
                                ->get();

                            if ($allHandovers->isEmpty()) {
                                return false;
                            }

                            // Check if at least one handover is not closed
                            $hasNonClosedHandover = $allHandovers->where('status_handover', '!=', 'Closed')->isNotEmpty();

                            if (!$hasNonClosedHandover) {
                                return false;
                            }

                            // Check if any handover has already requested handover
                            $hasRequestedHandover = false;
                            foreach ($allHandovers as $handover) {
                                if ($handover->handover_requested_at) {
                                    $hasRequestedHandover = true;
                                    break;
                                }
                            }

                            // Hide button if handover already requested
                            if ($hasRequestedHandover) {
                                return false;
                            }

                            // Calculate overall completion percentage for all handovers
                            $swIds = $allHandovers->pluck('id')->toArray();
                            $allPlans = ProjectPlan::where('lead_id', $leadId)
                                ->whereIn('sw_id', $swIds)
                                ->with('projectTask')
                                ->get();

                            if ($allPlans->isEmpty()) {
                                return false;
                            }

                            $totalWeight = 0;
                            $completedWeight = 0;

                            foreach ($allPlans as $plan) {
                                $taskWeight = $plan->projectTask->task_percentage ?? 0;
                                $totalWeight += $taskWeight;

                                if ($plan->status === 'completed') {
                                    $completedWeight += $taskWeight;
                                }
                            }

                            // Show button only if completion is 100%
                            $completionPercentage = $totalWeight > 0 ? round(($completedWeight / $totalWeight) * 100, 2) : 0;
                            return $completionPercentage >= 100;
                        })
                        ->action(function (Get $get, Set $set, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Please save the lead first')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Get target software handovers
                            $allHandovers = SoftwareHandover::where('lead_id', $leadId)
                                ->orderBy('created_at', 'desc')
                                ->get();

                            if ($allHandovers->isEmpty()) {
                                Notification::make()
                                    ->title('No Software Handovers Found')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Check if at least one handover is not closed
                            $hasNonClosedHandover = $allHandovers->where('status_handover', '!=', 'Closed')->isNotEmpty();

                            if (!$hasNonClosedHandover) {
                                Notification::make()
                                    ->title('All Handovers Closed')
                                    ->body('Cannot request handover as all handovers are closed')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Filter to only non-closed handovers for processing
                            $targetHandovers = $allHandovers->where('status_handover', '!=', 'Closed');

                            // Update handover_requested_at for non-closed handovers only
                            $updatedCount = 0;
                            $lead = Lead::find($leadId);

                            foreach ($targetHandovers as $handover) {
                                $handover->update([
                                    'handover_requested_at' => now(),
                                    'handover_requested_by' => auth()->id(),
                                ]);

                                // Create record in implementer_handover_requests table
                                ImplementerHandoverRequest::create([
                                    'sw_id' => $handover->id,
                                    'implementer_name' => $handover->implementer ?? 'Not Assigned',
                                    'company_name' => $lead->companyDetail?->company_name ?? 'Unknown Company',
                                    'date_request' => now(),
                                ]);

                                $updatedCount++;
                            }

                            $set('refresh_trigger', time());

                            Notification::make()
                                ->title('Handover Requested Successfully')
                                ->body("Handover request has been submitted for {$updatedCount} software handover(s). The relevant parties will be notified.")
                                ->success()
                                ->send();

                            // Dispatch refresh event
                            $livewire->dispatch('$refresh');
                        }),

                    // \Filament\Forms\Components\Actions\Action::make('sendHandoverEmail')
                    //     ->label('Send Handover Email')
                    //     ->icon('heroicon-o-envelope')
                    //     ->color('success')
                    //     ->size(ActionSize::Small)
                    //     ->visible(function (Get $get, $livewire) {
                    //         // Check if there's an approved handover request
                    //         $lead = $livewire->getRecord();
                    //         if (!$lead) {
                    //             return false;
                    //         }

                    //         // Find the latest software handover for this lead
                    //         $softwareHandover = SoftwareHandover::where('lead_id', $lead->id)
                    //             ->latest()
                    //             ->first();

                    //         if (!$softwareHandover) {
                    //             return false;
                    //         }

                    //         // Check if there's an approved handover request
                    //         $approvedRequest = ImplementerHandoverRequest::where('sw_id', $softwareHandover->id)
                    //             ->where('status', 'approved')
                    //             ->exists();

                    //         return $approvedRequest;
                    //     })
                    //     ->form([
                    //         Select::make('email_template')
                    //             ->label('Email Template')
                    //             ->options([
                    //                 'handover_completion' => 'Handover Completion',
                    //                 'project_closure' => 'Project Closure',
                    //                 'final_handover' => 'Final Handover',
                    //             ])
                    //             ->default('handover_completion')
                    //             ->required(),

                    //         Textarea::make('additional_notes')
                    //             ->label('Additional Notes')
                    //             ->rows(4)
                    //             ->placeholder('Any additional notes to include in the email...'),

                    //         TextInput::make('recipient_email')
                    //             ->label('Additional Recipients (optional)')
                    //             ->placeholder('email1@example.com; email2@example.com')
                    //             ->helperText('Separate multiple emails with semicolons'),
                    //     ])
                    //     ->modalHeading('Send Handover Email')
                    //     ->modalDescription('Send a handover email notification to the client and relevant parties.')
                    //     ->modalSubmitActionLabel('Send Email')
                    //     ->action(function (array $data, Get $get, $livewire) {
                    //         $lead = $livewire->getRecord();

                    //         // Find the latest software handover
                    //         $softwareHandover = SoftwareHandover::where('lead_id', $lead->id)
                    //             ->latest()
                    //             ->first();

                    //         if (!$softwareHandover) {
                    //             Notification::make()
                    //                 ->title('Error')
                    //                 ->body('Software handover not found.')
                    //                 ->danger()
                    //                 ->send();
                    //             return;
                    //         }

                    //         // Find the approved handover request
                    //         $handoverRequest = ImplementerHandoverRequest::where('sw_id', $softwareHandover->id)
                    //             ->where('status', 'approved')
                    //             ->latest()
                    //             ->first();

                    //         if (!$handoverRequest) {
                    //             Notification::make()
                    //                 ->title('Error')
                    //                 ->body('Approved handover request not found.')
                    //                 ->danger()
                    //                 ->send();
                    //             return;
                    //         }

                    //         try {
                    //             // Prepare recipients list
                    //             $recipients = [];

                    //             // Add client email
                    //             if ($lead->companyDetail?->email) {
                    //                 $recipients[] = $lead->companyDetail->email;
                    //             }

                    //             // Add lead owner email
                    //             if ($lead->lead_owner) {
                    //                 $leadOwner = User::where('name', $lead->lead_owner)->first();
                    //                 if ($leadOwner && $leadOwner->email) {
                    //                     $recipients[] = $leadOwner->email;
                    //                 }
                    //             }

                    //             // Add additional recipients from form
                    //             if (!empty($data['recipient_email'])) {
                    //                 $additionalEmails = array_filter(array_map('trim', explode(';', $data['recipient_email'])));
                    //                 foreach ($additionalEmails as $email) {
                    //                     if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    //                         $recipients[] = $email;
                    //                     }
                    //                 }
                    //             }

                    //             if (empty($recipients)) {
                    //                 Notification::make()
                    //                     ->title('Error')
                    //                     ->body('No valid recipients found.')
                    //                     ->danger()
                    //                     ->send();
                    //                 return;
                    //             }

                    //             // Prepare email data
                    //             $emailData = [
                    //                 'company_name' => $lead->companyDetail?->company_name ?? 'N/A',
                    //                 'sw_id' => $softwareHandover->formatted_handover_id,
                    //                 'implementer_name' => $softwareHandover->implementer,
                    //                 'approved_at' => $handoverRequest->approved_at?->format('d M Y, H:i'),
                    //                 'team_lead_remark' => $handoverRequest->team_lead_remark,
                    //                 'additional_notes' => $data['additional_notes'] ?? '',
                    //                 'template' => $data['email_template'],
                    //             ];

                    //             // Send email
                    //             $authUser = auth()->user();
                    //             \Illuminate\Support\Facades\Mail::send('emails.handover-completion', ['data' => $emailData], function ($message) use ($recipients, $authUser, $emailData) {
                    //                 $message->from($authUser->email, $authUser->name)
                    //                     ->to($recipients)
                    //                     ->cc($authUser->email)
                    //                     ->subject("{$emailData['sw_id']} | HANDOVER COMPLETION | {$emailData['company_name']}");
                    //             });

                    //             // Log the email sent
                    //             Log::info('Handover Completion Email Sent', [
                    //                 'sw_id' => $softwareHandover->formatted_handover_id,
                    //                 'company_name' => $lead->companyDetail?->company_name ?? 'N/A',
                    //                 'sent_to' => implode(', ', $recipients),
                    //                 'sent_by' => $authUser->name,
                    //                 'template' => $data['email_template'],
                    //                 'timestamp' => now()->format('Y-m-d H:i:s')
                    //             ]);

                    //             Notification::make()
                    //                 ->title('Email Sent Successfully')
                    //                 ->body('Handover email has been sent to all recipients.')
                    //                 ->success()
                    //                 ->send();

                    //         } catch (\Exception $e) {
                    //             Log::error('Failed to send handover email: ' . $e->getMessage());

                    //             Notification::make()
                    //                 ->title('Failed to Send Email')
                    //                 ->body('Error: ' . $e->getMessage())
                    //                 ->danger()
                    //                 ->send();
                    //         }
                    //     }),

                    \Filament\Forms\Components\Actions\Action::make('sendClosingEmail')
                        ->label('Send Closing Email')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('danger')
                        ->size(ActionSize::Small)
                        ->modalHeading(false)
                        ->modalWidth('6xl')
                        ->visible(function (Get $get, $livewire) {
                            // Check if there's an approved handover request with non-closed status
                            $lead = $livewire->getRecord();
                            if (!$lead) {
                                return false;
                            }

                            // Get all software handovers for this lead
                            $allHandovers = SoftwareHandover::where('lead_id', $lead->id)
                                ->orderBy('created_at', 'desc')
                                ->get();

                            if ($allHandovers->isEmpty()) {
                                return false;
                            }

                            $latestHandover = $allHandovers->first();

                            // Smart filtering: If latest handover is NOT closed, filter out closed ones
                            if ($latestHandover->status_handover !== 'Closed') {
                                $targetHandovers = $allHandovers->where('status_handover', '!=', 'Closed');
                            } else {
                                // If latest handover IS closed, don't show the button
                                return false;
                            }

                            if ($targetHandovers->isEmpty()) {
                                return false;
                            }

                            // Check if any non-closed handover has an approved request
                            foreach ($targetHandovers as $handover) {
                                $approvedRequest = ImplementerHandoverRequest::where('sw_id', $handover->id)
                                    ->where('status', 'approved')
                                    ->exists();

                                if ($approvedRequest) {
                                    return true;
                                }
                            }

                            return false;
                        })
                        ->form([
                            \Filament\Forms\Components\Grid::make(1)
                                ->schema([
                                    \Filament\Forms\Components\Fieldset::make('Email Details')
                                        ->schema([
                                            \Filament\Forms\Components\Grid::make(2)
                                                ->schema([
                                                    // Left column - Email form fields
                                                    \Filament\Forms\Components\Grid::make(1)
                                                        ->schema([
                                                            TextInput::make('required_attendees')
                                                                ->label('Required Attendees')
                                                                ->helperText('Separate each email with a semicolon (e.g., email1@example.com;email2@example.com)')
                                                                ->default(function ($livewire) {
                                                                    $lead = $livewire->getRecord();
                                                                    $emails = [];

                                                                    if ($lead) {
                                                                        // Add client email
                                                                        if ($lead->companyDetail?->email) {
                                                                            $emails[] = $lead->companyDetail->email;
                                                                        }

                                                                        // Add lead owner email
                                                                        // if ($lead->lead_owner) {
                                                                        //     $leadOwner = User::where('name', $lead->lead_owner)->first();
                                                                        //     if ($leadOwner && $leadOwner->email) {
                                                                        //         $emails[] = $leadOwner->email;
                                                                        //     }
                                                                        // }

                                                                        // Add implementer email
                                                                        $softwareHandover = SoftwareHandover::where('lead_id', $lead->id)->latest()->first();
                                                                        if ($softwareHandover && $softwareHandover->implementer) {
                                                                            $implementer = User::where('name', $softwareHandover->implementer)->first();
                                                                            if ($implementer && $implementer->email) {
                                                                                $emails[] = $implementer->email;
                                                                            }
                                                                        }
                                                                    }

                                                                    // Remove duplicates
                                                                    $uniqueEmails = array_unique(array_filter($emails));
                                                                    return !empty($uniqueEmails) ? implode(';', $uniqueEmails) : null;
                                                                })
                                                                ->required(),

                                                            Select::make('email_template')
                                                                ->label('Email Template')
                                                                ->options(function () {
                                                                    return EmailTemplate::where('type', 'implementer')->pluck('name', 'id')->toArray();
                                                                })
                                                                ->default(16)
                                                                ->searchable()
                                                                ->preload()
                                                                ->reactive()
                                                                ->afterStateHydrated(function ($state, callable $set, $livewire, $component) {
                                                                    // Load template on initial form open
                                                                    $templateId = $state ?? 16;

                                                                    $template = EmailTemplate::find($templateId);
                                                                    $lead = $livewire->getRecord();

                                                                    if ($template && $lead) {
                                                                        $subject = $template->subject ?? '';
                                                                        $content = $template->content ?? '';

                                                                        // Replace placeholders
                                                                        $softwareHandover = SoftwareHandover::where('lead_id', $lead->id)->latest()->first();

                                                                        $placeholders = [
                                                                            '{customer_name}' => $lead->contact_name ?? '',
                                                                            '{company_name}' => $lead->companyDetail?->company_name ?? 'Unknown Company',
                                                                            '{implementer_name}' => $softwareHandover?->implementer ?? 'N/A',
                                                                            '{lead_owner}' => $lead->lead_owner ?? '',
                                                                        ];

                                                                        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);
                                                                        $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);

                                                                        $set('email_subject', $subject);
                                                                        $set('email_content', $content);
                                                                        $set('template_selected', true);
                                                                    }
                                                                })
                                                                ->afterStateUpdated(function ($state, callable $set, $livewire) {
                                                                    if ($state) {
                                                                        $template = EmailTemplate::find($state);
                                                                        $lead = $livewire->getRecord();

                                                                        if ($template && $lead) {
                                                                            $subject = $template->subject ?? '';
                                                                            $content = $template->content ?? '';

                                                                            // Replace placeholders
                                                                            $softwareHandover = SoftwareHandover::where('lead_id', $lead->id)->latest()->first();

                                                                            $placeholders = [
                                                                                '{customer_name}' => $lead->contact_name ?? '',
                                                                                '{company_name}' => $lead->companyDetail?->company_name ?? 'Unknown Company',
                                                                                '{implementer_name}' => $softwareHandover?->implementer ?? 'N/A',
                                                                                '{lead_owner}' => $lead->lead_owner ?? '',
                                                                            ];

                                                                            $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);
                                                                            $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);

                                                                            $set('email_subject', $subject);
                                                                            $set('email_content', $content);
                                                                            $set('template_selected', true);
                                                                        }
                                                                    }
                                                                })
                                                                ->required(),

                                                            TextInput::make('email_subject')
                                                                ->label('Email Subject')
                                                                ->default(function ($livewire) {
                                                                    $lead = $livewire->getRecord();
                                                                    $template = EmailTemplate::find(16);

                                                                    if ($template && $lead) {
                                                                        $subject = $template->subject ?? '';
                                                                        $softwareHandover = SoftwareHandover::where('lead_id', $lead->id)->latest()->first();

                                                                        $placeholders = [
                                                                            '{customer_name}' => $lead->contact_name ?? '',
                                                                            '{company_name}' => $lead->companyDetail?->company_name ?? 'Unknown Company',
                                                                            '{implementer_name}' => $softwareHandover?->implementer ?? 'N/A',
                                                                            '{lead_owner}' => $lead->lead_owner ?? '',
                                                                        ];

                                                                        return str_replace(array_keys($placeholders), array_values($placeholders), $subject);
                                                                    }

                                                                    return '';
                                                                })
                                                                ->required()
                                                                ->reactive(),

                                                            RichEditor::make('email_content')
                                                                ->label('Email Content')
                                                                ->default(function ($livewire) {
                                                                    $lead = $livewire->getRecord();
                                                                    $template = EmailTemplate::find(16);

                                                                    if ($template && $lead) {
                                                                        $content = $template->content ?? '';
                                                                        $softwareHandover = SoftwareHandover::where('lead_id', $lead->id)->latest()->first();

                                                                        $placeholders = [
                                                                            '{customer_name}' => $lead->contact_name ?? '',
                                                                            '{company_name}' => $lead->companyDetail?->company_name ?? 'Unknown Company',
                                                                            '{implementer_name}' => $softwareHandover?->implementer ?? 'N/A',
                                                                            '{lead_owner}' => $lead->lead_owner ?? '',
                                                                        ];

                                                                        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
                                                                    }

                                                                    return '';
                                                                })
                                                                ->disableToolbarButtons([
                                                                    'attachFiles',
                                                                ])
                                                                ->required()
                                                                ->reactive(),

                                                            FileUpload::make('email_attachments')
                                                                ->label('Email Attachments')
                                                                ->multiple()
                                                                ->maxFiles(5)
                                                                ->acceptedFileTypes(['application/pdf', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                                                ->maxSize(10240)
                                                                ->disk('public')
                                                                ->directory('temp_email_attachments')
                                                                ->visibility('private')
                                                                ->preserveFilenames()
                                                                ->helperText('Upload up to 5 files (PDF or Excel)')
                                                                ->columnSpanFull()
                                                                ->reactive(),
                                                        ])->columnSpan(1),

                                                    // Right column - Email preview
                                                    \Filament\Forms\Components\Placeholder::make('email_preview')
                                                        ->label('Email Preview')
                                                        ->content(function (callable $get, $livewire) {
                                                            $subject = $get('email_subject') ?? '';
                                                            $content = $get('email_content') ?? '';

                                                            if (empty($subject) && empty($content)) {
                                                                return new HtmlString('<p class="italic text-gray-500">Select a template to see preview...</p>');
                                                            }

                                                            // Get lead data
                                                            $lead = $livewire->getRecord();
                                                            $softwareHandover = SoftwareHandover::where('lead_id', $lead->id)->latest()->first();

                                                            $previewImplementerName = auth()->user()->name ?? '';
                                                            $previewDesignation = 'Implementer';

                                                            // Add signature
                                                            $signature = "<br>Regards,<br>{$previewImplementerName}<br>{$previewDesignation}<br>TimeTec Cloud Sdn Bhd<br>Phone: 03-80709933";
                                                            $previewContent = $content . $signature;

                                                            $html = '<div class="p-4 border rounded-lg bg-gray-50">';
                                                            $html .= '<div class="mb-3"><strong>Subject:</strong> <span class="text-blue-600">' . e($subject) . '</span></div>';
                                                            $html .= '<div><strong>Content:</strong></div>';
                                                            $html .= '<div class="p-3 mt-2 bg-white border rounded">' . $previewContent . '</div>';
                                                            $html .= '</div>';

                                                            return new HtmlString($html);
                                                        })
                                                        ->columnSpan(1)
                                                        ->dehydrated(false)
                                                        ->visible(fn (callable $get) => !empty($get('email_subject')) || !empty($get('email_content')))
                                                ]),
                                        ]),

                                    // Hidden fields
                                    \Filament\Forms\Components\Hidden::make('template_selected')
                                        ->default(false)
                                        ->dehydrated(false),

                                    \Filament\Forms\Components\Hidden::make('implementer_name')
                                        ->default(auth()->user()->name ?? ''),

                                    \Filament\Forms\Components\Hidden::make('implementer_designation')
                                        ->default('Implementer'),

                                    \Filament\Forms\Components\Hidden::make('implementer_company')
                                        ->default('TimeTec Cloud Sdn Bhd'),

                                    \Filament\Forms\Components\Hidden::make('implementer_phone')
                                        ->default('03-80709933'),

                                    \Filament\Forms\Components\Hidden::make('implementer_email')
                                        ->default(auth()->user()->email ?? ''),
                                ]),
                        ])
                        ->action(function (array $data, $livewire) {
                            $lead = $livewire->getRecord();

                            try {
                                // Find the latest software handover
                                $softwareHandover = SoftwareHandover::where('lead_id', $lead->id)
                                    ->latest()
                                    ->first();

                                if (!$softwareHandover) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Software handover not found.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Find the approved handover request
                                $handoverRequest = ImplementerHandoverRequest::where('sw_id', $softwareHandover->id)
                                    ->where('status', 'approved')
                                    ->latest()
                                    ->first();

                                if (!$handoverRequest) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Approved handover request not found.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Get recipient emails
                                $recipientStr = $data['required_attendees'] ?? '';

                                if (empty($recipientStr)) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('No recipients specified.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Get email template content
                                $subject = $data['email_subject'];
                                $content = $data['email_content'];

                                // Add signature to email content
                                if (isset($data['implementer_name']) && !empty($data['implementer_name'])) {
                                    $signature = "<br><br>Regards,<br>";
                                    $signature .= "{$data['implementer_name']}<br>";
                                    $signature .= "{$data['implementer_designation']}<br>";
                                    $signature .= "{$data['implementer_company']}<br>";
                                    $signature .= "Phone: {$data['implementer_phone']}<br>";

                                    if (!empty($data['implementer_email'])) {
                                        $signature .= "Email: {$data['implementer_email']}<br>";
                                    }

                                    $content .= $signature;
                                }

                                // Replace placeholders with actual data
                                $placeholders = [
                                    '{customer_name}' => $lead->contact_name ?? '',
                                    '{company_name}' => $softwareHandover->company_name ?? ($lead->companyDetail?->company_name ?? 'Unknown Company'),
                                    '{implementer_name}' => $data['implementer_name'] ?? auth()->user()->name ?? '',
                                    '{lead_owner}' => $lead->lead_owner ?? '',
                                ];

                                $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);
                                $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);

                                // Collect valid email addresses
                                $validRecipients = [];
                                foreach (explode(';', $recipientStr) as $recipient) {
                                    $recipient = trim($recipient);
                                    if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                                        $validRecipients[] = $recipient;
                                    }
                                }

                                if (empty($validRecipients)) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('No valid recipients found.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Get authenticated user's email for sender
                                $authUser = auth()->user();
                                $senderEmail = $data['implementer_email'] ?? $authUser->email;
                                $senderName = $data['implementer_name'] ?? $authUser->name;

                                // Track attachment info for logging
                                $attachedFiles = [];

                                // Send email with attachments
                                Mail::html($content, function ($message) use ($subject, $validRecipients, $senderEmail, $senderName, $data, &$attachedFiles) {
                                    $message->from($senderEmail, $senderName)
                                        ->to($validRecipients)
                                        ->cc($senderEmail)
                                        ->subject($subject);

                                    // Add attachments
                                    if (!empty($data['email_attachments']) && is_array($data['email_attachments'])) {
                                        foreach ($data['email_attachments'] as $file) {
                                            if (is_string($file)) {
                                                // Try multiple possible file locations
                                                $possiblePaths = [
                                                    storage_path('app/public/' . $file),
                                                    storage_path('app/' . $file),
                                                    public_path('storage/' . $file),
                                                    $file,
                                                ];

                                                $fullPath = null;
                                                foreach ($possiblePaths as $path) {
                                                    if (file_exists($path)) {
                                                        $fullPath = $path;
                                                        break;
                                                    }
                                                }

                                                if ($fullPath && file_exists($fullPath)) {
                                                    try {
                                                        $message->attach($fullPath, [
                                                            'as' => basename($file),
                                                            'mime' => mime_content_type($fullPath)
                                                        ]);

                                                        $attachedFiles[] = basename($file);
                                                    } catch (\Exception $e) {
                                                        Log::error('Failed to attach file', [
                                                            'file' => basename($file),
                                                            'error' => $e->getMessage()
                                                        ]);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });

                                // Update ALL software handovers with approved requests to Closed
                                $allHandovers = SoftwareHandover::where('lead_id', $lead->id)
                                    ->where('status_handover', '!=', 'Closed')
                                    ->get();

                                $closedCount = 0;
                                foreach ($allHandovers as $handover) {
                                    // Check if this handover has an approved request
                                    $hasApprovedRequest = ImplementerHandoverRequest::where('sw_id', $handover->id)
                                        ->where('status', 'approved')
                                        ->exists();

                                    if ($hasApprovedRequest) {
                                        $handover->update([
                                            'status_handover' => 'Closed',
                                            'closed_at' => now(),
                                            'closed_by' => auth()->id(),
                                        ]);
                                        $closedCount++;
                                    }
                                }

                                // Clean up temporary attachments
                                if (!empty($data['email_attachments'])) {
                                    foreach ($data['email_attachments'] as $file) {
                                        \Illuminate\Support\Facades\Storage::delete($file);
                                    }
                                }

                                // Log the email sent with complete information
                                Log::info('Project Closing Email Sent', [
                                    'sw_id' => $softwareHandover->formatted_handover_id,
                                    'company_name' => $lead->companyDetail?->company_name ?? 'N/A',
                                    'lead_id' => $lead->id,
                                    'subject' => $subject,
                                    'recipients' => $validRecipients,
                                    'sent_by' => $senderName,
                                    'sent_from' => $senderEmail,
                                    'template_id' => $data['email_template'],
                                    'attachments' => $attachedFiles,
                                    'attachment_count' => count($attachedFiles),
                                    'closed_handovers_count' => $closedCount,
                                    'timestamp' => now()->format('Y-m-d H:i:s')
                                ]);

                                $closedMessage = $closedCount > 1
                                    ? "and {$closedCount} software handovers marked as Closed."
                                    : "and software handover marked as Closed.";

                                Notification::make()
                                    ->title('Email Sent Successfully & Project Closed')
                                    ->success()
                                    ->body('Email sent to ' . count($validRecipients) . ' recipient(s) ' . $closedMessage)
                                    ->send();

                                // Refresh the page
                                $livewire->dispatch('$refresh');

                            } catch (\Exception $e) {
                                Log::error('Error sending closing email: ' . $e->getMessage());
                                Notification::make()
                                    ->title('Error sending email')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),

                    \Filament\Forms\Components\Actions\Action::make('downloadPdf')
                        ->label('Generate Project Plan PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->size(ActionSize::Small)
                        ->visible(function (Get $get, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                return false;
                            }

                            // ✅ Smart filtering: Same logic as view
                            $allHandovers = SoftwareHandover::where('lead_id', $leadId)
                                ->orderBy('created_at', 'desc')
                                ->get();

                            if ($allHandovers->isEmpty()) {
                                return false;
                            }

                            $latestHandover = $allHandovers->first();

                            // Apply same filtering logic
                            if ($latestHandover->status_handover !== 'Closed') {
                                $targetHandovers = $allHandovers->where('status_handover', '!=', 'Closed');
                            } else {
                                $targetHandovers = $allHandovers;
                            }

                            // Check if any handover has project plans (single query)
                            $swIds = $targetHandovers->pluck('id')->toArray();
                            return ProjectPlan::where('lead_id', $leadId)
                                ->whereIn('sw_id', $swIds)
                                ->exists();
                        })
                        ->action(function (Get $get, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Please save the lead first')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $lead = Lead::find($leadId);

                            // ✅ Apply same smart filtering
                            $allHandovers = SoftwareHandover::where('lead_id', $leadId)
                                ->orderBy('created_at', 'desc')
                                ->get();

                            if ($allHandovers->isEmpty()) {
                                Notification::make()
                                    ->title('No Software Handover Found')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $latestHandover = $allHandovers->first();

                            if ($latestHandover->status_handover !== 'Closed') {
                                $targetHandovers = $allHandovers->where('status_handover', '!=', 'Closed');
                            } else {
                                $targetHandovers = $allHandovers;
                            }

                            // Filter to only those with project plans
                            $softwareHandovers = $targetHandovers->filter(function ($handover) use ($leadId) {
                                return ProjectPlan::where('lead_id', $leadId)
                                    ->where('sw_id', $handover->id)
                                    ->exists();
                            });

                            if ($softwareHandovers->isEmpty()) {
                                Notification::make()
                                    ->title('No Software Handover Found')
                                    ->body('No software handovers with project plans found')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // If multiple handovers, generate combined Excel
                            if ($softwareHandovers->count() > 1) {
                                $filePath = self::generateCombinedProjectPlanExcel($lead, $softwareHandovers);
                            } else {
                                $filePath = self::generateProjectPlanExcel($lead, $softwareHandovers->first());
                            }

                            if ($filePath) {
                                // Update all handovers with generation timestamp
                                foreach ($softwareHandovers as $handover) {
                                    $handover->update([
                                        'project_plan_generated_at' => now(),
                                    ]);
                                }

                                // ✅ Get file details for the notification actions
                                $companyName = $lead->companyDetail?->company_name ?? 'Unknown';
                                $companySlug = \Illuminate\Support\Str::slug($companyName);

                                // Find the latest file
                                $files = \Illuminate\Support\Facades\Storage::disk('public')->files('project-plans');
                                $matchingFiles = [];

                                foreach ($files as $file) {
                                    if (str_contains($file, $companySlug)) {
                                        $fullPath = storage_path('app/public/' . $file);
                                        $matchingFiles[] = [
                                            'path' => $file,
                                            'modified' => file_exists($fullPath) ? filemtime($fullPath) : 0
                                        ];
                                    }
                                }

                                if (!empty($matchingFiles)) {
                                    usort($matchingFiles, function($a, $b) {
                                        return $b['modified'] - $a['modified'];
                                    });

                                    $latestFile = $matchingFiles[0];
                                    $fileName = basename($latestFile['path']);
                                    $fileFullPath = storage_path('app/public/' . $latestFile['path']);

                                    $handoverCount = $softwareHandovers->count();
                                    $bodyMessage = $handoverCount > 1
                                        ? "Combined project plan Excel file from {$handoverCount} software handovers has been generated."
                                        : 'Project plan Excel file has been generated.';

                                    // ✅ Notification with View and Download actions
                                    Notification::make()
                                        ->title('Excel File Generated Successfully')
                                        ->body($bodyMessage . ' Click below to view or download.')
                                        ->success()
                                        ->duration(10000) // 10 seconds to give time to click
                                        ->actions([
                                            \Filament\Notifications\Actions\Action::make('view')
                                                ->label('View in Office Online')
                                                ->icon('heroicon-o-eye')
                                                ->color('info')
                                                ->url(function () use ($latestFile) {
                                                    // ✅ Generate public URL for the file
                                                    $publicUrl = url('storage/' . $latestFile['path']);

                                                    // ✅ Use Office Web Viewer
                                                    return 'https://view.officeapps.live.com/op/view.aspx?src=' . urlencode($publicUrl);
                                                })
                                                ->openUrlInNewTab(),

                                            \Filament\Notifications\Actions\Action::make('download')
                                                ->label('Download')
                                                ->icon('heroicon-o-arrow-down-tray')
                                                ->color('success')
                                                ->url(function () use ($latestFile) {
                                                    return route('download.project-plan', [
                                                        'file' => basename($latestFile['path'])
                                                    ]);
                                                })
                                                ->openUrlInNewTab(),
                                        ])
                                        ->send();
                                } else {
                                    Notification::make()
                                        ->title('Excel File Generated')
                                        ->body('Project plan Excel file has been generated and saved.')
                                        ->success()
                                        ->send();
                                }
                            }
                        }),

                    \Filament\Forms\Components\Actions\Action::make('refreshModules')
                        ->label('Sync Project Plan')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->size(ActionSize::Small)
                        ->requiresConfirmation()
                        ->modalHeading('Sync Project Tasks')
                        ->modalDescription('This will create project tasks based on the latest software handover modules and admin-defined task templates. Phase 1 and Phase 2 will always be included.')
                        ->visible(function (Get $get, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                return false;
                            }

                            // ✅ Smart filtering: Check latest handover to determine visibility logic
                            $allHandovers = SoftwareHandover::where('lead_id', $leadId)
                                ->orderBy('created_at', 'desc')
                                ->get();

                            if ($allHandovers->isEmpty()) {
                                return false;
                            }

                            $latestHandover = $allHandovers->first();

                            // If latest handover is NOT closed, filter out closed ones
                            if ($latestHandover->status_handover !== 'Closed') {
                                $targetHandovers = $allHandovers->where('status_handover', '!=', 'Closed');
                            } else {
                                // If latest handover IS closed, show all handovers
                                $targetHandovers = $allHandovers;
                            }

                            if ($targetHandovers->isEmpty()) {
                                return false;
                            }

                            // Show sync button if there are target handovers without project plans (single query)
                            $swIds = $targetHandovers->pluck('id')->toArray();
                            $swIdsWithPlans = ProjectPlan::where('lead_id', $leadId)
                                ->whereIn('sw_id', $swIds)
                                ->distinct()
                                ->pluck('sw_id')
                                ->toArray();

                            // Show button if any handover doesn't have plans yet
                            return count($swIdsWithPlans) < count($swIds);
                        })
                        ->action(function (Set $set, Get $get, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Please save the lead first')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // ✅ Smart filtering: Check latest handover to determine target handovers
                            $allHandovers = SoftwareHandover::where('lead_id', $leadId)
                                ->orderBy('created_at', 'desc')
                                ->get();

                            if ($allHandovers->isEmpty()) {
                                Notification::make()
                                    ->title('No Software Handovers Found')
                                    ->body('Please create software handovers first to define project modules')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            $latestHandover = $allHandovers->first();

                            // If latest handover is NOT closed, filter out closed ones
                            if ($latestHandover->status_handover !== 'Closed') {
                                $softwareHandovers = $allHandovers->where('status_handover', '!=', 'Closed');
                            } else {
                                // If latest handover IS closed, show all handovers
                                $softwareHandovers = $allHandovers;
                            }

                            $totalCreatedCount = 0;
                            $syncedHandovers = [];

                            foreach ($softwareHandovers as $softwareHandover) {
                                $selectedModules = $softwareHandover->getSelectedModules();
                                $modulesToSync = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));
                                $createdCount = self::createProjectPlansForModules($leadId, $softwareHandover->id, $modulesToSync);

                                if ($createdCount > 0) {
                                    $totalCreatedCount += $createdCount;
                                    $syncedHandovers[] = [
                                        'id' => $softwareHandover->id,
                                        'modules' => $modulesToSync,
                                        'count' => $createdCount
                                    ];
                                }
                            }

                            $set('refresh_trigger', time());

                            if ($totalCreatedCount > 0) {
                                $handoverCount = count($syncedHandovers);
                                Notification::make()
                                    ->title('Tasks Synced Successfully')
                                    ->body("Synced {$totalCreatedCount} tasks from {$handoverCount} software handover(s)")
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No New Tasks Created')
                                    ->body('All non-closed software handovers already have their project plans synced')
                                    ->info()
                                    ->send();
                            }
                        }),

                    \Filament\Forms\Components\Actions\Action::make('setTaskDates')
                        ->label('Update Project Plan')
                        ->icon('heroicon-o-calendar')
                        ->color('success')
                        ->size(ActionSize::Small)
                        ->closeModalByClickingAway(false)
                        ->visible(function (Get $get, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                return false;
                            }

                            // ✅ Smart filtering: Check latest handover to determine visibility logic
                            $allHandovers = SoftwareHandover::where('lead_id', $leadId)
                                ->orderBy('created_at', 'desc')
                                ->get();

                            if ($allHandovers->isEmpty()) {
                                return false;
                            }

                            $latestHandover = $allHandovers->first();

                            // If latest handover is NOT closed, filter out closed ones
                            if ($latestHandover->status_handover !== 'Closed') {
                                $targetHandovers = $allHandovers->where('status_handover', '!=', 'Closed');
                            } else {
                                // If latest handover IS closed, show all handovers
                                $targetHandovers = $allHandovers;
                            }

                            if ($targetHandovers->isEmpty()) {
                                return false;
                            }

                            // Check if any target handover has project plans (single query)
                            $swIds = $targetHandovers->pluck('id')->toArray();
                            return ProjectPlan::where('lead_id', $leadId)
                                ->whereIn('sw_id', $swIds)
                                ->exists();
                        })
                        ->form(function (Get $get, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                return [
                                    \Filament\Forms\Components\Placeholder::make('no_lead')
                                        ->content('No lead selected. Please save the lead first.')
                                ];
                            }

                            // ✅ Smart filtering: Check latest handover to determine target handovers
                            $allHandovers = SoftwareHandover::where('lead_id', $leadId)
                                ->orderBy('created_at', 'desc')
                                ->get();

                            if ($allHandovers->isEmpty()) {
                                return [
                                    \Filament\Forms\Components\Placeholder::make('no_sw')
                                        ->content('No software handovers found. Please create software handovers first.')
                                ];
                            }

                            $latestHandover = $allHandovers->first();

                            // If latest handover is NOT closed, filter out closed ones
                            if ($latestHandover->status_handover !== 'Closed') {
                                $softwareHandovers = $allHandovers->where('status_handover', '!=', 'Closed');
                            } else {
                                // If latest handover IS closed, show all handovers
                                $softwareHandovers = $allHandovers;
                            }

                            if ($softwareHandovers->isEmpty()) {
                                return [
                                    \Filament\Forms\Components\Placeholder::make('no_active_sw')
                                        ->content('No suitable software handovers found.')
                                ];
                            }

                            // Collect all selected modules from target handovers
                            $allSelectedModules = [];
                            foreach ($softwareHandovers as $handover) {
                                $selectedModules = $handover->getSelectedModules();
                                $allSelectedModules = array_merge($allSelectedModules, $selectedModules);
                            }

                            $allModules = array_unique(array_merge(['phase 1', 'phase 2'], $allSelectedModules));

                            // ✅ Get all unique module_names from selected modules
                            $moduleNames = ProjectTask::whereIn('module', $allModules)
                                ->where('is_active', true)
                                ->select('module_name', 'module_order')
                                ->distinct()
                                ->orderBy('module_order')
                                ->orderBy('module_name')
                                ->get()
                                ->pluck('module_name')
                                ->toArray();

                            // Get all project plans from target software handovers (including completed)
                            $swIds = $softwareHandovers->pluck('id')->toArray();
                            $projectPlans = ProjectPlan::where('lead_id', $leadId)
                                ->whereIn('sw_id', $swIds)
                                ->whereHas('projectTask', function ($query) use ($moduleNames) {
                                    $query->whereIn('module_name', $moduleNames)
                                        ->where('is_active', true);
                                })
                                ->with('projectTask')
                                ->orderBy('id')
                                ->get();

                            if ($projectPlans->isEmpty()) {
                                return [
                                    \Filament\Forms\Components\Placeholder::make('no_plans')
                                        ->content('No project plans found.')
                                ];
                            }

                            $schema = [];

                            // ✅ Group by module_name (not module)
                            foreach ($moduleNames as $moduleName) {
                                $modulePlans = $projectPlans->filter(function ($plan) use ($moduleName) {
                                    return $plan->projectTask && $plan->projectTask->module_name === $moduleName;
                                });

                                // Only show module if it has incomplete tasks
                                if ($modulePlans->isNotEmpty()) {
                                    $firstTask = $modulePlans->first()->projectTask;
                                    $modulePercentage = $firstTask->module_percentage;
                                    $moduleOrder = $firstTask->module_order ?? 999;

                                    // Calculate module progress from already-loaded collection
                                    $totalModuleTasks = $modulePlans->count();
                                    $completedModuleTasks = $modulePlans->where('status', 'completed')->count();
                                    $pendingTasks = $totalModuleTasks - $completedModuleTasks;
                                    $progressPercentage = $totalModuleTasks > 0 ? round(($completedModuleTasks / $totalModuleTasks) * 100) : 0;

                                    // Auto-expand logic
                                    $isExpanded = false;
                                    if ($progressPercentage > 0 && $progressPercentage < 100) {
                                        $isExpanded = true;
                                    } elseif ($progressPercentage == 0) {
                                        static $firstPendingExpanded = false;
                                        if (!$firstPendingExpanded) {
                                            $isExpanded = true;
                                            $firstPendingExpanded = true;
                                        }
                                    }

                                    $tableRows = [];

                                    $tableRows[] = [
                                        \Filament\Forms\Components\Placeholder::make("header_{$moduleName}_task")
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div>
                                                    <strong style="font-size: 14px;">Task Name</strong>
                                                </div>'
                                            ))
                                            ->columnSpan(4),

                                        \Filament\Forms\Components\Placeholder::make("header_{$moduleName}_plan_date")
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div>
                                                    <strong style="font-size: 14px;">Planned Date</strong>
                                                </div>'
                                            ))
                                            ->columnSpan(6),

                                        \Filament\Forms\Components\Placeholder::make("header_{$moduleName}_actual_date")
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div>
                                                    <strong style="font-size: 14px;">Actual Start Date</strong>
                                                </div>'
                                            ))
                                            ->columnSpan(4),

                                        \Filament\Forms\Components\Placeholder::make("header_{$moduleName}_actual_date")
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString(
                                                '<div>
                                                    <strong style="font-size: 14px;">Actual End Date</strong>
                                                </div>'
                                            ))
                                            ->columnSpan(4),
                                    ];

                                    foreach ($modulePlans as $plan) {
                                        $task = $plan->projectTask;

                                        $planDateRangeValue = null;
                                        if ($plan->plan_start_date && $plan->plan_end_date) {
                                            $planDateRangeValue = \Carbon\Carbon::parse($plan->plan_start_date)->format('d/m/Y') . ' - ' .
                                                                  \Carbon\Carbon::parse($plan->plan_end_date)->format('d/m/Y');
                                        }

                                        $actualDateRangeValue = null;
                                        if ($plan->actual_start_date && $plan->actual_end_date) {
                                            $actualDateRangeValue = \Carbon\Carbon::parse($plan->actual_start_date)->format('d/m/Y') . ' - ' .
                                                                    \Carbon\Carbon::parse($plan->actual_end_date)->format('d/m/Y');
                                        }

                                        $tableRows[] = [
                                            TextInput::make("plan_{$plan->id}_task")
                                                ->hiddenLabel()
                                                ->default($task->task_name)
                                                ->disabled()
                                                ->extraAttributes([
                                                    'title' => 'Task Percentage: ' . ($task->task_percentage ?? 0) . '%',
                                                    'style' => 'cursor: help;'
                                                ])
                                                ->columnSpan(4),

                                            TextInput::make("plan_{$plan->id}_plan_date_range")
                                                ->hiddenLabel()
                                                ->default($planDateRangeValue)
                                                ->placeholder('dd/mm/yyyy - dd/mm/yyyy')
                                                ->suffixAction(
                                                    \Filament\Forms\Components\Actions\Action::make('selectPlanDateRange')
                                                        ->icon('heroicon-m-calendar')
                                                        ->tooltip('Select date range')
                                                        ->modalHeading('Select Planned Date Range')
                                                        ->modalWidth('md')
                                                        ->form([
                                                            DateRangePicker::make('date_range')
                                                                ->label('Planned Date Range')
                                                                ->format('d/m/Y')
                                                                ->displayFormat('DD/MM/YYYY')
                                                                ->required()
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->action(function (array $data, Set $set) use ($plan) {
                                                            if (!isset($data['date_range']) || !$data['date_range']) {
                                                                Notification::make()
                                                                    ->title('Invalid Date Range')
                                                                    ->body('Please select a valid date range')
                                                                    ->warning()
                                                                    ->send();
                                                                return;
                                                            }

                                                            $dateRange = $data['date_range'];

                                                            try {
                                                                $set("plan_{$plan->id}_plan_date_range", $dateRange);

                                                                [$start, $end] = explode(' - ', $dateRange);
                                                                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($start));
                                                                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($end));

                                                                $weekdays = self::calculateWeekdays($startDate, $endDate);
                                                                $set("plan_{$plan->id}_plan_duration", $weekdays);

                                                                if ($plan->status === 'pending') {
                                                                    $set("plan_{$plan->id}_status", 'in_progress');
                                                                }

                                                                Notification::make()
                                                                    ->title('Planned Dates Set')
                                                                    ->body("Duration: {$weekdays} days")
                                                                    ->success()
                                                                    ->send();
                                                            } catch (\Exception $e) {
                                                                Notification::make()
                                                                    ->title('Invalid Date Format')
                                                                    ->body('Please select a valid date range in DD/MM/YYYY format')
                                                                    ->danger()
                                                                    ->send();
                                                            }
                                                        })
                                                )
                                                ->columnSpan(6),

                                            TextInput::make("plan_{$plan->id}_actual_start_date")
                                                ->hiddenLabel()
                                                ->default($plan->actual_start_date ? \Carbon\Carbon::parse($plan->actual_start_date)->format('d/m/Y') : '')
                                                ->placeholder('dd/mm/yyyy')
                                                ->rules([
                                                    fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($plan, $get) {
                                                        if (!$value) return;
                                                        $plannedRange = $get("plan_{$plan->id}_plan_date_range");
                                                        if (!$plannedRange && !$plan->plan_start_date) {
                                                            $fail("Planned date is required before setting actual start date.");
                                                        }
                                                    },
                                                ])
                                                ->suffixAction(
                                                    \Filament\Forms\Components\Actions\Action::make('selectActualStartDate')
                                                        ->icon('heroicon-m-calendar')
                                                        ->tooltip('Select start date')
                                                        ->modalHeading('Select Actual Start Date')
                                                        ->modalWidth('md')
                                                        ->form([
                                                            DatePicker::make('start_date')
                                                                ->label('Actual Start Date')
                                                                ->format('Y-m-d')
                                                                ->native(false)
                                                                ->displayFormat('d/m/Y')
                                                                ->columnSpanFull(),
                                                        ])
                                                        ->action(function (array $data, Set $set, Get $get) use ($plan) {
                                                            if (!isset($data['start_date']) || !$data['start_date']) {
                                                                $set("plan_{$plan->id}_actual_start_date", null);
                                                                $set("plan_{$plan->id}_actual_end_date", null);
                                                                return;
                                                            }

                                                            $startDate = $data['start_date'];

                                                            try {
                                                                $start = \Carbon\Carbon::parse($startDate);
                                                                $set("plan_{$plan->id}_actual_start_date", $start->format('d/m/Y'));

                                                                $endDateDisplay = $get("plan_{$plan->id}_actual_end_date");
                                                                if ($endDateDisplay) {
                                                                    try {
                                                                        $end = \Carbon\Carbon::createFromFormat('d/m/Y', $endDateDisplay);
                                                                        if ($end->lt($start)) {
                                                                            $set("plan_{$plan->id}_actual_end_date", null);
                                                                        }
                                                                    } catch (\Exception $e) {}
                                                                }
                                                            } catch (\Exception $e) {
                                                                Notification::make()
                                                                    ->title('Invalid Date Format')
                                                                    ->body('Please select a valid date')
                                                                    ->danger()
                                                                    ->send();
                                                            }
                                                        })
                                                )
                                                ->columnSpan(4),

                                            TextInput::make("plan_{$plan->id}_actual_end_date")
                                                ->hiddenLabel()
                                                ->default($plan->actual_end_date ? \Carbon\Carbon::parse($plan->actual_end_date)->format('d/m/Y') : '')
                                                ->placeholder('dd/mm/yyyy')
                                                ->rules([
                                                    fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($plan, $get) {
                                                        if (!$value) return;
                                                        $actualStart = $get("plan_{$plan->id}_actual_start_date");
                                                        if (!$actualStart) {
                                                            $fail("Actual start date is required before setting actual end date.");
                                                        }
                                                    },
                                                ])
                                                ->suffixAction(
                                                    \Filament\Forms\Components\Actions\Action::make('selectActualEndDate')
                                                        ->icon('heroicon-m-calendar')
                                                        ->tooltip('Select end date')
                                                        ->modalHeading('Select Actual End Date')
                                                        ->modalWidth('md')
                                                        ->form(function (Get $get) use ($plan) {
                                                            $startDateDisplay = $get("plan_{$plan->id}_actual_start_date");
                                                            $minDate = null;

                                                            if ($startDateDisplay) {
                                                                try {
                                                                    $minDate = \Carbon\Carbon::createFromFormat('d/m/Y', $startDateDisplay);
                                                                } catch (\Exception $e) {
                                                                    // Invalid start date format
                                                                }
                                                            }

                                                            return [
                                                                DatePicker::make('end_date')
                                                                    ->label('Actual End Date (leave empty to clear)')
                                                                    ->format('Y-m-d')
                                                                    ->displayFormat('d/m/Y')
                                                                    ->native(false)
                                                                    ->minDate($minDate ? $minDate->subDay() : null)
                                                                    ->columnSpanFull(),
                                                            ];
                                                        })
                                                        ->action(function (array $data, Set $set, Get $get) use ($plan) {
                                                            if (!isset($data['end_date']) || !$data['end_date']) {
                                                                $set("plan_{$plan->id}_actual_end_date", null);
                                                                return;
                                                            }

                                                            $endDate = $data['end_date'];
                                                            $startDateDisplay = $get("plan_{$plan->id}_actual_start_date");

                                                            if (!$startDateDisplay) {
                                                                Notification::make()
                                                                    ->title('Start Date Required')
                                                                    ->body('Please select actual start date first')
                                                                    ->warning()
                                                                    ->send();
                                                                return;
                                                            }

                                                            try {
                                                                $start = \Carbon\Carbon::createFromFormat('d/m/Y', $startDateDisplay)->startOfDay();
                                                                $end = \Carbon\Carbon::parse($endDate)->startOfDay();

                                                                if ($end->lt($start)) {
                                                                    Notification::make()
                                                                        ->title('Invalid Date Range')
                                                                        ->body('End date cannot be before start date')
                                                                        ->danger()
                                                                        ->send();
                                                                    return;
                                                                }

                                                                $set("plan_{$plan->id}_actual_end_date", $end->format('d/m/Y'));
                                                            } catch (\Exception $e) {
                                                                Notification::make()
                                                                    ->title('Invalid Date Format')
                                                                    ->body('Error: ' . $e->getMessage())
                                                                    ->danger()
                                                                    ->send();
                                                            }
                                                        })
                                                )
                                                ->columnSpan(4),

                                            Textarea::make("plan_{$plan->id}_remarks")
                                                ->hiddenLabel()
                                                ->default($plan->remarks)
                                                ->placeholder('Add remarks...')
                                                ->rows(2)
                                                ->autosize()
                                                ->extraAlpineAttributes([
                                                    'x-on:input' => '
                                                        const start = $el.selectionStart;
                                                        const end = $el.selectionEnd;
                                                        const value = $el.value;
                                                        $el.value = value.toUpperCase();
                                                        $el.setSelectionRange(start, end);
                                                    '
                                                ])
                                                ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                                ->columnSpan(16),

                                            Placeholder::make("plan_{$plan->id}_status")
                                                ->hiddenLabel()
                                                ->content(function (Get $get) use ($plan) {
                                                    // Map status codes to display labels
                                                    $statusLabels = [
                                                        'pending' => 'Pending',
                                                        'in_progress' => 'In Progress',
                                                        'completed' => 'Completed',
                                                        'on_hold' => 'On Hold',
                                                    ];

                                                    // ✅ Try to get the current state from the form first, fallback to database value
                                                    $currentStatus = $get("plan_{$plan->id}_status") ?? $plan->status;
                                                    $statusLabel = $statusLabels[$currentStatus] ?? ucfirst(str_replace('_', ' ', $currentStatus));

                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div style="text-align: center;">
                                                            ' . $statusLabel . '
                                                        </div>'
                                                    );
                                                })
                                                ->columnSpan(2),
                                        ];
                                    }

                                    $moduleSchema = [];

                                    // Task rows
                                    foreach ($tableRows as $row) {
                                        $moduleSchema[] = \Filament\Forms\Components\Grid::make(18)
                                            ->schema($row);
                                    }

                                    // Add module section
                                    $schema[] = Section::make($moduleName)
                                        ->heading(new \Illuminate\Support\HtmlString('
                                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                                <span>' . e($moduleName) . '</span>
                                                <span style="color: #dc2626; font-weight: bold; font-size: 14px;">
                                                    Progress: ' . $completedModuleTasks . '/' . $totalModuleTasks . '
                                                </span>
                                            </div>
                                        '))
                                        ->collapsible()
                                        ->collapsed(!$isExpanded)
                                        ->persistCollapsed()
                                        ->schema($moduleSchema);
                                }
                            }

                            return $schema;
                        })
                        ->action(function (array $data, Get $get, Set $set, $livewire) {
                            $leadId = $livewire->record?->id ?? $get('id') ?? 0;

                            if ($leadId === 0) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('No lead ID found')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $updatedCount = 0;

                            DB::transaction(function () use ($data, $leadId, &$updatedCount) {
                                foreach ($data as $key => $value) {
                                    if (preg_match('/plan_(\d+)_plan_date_range/', $key, $matches)) {
                                        $planId = $matches[1];
                                        $plan = ProjectPlan::find($planId);

                                        if ($plan && $plan->lead_id == $leadId && $value) {
                                            try {
                                                [$start, $end] = explode(' - ', $value);
                                                $startDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($start))->format('Y-m-d');
                                                $endDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($end))->format('Y-m-d');

                                                // Update all project plans with the same project_task_id and lead_id
                                                $affectedPlans = ProjectPlan::where('lead_id', $leadId)
                                                    ->where('project_task_id', $plan->project_task_id)
                                                    ->get();

                                                foreach ($affectedPlans as $affectedPlan) {
                                                    $affectedPlan->plan_start_date = $startDate;
                                                    $affectedPlan->plan_end_date = $endDate;

                                                    if ($affectedPlan->status === 'pending') {
                                                        $affectedPlan->status = 'in_progress';
                                                    }

                                                    $affectedPlan->save();
                                                    $affectedPlan->calculatePlanDuration();
                                                    $updatedCount++;
                                                }
                                            } catch (\Exception $e) {
                                                // Invalid date format
                                            }
                                        }
                                    }
                                    elseif (preg_match('/plan_(\d+)_actual_start_date/', $key, $matches)) {
                                        $planId = $matches[1];
                                        $plan = ProjectPlan::find($planId);

                                        if ($plan && $plan->lead_id == $leadId) {
                                            // Parse new value
                                            $newStartDate = null;
                                            if ($value) {
                                                try {
                                                    $newStartDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($value))->format('Y-m-d');
                                                } catch (\Exception $e) {
                                                    continue;
                                                }
                                            }

                                            // Compare with current DB value — skip if no change
                                            $currentStartDate = $plan->actual_start_date ? \Carbon\Carbon::parse($plan->actual_start_date)->format('Y-m-d') : null;
                                            if ($newStartDate === $currentStartDate) {
                                                continue;
                                            }

                                            $affectedPlans = ProjectPlan::where('lead_id', $leadId)
                                                ->where('project_task_id', $plan->project_task_id)
                                                ->get();

                                            foreach ($affectedPlans as $affectedPlan) {
                                                $changed = false;

                                                if ($newStartDate) {
                                                    // Setting start date
                                                    $affectedPlan->actual_start_date = $newStartDate;
                                                    $changed = true;

                                                    if ($affectedPlan->status === 'pending') {
                                                        $affectedPlan->status = 'in_progress';
                                                    }
                                                } else {
                                                    // Clearing start date — only null fields that have values
                                                    if ($affectedPlan->actual_start_date !== null) {
                                                        $affectedPlan->actual_start_date = null;
                                                        $changed = true;
                                                    }
                                                    if ($affectedPlan->actual_end_date !== null) {
                                                        $affectedPlan->actual_end_date = null;
                                                        $changed = true;
                                                    }
                                                    if ($affectedPlan->actual_duration !== null) {
                                                        $affectedPlan->actual_duration = null;
                                                        $changed = true;
                                                    }
                                                    if ($affectedPlan->status !== 'pending') {
                                                        $affectedPlan->status = 'pending';
                                                        $changed = true;
                                                    }
                                                }

                                                if ($changed) {
                                                    $affectedPlan->save();
                                                    $updatedCount++;
                                                }
                                            }
                                        }
                                    }
                                    elseif (preg_match('/plan_(\d+)_actual_end_date/', $key, $matches)) {
                                        $planId = $matches[1];
                                        $plan = ProjectPlan::find($planId);

                                        if ($plan && $plan->lead_id == $leadId) {
                                            // Parse new value
                                            $newEndDate = null;
                                            if ($value) {
                                                try {
                                                    $newEndDate = \Carbon\Carbon::createFromFormat('d/m/Y', trim($value))->format('Y-m-d');
                                                } catch (\Exception $e) {
                                                    continue;
                                                }
                                            }

                                            // Compare with current DB value — skip if no change
                                            $currentEndDate = $plan->actual_end_date ? \Carbon\Carbon::parse($plan->actual_end_date)->format('Y-m-d') : null;
                                            if ($newEndDate === $currentEndDate) {
                                                continue;
                                            }

                                            $affectedPlans = ProjectPlan::where('lead_id', $leadId)
                                                ->where('project_task_id', $plan->project_task_id)
                                                ->get();

                                            foreach ($affectedPlans as $affectedPlan) {
                                                $changed = false;

                                                if ($newEndDate) {
                                                    // Setting end date
                                                    $affectedPlan->actual_end_date = $newEndDate;
                                                    $changed = true;

                                                    if ($affectedPlan->status !== 'completed') {
                                                        $affectedPlan->status = 'completed';
                                                    }

                                                    $affectedPlan->save();
                                                    $affectedPlan->calculateActualDuration();
                                                } else {
                                                    // Clearing end date — only null fields that have values
                                                    if ($affectedPlan->actual_end_date !== null) {
                                                        $affectedPlan->actual_end_date = null;
                                                        $changed = true;
                                                    }
                                                    if ($affectedPlan->actual_duration !== null) {
                                                        $affectedPlan->actual_duration = null;
                                                        $changed = true;
                                                    }

                                                    $expectedStatus = $affectedPlan->actual_start_date ? 'in_progress' : 'pending';
                                                    if ($affectedPlan->status !== $expectedStatus) {
                                                        $affectedPlan->status = $expectedStatus;
                                                        $changed = true;
                                                    }

                                                    if ($changed) {
                                                        $affectedPlan->save();
                                                    }
                                                }

                                                if ($changed) {
                                                    $updatedCount++;
                                                }
                                            }
                                        }
                                    }
                                    elseif (preg_match('/plan_(\d+)_status/', $key, $matches)) {
                                        $planId = $matches[1];
                                        $plan = ProjectPlan::find($planId);

                                        if ($plan && $plan->lead_id == $leadId) {
                                            // Update all project plans with the same project_task_id and lead_id
                                            $affectedPlans = ProjectPlan::where('lead_id', $leadId)
                                                ->where('project_task_id', $plan->project_task_id)
                                                ->get();

                                            foreach ($affectedPlans as $affectedPlan) {
                                                $affectedPlan->status = $value;
                                                $affectedPlan->save();
                                                $updatedCount++;
                                            }
                                        }
                                    }
                                    elseif (preg_match('/plan_(\d+)_remarks/', $key, $matches)) {
                                        $planId = $matches[1];
                                        $plan = ProjectPlan::find($planId);

                                        if ($plan && $plan->lead_id == $leadId) {
                                            // Update all project plans with the same project_task_id and lead_id
                                            $affectedPlans = ProjectPlan::where('lead_id', $leadId)
                                                ->where('project_task_id', $plan->project_task_id)
                                                ->get();

                                            foreach ($affectedPlans as $affectedPlan) {
                                                $affectedPlan->remarks = $value;
                                                $affectedPlan->save();
                                                $updatedCount++;
                                            }
                                        }
                                    }
                                }
                            });

                            $set('refresh_trigger', time());

                            Notification::make()
                                ->title('Tasks Updated Successfully')
                                ->body("Updated {$updatedCount} field(s). The progress view will refresh automatically.")
                                ->success()
                                ->send();

                            $livewire->dispatch('refresh-project-progress');
                        })
                        ->modalWidth('7xl')
                        ->slideOver()
                        ->after(function ($livewire) {
                            // ✅ Force a full component refresh after modal closes
                            $livewire->dispatch('$refresh');
                        }),
                ])
                ->schema([
                    \Filament\Forms\Components\Hidden::make('refresh_trigger')
                        ->default(0)
                        ->live(),

                    ViewField::make('project_progress_view')
                        ->view('filament.resources.lead-resource.tabs.project-progress-view')
                        ->live()
                        ->dehydrated(false),
                ])
        ];
    }

    protected static function generateProjectPlanExcel(Lead $lead, SoftwareHandover $softwareHandover): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $companyName = $lead->companyDetail?->company_name ?? 'Unknown Company';
        $implementerName = $softwareHandover->implementer ?? 'Not Assigned';

        $spreadsheet->getProperties()
            ->setCreator('TimeTec CRM')
            ->setTitle("Project Plan - {$companyName}")
            ->setSubject('Project Implementation Plan');

        $currentRow = 1;

        // Row 1: Company Name
        $sheet->setCellValue("A{$currentRow}", 'Company Name');
        $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
        $sheet->setCellValue("C{$currentRow}", $companyName);
        $sheet->mergeCells("C{$currentRow}:K{$currentRow}");
        $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $currentRow++;

        // Row 2: Implementer Name
        $sheet->setCellValue("A{$currentRow}", 'Implementer Name');
        $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
        $sheet->setCellValue("C{$currentRow}", $implementerName);
        $sheet->mergeCells("C{$currentRow}:K{$currentRow}");
        $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $currentRow++;

        // Row 3: Project Progress Overview
        $sheet->setCellValue("A{$currentRow}", 'Project Progress Overview');
        $sheet->mergeCells("A{$currentRow}:K{$currentRow}");
        $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(30);
        $currentRow++;

        // Add empty row for spacing
        $currentRow++;

        $selectedModules = $softwareHandover->getSelectedModules();
        $allModules = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));

        $moduleNames = ProjectTask::whereIn('module', $allModules)
            ->where('is_active', true)
            ->select('module_name', 'module_order', 'module_percentage', 'module')
            ->distinct()
            ->orderBy('module_order')
            ->orderBy('module_name')
            ->get();

        foreach ($moduleNames as $moduleData) {
            $moduleName = $moduleData->module_name;
            $modulePercentage = $moduleData->module_percentage;
            $module = $moduleData->module;

            $modulePlans = ProjectPlan::where('lead_id', $lead->id)
                ->where('sw_id', $softwareHandover->id)
                ->whereHas('projectTask', function ($query) use ($moduleName) {
                    $query->where('module_name', $moduleName)
                        ->where('is_active', true);
                })
                ->with('projectTask')
                ->orderBy('id')
                ->get();

            if ($modulePlans->isEmpty()) {
                continue;
            }

            // ✅ First row: Plan and Actual headers only (E-J), K is empty
            $sheet->setCellValue("E{$currentRow}", 'Plan');
            $sheet->mergeCells("E{$currentRow}:G{$currentRow}");

            $sheet->setCellValue("H{$currentRow}", 'Actual');
            $sheet->mergeCells("H{$currentRow}:J{$currentRow}");

            $sheet->getStyle("E{$currentRow}:G{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ]);

            $sheet->getStyle("H{$currentRow}:J{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00FF00']],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ]);

            $currentRow++;

            // ✅ Second row: Module code + Module name + Sub-headers + Remarks
            $sheet->setCellValue("A{$currentRow}", ucfirst(strtolower($module)));
            $sheet->setCellValue("B{$currentRow}", $moduleName);
            $sheet->setCellValue("C{$currentRow}", 'Status');
            $sheet->setCellValue("D{$currentRow}", $modulePercentage . '%');

            $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B0F0']],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ]);

            // ✅ Sub-headers WITH REMARKS
            $headers = ['Start Date', 'End Date', 'Duration', 'Start Date', 'End Date', 'Duration', 'Remarks'];
            $col = 'E';
            foreach ($headers as $header) {
                $sheet->setCellValue("{$col}{$currentRow}", $header);

                if (in_array($col, ['E', 'F', 'G'])) {
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                    ]);
                } elseif (in_array($col, ['H', 'I', 'J'])) {
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00FF00']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                    ]);
                } elseif ($col === 'K') {
                    // ✅ Remarks column styling
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE699']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                    ]);
                }

                $col++;
            }

            $currentRow++;

            // Task rows
            $taskNumber = 1;
            foreach ($modulePlans as $plan) {
                $task = $plan->projectTask;

                $sheet->setCellValue("A{$currentRow}", $taskNumber);
                $sheet->setCellValue("B{$currentRow}", $task->task_name);
                $sheet->setCellValue("C{$currentRow}", ucfirst($plan->status));
                $sheet->setCellValue("D{$currentRow}", ($task->task_percentage ?? 0) . '%');

                $sheet->getStyle("D{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Plan dates
                $sheet->setCellValue("E{$currentRow}", $plan->plan_start_date ? \Carbon\Carbon::parse($plan->plan_start_date)->format('d/m/Y') : '');
                $sheet->setCellValue("F{$currentRow}", $plan->plan_end_date ? \Carbon\Carbon::parse($plan->plan_end_date)->format('d/m/Y') : '');
                $sheet->setCellValue(
                    "G{$currentRow}",
                    ($plan->plan_start_date && $plan->plan_end_date)
                        ? self::calculateWeekdays(
                            \Carbon\Carbon::parse($plan->plan_start_date),
                            \Carbon\Carbon::parse($plan->plan_end_date)
                        )
                        : ''
                );

                // Actual dates
                $sheet->setCellValue("H{$currentRow}", $plan->actual_start_date ? \Carbon\Carbon::parse($plan->actual_start_date)->format('d/m/Y') : '');
                $sheet->setCellValue("I{$currentRow}", $plan->actual_end_date ? \Carbon\Carbon::parse($plan->actual_end_date)->format('d/m/Y') : '');
                $sheet->setCellValue(
                    "J{$currentRow}",
                    ($plan->actual_start_date && $plan->actual_end_date)
                        ? self::calculateWeekdays(
                            \Carbon\Carbon::parse($plan->actual_start_date),
                            \Carbon\Carbon::parse($plan->actual_end_date)
                        )
                        : ''
                );

                // ✅ Remarks column
                $sheet->setCellValue("K{$currentRow}", $plan->remarks ?? '');
                $sheet->getStyle("K{$currentRow}")->getAlignment()->setWrapText(true);

                // ✅ Add borders to all columns including Remarks
                $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ]);

                $currentRow++;
                $taskNumber++;
            }

            $currentRow++;
        }

        // Auto-size columns A-J
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ✅ Set fixed width for Remarks column
        $sheet->getColumnDimension('K')->setWidth(40);

        // Save to public storage
        $companySlug = \Illuminate\Support\Str::slug($companyName);
        $timestamp = now()->format('Y-m-d_His');
        $filename = "Project_Plan_{$companySlug}_{$timestamp}.xlsx";
        $directory = 'project-plans';
        $filePath = "{$directory}/{$filename}";

        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($directory);

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        \Illuminate\Support\Facades\Storage::disk('public')->put(
            $filePath,
            file_get_contents($tempFile)
        );

        unlink($tempFile);

        \Illuminate\Support\Facades\Log::info("Project plan Excel generated", [
            'lead_id' => $lead->id,
            'company_name' => $companyName,
            'file_path' => $filePath,
            'filename' => $filename,
        ]);

        return storage_path('app/public/' . $filePath);
    }

    protected static function generateCombinedProjectPlanExcel(Lead $lead, $softwareHandovers): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set document properties
        $companyName = $lead->companyDetail?->company_name ?? 'Unknown Company';
        $handoverCount = $softwareHandovers->count();

        $spreadsheet->getProperties()
            ->setCreator('TimeTec CRM')
            ->setTitle("Combined Project Plan - {$companyName}")
            ->setSubject('Combined Project Implementation Plan');

        $currentRow = 1;

        // Row 1: Company Name
        $sheet->setCellValue("A{$currentRow}", 'Company Name');
        $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
        $sheet->setCellValue("C{$currentRow}", $companyName);
        $sheet->mergeCells("C{$currentRow}:K{$currentRow}");
        $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E9']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $currentRow++;

        // Row 2: Software Handover Count
        $sheet->setCellValue("A{$currentRow}", 'Software Handovers');
        $sheet->mergeCells("A{$currentRow}:B{$currentRow}");
        $sheet->setCellValue("C{$currentRow}", "{$handoverCount} Active Handovers Combined");
        $sheet->mergeCells("C{$currentRow}:K{$currentRow}");
        $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $currentRow++;

        // Row 3: Project Progress Overview
        $sheet->setCellValue("A{$currentRow}", 'Combined Project Progress Overview');
        $sheet->mergeCells("A{$currentRow}:K{$currentRow}");
        $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1976D2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
            ],
        ]);
        $sheet->getRowDimension($currentRow)->setRowHeight(30);
        $currentRow++;

        // Add empty row for spacing
        $currentRow++;

        // Collect all modules from all handovers
        $allSelectedModules = [];
        $swIds = [];
        foreach ($softwareHandovers as $handover) {
            $handoverModules = $handover->getSelectedModules();
            $allSelectedModules = array_merge($allSelectedModules, $handoverModules);
            $swIds[] = $handover->id;
        }

        $allModules = array_unique(array_merge(['phase 1', 'phase 2'], $allSelectedModules));

        $moduleNames = ProjectTask::whereIn('module', $allModules)
            ->where('is_active', true)
            ->select('module_name', 'module_order', 'module_percentage', 'module')
            ->distinct()
            ->orderBy('module_order')
            ->orderBy('module_name')
            ->get();

        foreach ($moduleNames as $moduleData) {
            $moduleName = $moduleData->module_name;
            $modulePercentage = $moduleData->module_percentage;
            $module = $moduleData->module;

            // Get plans from ALL software handovers for this module
            $modulePlans = ProjectPlan::where('lead_id', $lead->id)
                ->whereIn('sw_id', $swIds)
                ->whereHas('projectTask', function ($query) use ($moduleName) {
                    $query->where('module_name', $moduleName)
                        ->where('is_active', true);
                })
                ->with('projectTask')
                ->orderBy('id')
                ->get();

            if ($modulePlans->isEmpty()) {
                continue;
            }

            // Add software handover info for this module
            $moduleHandoverIds = $modulePlans->pluck('sw_id')->unique()->toArray();
            $moduleHandoverCount = count($moduleHandoverIds);
            $handoverInfo = $moduleHandoverCount > 1 ? " (from {$moduleHandoverCount} handovers)" : '';

            // ✅ First row: Plan and Actual headers only (E-J), K is empty
            $sheet->setCellValue("E{$currentRow}", 'Plan');
            $sheet->mergeCells("E{$currentRow}:G{$currentRow}");

            $sheet->setCellValue("H{$currentRow}", 'Actual');
            $sheet->mergeCells("H{$currentRow}:J{$currentRow}");

            $sheet->getStyle("E{$currentRow}:G{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ]);

            $sheet->getStyle("H{$currentRow}:J{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00FF00']],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ]);

            $currentRow++;

            // ✅ Second row: Module code + Module name + Sub-headers + Remarks
            $sheet->setCellValue("A{$currentRow}", ucfirst(strtolower($module)));
            $sheet->setCellValue("B{$currentRow}", $moduleName . $handoverInfo);
            $sheet->setCellValue("C{$currentRow}", 'Status');
            $sheet->setCellValue("D{$currentRow}", $modulePercentage . '%');

            $sheet->getStyle("A{$currentRow}:D{$currentRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B0F0']],
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                ],
            ]);

            // ✅ Sub-headers WITH REMARKS
            $headers = ['Start Date', 'End Date', 'Duration', 'Start Date', 'End Date', 'Duration', 'Remarks'];
            $col = 'E';
            foreach ($headers as $header) {
                $sheet->setCellValue("{$col}{$currentRow}", $header);

                if (in_array($col, ['E', 'F', 'G'])) {
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                    ]);
                } elseif (in_array($col, ['H', 'I', 'J'])) {
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00FF00']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                    ]);
                } elseif ($col === 'K') {
                    // ✅ Remarks column styling
                    $sheet->getStyle("{$col}{$currentRow}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE699']],
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'borders' => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                        ],
                    ]);
                }

                $col++;
            }

            $currentRow++;

            // Task rows
            $taskNumber = 1;
            foreach ($modulePlans as $plan) {
                $task = $plan->projectTask;

                $sheet->setCellValue("A{$currentRow}", $taskNumber);
                $sheet->setCellValue("B{$currentRow}", $task->task_name);
                $sheet->setCellValue("C{$currentRow}", ucfirst($plan->status));
                $sheet->setCellValue("D{$currentRow}", ($task->task_percentage ?? 0) . '%');

                $sheet->getStyle("D{$currentRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);

                // Plan dates
                $sheet->setCellValue("E{$currentRow}", $plan->plan_start_date ? \Carbon\Carbon::parse($plan->plan_start_date)->format('d/m/Y') : '');
                $sheet->setCellValue("F{$currentRow}", $plan->plan_end_date ? \Carbon\Carbon::parse($plan->plan_end_date)->format('d/m/Y') : '');
                $sheet->setCellValue(
                    "G{$currentRow}",
                    ($plan->plan_start_date && $plan->plan_end_date)
                        ? self::calculateWeekdays(
                            \Carbon\Carbon::parse($plan->plan_start_date),
                            \Carbon\Carbon::parse($plan->plan_end_date)
                        )
                        : ''
                );

                // Actual dates
                $sheet->setCellValue("H{$currentRow}", $plan->actual_start_date ? \Carbon\Carbon::parse($plan->actual_start_date)->format('d/m/Y') : '');
                $sheet->setCellValue("I{$currentRow}", $plan->actual_end_date ? \Carbon\Carbon::parse($plan->actual_end_date)->format('d/m/Y') : '');
                $sheet->setCellValue(
                    "J{$currentRow}",
                    ($plan->actual_start_date && $plan->actual_end_date)
                        ? self::calculateWeekdays(
                            \Carbon\Carbon::parse($plan->actual_start_date),
                            \Carbon\Carbon::parse($plan->actual_end_date)
                        )
                        : ''
                );

                // ✅ Remarks column
                $sheet->setCellValue("K{$currentRow}", $plan->remarks ?? '');
                $sheet->getStyle("K{$currentRow}")->getAlignment()->setWrapText(true);

                // ✅ Add borders to all columns including Remarks
                $sheet->getStyle("A{$currentRow}:K{$currentRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                    ],
                ]);

                $currentRow++;
                $taskNumber++;
            }

            $currentRow++;
        }

        // Auto-size columns A-J
        foreach (range('A', 'J') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ✅ Set fixed width for Remarks column
        $sheet->getColumnDimension('K')->setWidth(40);

        // Save to public storage
        $companySlug = \Illuminate\Support\Str::slug($companyName);
        $timestamp = now()->format('Y-m-d_His');
        $filename = "Combined_Project_Plan_{$companySlug}_{$timestamp}.xlsx";
        $directory = 'project-plans';
        $filePath = "{$directory}/{$filename}";

        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($directory);

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        \Illuminate\Support\Facades\Storage::disk('public')->put(
            $filePath,
            file_get_contents($tempFile)
        );

        unlink($tempFile);

        \Illuminate\Support\Facades\Log::info("Combined project plan Excel generated", [
            'lead_id' => $lead->id,
            'company_name' => $companyName,
            'handover_count' => $handoverCount,
            'file_path' => $filePath,
            'filename' => $filename,
        ]);

        return storage_path('app/public/' . $filePath);
    }

    // protected static function generateProjectPlanPdf(Lead $lead, SoftwareHandover $softwareHandover): string
    // {
    //     // Set document properties
    //     $companyName = $lead->companyDetail?->company_name ?? 'Unknown Company';
    //     $implementerName = $softwareHandover->implementer ?? 'Not Assigned';

    //     $selectedModules = $softwareHandover->getSelectedModules();
    //     $allModules = array_unique(array_merge(['phase 1', 'phase 2'], $selectedModules));

    //     $moduleNames = ProjectTask::whereIn('module', $allModules)
    //         ->where('is_active', true)
    //         ->select('module_name', 'module_order', 'module_percentage', 'module')
    //         ->distinct()
    //         ->orderBy('module_order')
    //         ->orderBy('module_name')
    //         ->get();

    //     $projectData = [];
    //     foreach ($moduleNames as $moduleData) {
    //         $moduleName = $moduleData->module_name;
    //         $modulePercentage = $moduleData->module_percentage;
    //         $module = $moduleData->module;

    //         $modulePlans = ProjectPlan::where('lead_id', $lead->id)
    //             ->where('sw_id', $softwareHandover->id)
    //             ->whereHas('projectTask', function ($query) use ($moduleName) {
    //                 $query->where('module_name', $moduleName)
    //                     ->where('is_active', true);
    //             })
    //             ->with('projectTask')
    //             ->orderBy('id')
    //             ->get();

    //         if ($modulePlans->isNotEmpty()) {
    //             $projectData[] = [
    //                 'module_name' => $moduleName,
    //                 'module_code' => ucfirst(strtolower($module)),
    //                 'module_percentage' => $modulePercentage,
    //                 'plans' => $modulePlans->map(function ($plan) {
    //                     return [
    //                         'task_name' => $plan->projectTask->task_name,
    //                         'status' => ucfirst($plan->status),
    //                         'task_percentage' => ($plan->projectTask->task_percentage ?? 0) . '%',
    //                         'plan_start_date' => $plan->plan_start_date ? \Carbon\Carbon::parse($plan->plan_start_date)->format('d/m/Y') : '',
    //                         'plan_end_date' => $plan->plan_end_date ? \Carbon\Carbon::parse($plan->plan_end_date)->format('d/m/Y') : '',
    //                         'plan_duration' => $plan->plan_duration ?? '',
    //                         'actual_start_date' => $plan->actual_start_date ? \Carbon\Carbon::parse($plan->actual_start_date)->format('d/m/Y') : '',
    //                         'actual_end_date' => $plan->actual_end_date ? \Carbon\Carbon::parse($plan->actual_end_date)->format('d/m/Y') : '',
    //                         'actual_duration' => $plan->actual_duration ?? '',
    //                         'remarks' => $plan->remarks ?? '',
    //                     ];
    //                 })->toArray()
    //             ];
    //         }
    //     }

    //     // Prepare data for PDF view
    //     $pdfData = [
    //         'company_name' => $companyName,
    //         'implementer_name' => $implementerName,
    //         'project_data' => $projectData,
    //         'generated_at' => now()->format('d/m/Y H:i:s')
    //     ];

    //     // Generate PDF
    //     $pdf = PDF::loadView('pdf.project-plan', $pdfData);
    //     $pdf->setPaper('A4', 'landscape'); // Set landscape orientation for better table display

    //     // Save to public storage
    //     $companySlug = \Illuminate\Support\Str::slug($companyName);
    //     $timestamp = now()->format('Y-m-d_His');
    //     $filename = "Project_Plan_{$companySlug}_{$timestamp}.pdf";
    //     $directory = 'project-plans';
    //     $filePath = "{$directory}/{$filename}";

    //     \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory($directory);

    //     // Save PDF content
    //     \Illuminate\Support\Facades\Storage::disk('public')->put(
    //         $filePath,
    //         $pdf->output()
    //     );

    //     \Illuminate\Support\Facades\Log::info("Project plan PDF generated", [
    //         'lead_id' => $lead->id,
    //         'company_name' => $companyName,
    //         'file_path' => $filePath,
    //         'filename' => $filename,
    //     ]);

    //     return storage_path('app/public/' . $filePath);
    // }

    protected static function calculateWeekdays(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): int
    {
        $start = $startDate->copy()->startOfDay();
        $end = $endDate->copy()->startOfDay();

        if ($start->gt($end)) {
            [$start, $end] = [$end, $start];
        }

        $totalDays = $start->diffInDays($end) + 1;
        $fullWeeks = intdiv($totalDays, 7);
        $remainingDays = $totalDays % 7;

        $weekdays = $fullWeeks * 5;
        $startIsoDay = $start->dayOfWeekIso;

        for ($i = 0; $i < $remainingDays; $i++) {
            $dayIso = (($startIsoDay + $i - 1) % 7) + 1;
            if ($dayIso <= 5) {
                $weekdays++;
            }
        }

        return $weekdays;
    }

    public static function createProjectPlansForModules(int $leadId, int $swId, array $modules): int
    {
        $createdCount = 0;

        foreach ($modules as $module) {
            $moduleNames = ProjectTask::where('module', $module)
                ->where('is_active', true)
                ->select('module_name')
                ->distinct()
                ->get()
                ->pluck('module_name');

            foreach ($moduleNames as $moduleName) {
                $tasks = ProjectTask::where('module_name', $moduleName)
                    ->where('is_active', true)
                    ->orderBy('order')
                    ->get();

                foreach ($tasks as $task) {
                    $plan = ProjectPlan::firstOrCreate(
                        [
                            'lead_id' => $leadId,
                            'sw_id' => $swId,
                            'project_task_id' => $task->id,
                        ],
                        [
                            'status' => 'pending',
                        ]
                    );

                    if ($plan->wasRecentlyCreated) {
                        $createdCount++;
                    }
                }
            }
        }

        return $createdCount;
    }
}
