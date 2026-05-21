<?php

namespace App\Livewire\ImplementerDashboard;

use App\Models\CompanyDetail;
use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\ImplementerHandoverRequest;
use App\Models\SoftwareHandover;
use App\Models\User;
use App\Mail\ProjectClosingNotification;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectClosingNew extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public $selectedUser;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        if ($selectedUser) {
            $this->selectedUser = $selectedUser;
            session(['selectedUser' => $selectedUser]);
        } else {
            $this->selectedUser = 7;
            session(['selectedUser' => 7]);
        }

        $this->resetTable();
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-implementer-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function getNewHandoverRequests(): Builder
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        $query = ImplementerHandoverRequest::query()
            ->where(function (Builder $q) {
                $q->whereNull('status')->orWhere('status', 'pending');
            });

        if ($this->selectedUser === 'all-implementer') {
            // no filter
        } elseif (is_numeric($this->selectedUser)) {
            $user = User::find($this->selectedUser);

            if ($user && ($user->role_id === 4 || $user->role_id === 5)) {
                $query->where('implementer_name', $user->name);
            }
        } else {
            $currentUser = auth()->user();

            if ($currentUser && $currentUser->role_id === 4) {
                $query->where('implementer_name', $currentUser->name);
            }
        }

        return $query->orderBy('date_request', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getNewHandoverRequests())
            ->filters([
                SelectFilter::make('implementer_name')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::whereIn('role_id', [4, 5])
                            ->orderBy('name')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),
            ])
            ->columns([
                TextColumn::make('softwareHandover.formatted_handover_id')
                    ->label('SW ID')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('softwareHandover', function (Builder $q) use ($search) {
                            $q->where('id', 'like', "%{$search}%");
                        });
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (ImplementerHandoverRequest $record): View {
                                $handover = $record->softwareHandover;
                                if (!$handover) {
                                    return view('components.empty-state-question');
                                }
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $handover]);
                            })
                    ),

                TextColumn::make('implementer_name')
                    ->label('Implementer Name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->formatStateUsing(function ($state, $record) {
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->softwareHandover->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->softwareHandover->lead_id)->first();
                        }

                        if ($company) {
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . e($company->company_name) . '
                                </a>');
                        }

                        return "<span title='{$state}'>{$state}</span>";
                    })
                    ->html(),

                TextColumn::make('date_request')
                    ->label('Date Request')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalHeading(false)
                        ->modalWidth('7xl')
                        ->form([
                            Textarea::make('team_lead_remark')
                                ->label('Team Lead Remark')
                                ->rows(3)
                                ->required()
                                ->extraAlpineAttributes([
                                    'x-on:input' => '
                                        const start = $el.selectionStart;
                                        const end = $el.selectionEnd;
                                        const value = $el.value;
                                        $el.value = value.toUpperCase();
                                        $el.setSelectionRange(start, end);
                                    '
                                ])
                                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                            Fieldset::make('Email Details')
                                ->schema([
                                    Grid::make(3)
                                        ->schema([
                                            // Left column - Email form fields
                                            Grid::make(1)
                                                ->schema([
                                                    TextInput::make('required_attendees')
                                                        ->label('Required Attendees')
                                                        ->helperText('Separate each email with a semicolon (e.g., email1@example.com;email2@example.com)')
                                                        ->default(function (ImplementerHandoverRequest $record) {
                                                            $handover = $record->softwareHandover;
                                                            $emails = [];

                                                            if ($handover) {
                                                                $lead = $handover->lead;

                                                                // Add original PIC emails from implementation_pics (Available only)
                                                                $pics = is_string($handover->implementation_pics)
                                                                    ? json_decode($handover->implementation_pics, true) ?? []
                                                                    : $handover->implementation_pics ?? [];
                                                                foreach ($pics as $pic) {
                                                                    if (!empty($pic['pic_email_impl']) && ($pic['status'] ?? 'Available') !== 'Resign') {
                                                                        $emails[] = $pic['pic_email_impl'];
                                                                    }
                                                                }

                                                                // Add new PIC emails from additional_pic (Available only)
                                                                if ($lead && $lead->companyDetail) {
                                                                    $additionalPics = is_string($lead->companyDetail->additional_pic)
                                                                        ? json_decode($lead->companyDetail->additional_pic, true) ?? []
                                                                        : $lead->companyDetail->additional_pic ?? [];
                                                                    foreach ($additionalPics as $pic) {
                                                                        if (!empty($pic['email']) && ($pic['status'] ?? 'Available') !== 'Resign') {
                                                                            $emails[] = $pic['email'];
                                                                        }
                                                                    }
                                                                }

                                                                // Add implementer email
                                                                if ($handover->implementer) {
                                                                    $implementer = User::where('name', $handover->implementer)->first();
                                                                    if ($implementer?->email) {
                                                                        $emails[] = $implementer->email;
                                                                    }
                                                                }
                                                            }

                                                            $uniqueEmails = array_unique(array_filter($emails));
                                                            return !empty($uniqueEmails) ? implode(';', $uniqueEmails) : null;
                                                        })
                                                        ->required(),

                                                    Select::make('email_template')
                                                        ->label('Email Template')
                                                        ->options(function () {
                                                            return EmailTemplate::where('type', 'implementer')->pluck('name', 'id')->toArray();
                                                        })
                                                        ->default(19)
                                                        ->searchable()
                                                        ->preload()
                                                        ->reactive()
                                                        ->afterStateHydrated(function ($state, callable $set, ImplementerHandoverRequest $record) {
                                                            $templateId = $state ?? 19;
                                                            $template = EmailTemplate::find($templateId);
                                                            $handover = $record->softwareHandover;

                                                            if ($template && $handover) {
                                                                $lead = $handover->lead;
                                                                $lead?->load('companyDetail');

                                                                $placeholders = self::buildPlaceholders($lead, $handover);

                                                                $subject = str_replace(array_keys($placeholders), array_values($placeholders), $template->subject ?? '');
                                                                $content = str_replace(array_keys($placeholders), array_values($placeholders), $template->content ?? '');

                                                                $set('email_subject', $subject);
                                                                $set('email_content', $content);
                                                            }
                                                        })
                                                        ->afterStateUpdated(function ($state, callable $set, ImplementerHandoverRequest $record) {
                                                            if ($state) {
                                                                $template = EmailTemplate::find($state);
                                                                $handover = $record->softwareHandover;

                                                                if ($template && $handover) {
                                                                    $lead = $handover->lead;
                                                                    $lead?->load('companyDetail');

                                                                    $placeholders = self::buildPlaceholders($lead, $handover);

                                                                    $subject = str_replace(array_keys($placeholders), array_values($placeholders), $template->subject ?? '');
                                                                    $content = str_replace(array_keys($placeholders), array_values($placeholders), $template->content ?? '');

                                                                    $set('email_subject', $subject);
                                                                    $set('email_content', $content);
                                                                }
                                                            }
                                                        })
                                                        ->required(),

                                                    TextInput::make('email_subject')
                                                        ->label('Email Subject')
                                                        ->default(function (ImplementerHandoverRequest $record) {
                                                            $handover = $record->softwareHandover;
                                                            $template = EmailTemplate::find(19);

                                                            if ($template && $handover) {
                                                                $lead = $handover->lead;
                                                                $lead?->load('companyDetail');

                                                                $placeholders = self::buildPlaceholders($lead, $handover);

                                                                return str_replace(array_keys($placeholders), array_values($placeholders), $template->subject ?? '');
                                                            }

                                                            return '';
                                                        })
                                                        ->required()
                                                        ->reactive(),

                                                    RichEditor::make('email_content')
                                                        ->label('Email Content')
                                                        ->default(function (ImplementerHandoverRequest $record) {
                                                            $handover = $record->softwareHandover;
                                                            $template = EmailTemplate::find(19);

                                                            if ($template && $handover) {
                                                                $lead = $handover->lead;
                                                                $lead?->load('companyDetail');

                                                                $placeholders = self::buildPlaceholders($lead, $handover);

                                                                return str_replace(array_keys($placeholders), array_values($placeholders), $template->content ?? '');
                                                            }

                                                            return '';
                                                        })
                                                        ->disableToolbarButtons([
                                                            'attachFiles',
                                                        ])
                                                        ->required()
                                                        ->reactive(),
                                                ])->columnSpan(1),

                                            // Right column - Email preview
                                            Placeholder::make('email_preview')
                                                ->label('Email Preview')
                                                ->content(function (callable $get) {
                                                    $subject = $get('email_subject') ?? '';
                                                    $content = $get('email_content') ?? '';

                                                    if (empty($subject) && empty($content)) {
                                                        return new HtmlString('<p class="italic text-gray-500">Select a template to see preview...</p>');
                                                    }

                                                    $previewImplementerName = auth()->user()->name ?? '';
                                                    $signature = "<br>Regards,<br>{$previewImplementerName}<br>Implementer<br>TimeTec Cloud Sdn Bhd<br>Phone: 03-80709933";
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
                                                ->visible(fn (callable $get) => !empty($get('email_subject')) || !empty($get('email_content'))),

                                            Grid::make(1)
                                                ->schema([
                                                    Select::make('selected_pic')
                                                        ->label('Contact Person for PDF Attachment')
                                                        ->helperText('Select which PIC details to include in the System Go Live PDF')
                                                        ->options(function (ImplementerHandoverRequest $record) {
                                                            $handover = $record->softwareHandover;
                                                            $options = [];

                                                            if ($handover) {
                                                                $lead = $handover->lead;

                                                                // Original PICs from implementation_pics
                                                                $pics = is_string($handover->implementation_pics)
                                                                    ? json_decode($handover->implementation_pics, true) ?? []
                                                                    : $handover->implementation_pics ?? [];
                                                                foreach ($pics as $index => $pic) {
                                                                    if (($pic['status'] ?? 'Available') !== 'Resign' && !empty($pic['pic_name_impl'])) {
                                                                        $label = ($pic['pic_name_impl'] ?? 'N/A');
                                                                        if (!empty($pic['position'])) $label .= ' - ' . $pic['position'];
                                                                        if (!empty($pic['pic_email_impl'])) $label .= ' (' . $pic['pic_email_impl'] . ')';
                                                                        $options["orig_{$index}"] = strtoupper($label);
                                                                    }
                                                                }

                                                                // New PICs from additional_pic
                                                                if ($lead && $lead->companyDetail) {
                                                                    $additionalPics = is_string($lead->companyDetail->additional_pic)
                                                                        ? json_decode($lead->companyDetail->additional_pic, true) ?? []
                                                                        : $lead->companyDetail->additional_pic ?? [];
                                                                    foreach ($additionalPics as $index => $pic) {
                                                                        if (($pic['status'] ?? 'Available') !== 'Resign' && !empty($pic['name'])) {
                                                                            $label = ($pic['name'] ?? 'N/A');
                                                                            if (!empty($pic['position'])) $label .= ' - ' . $pic['position'];
                                                                            if (!empty($pic['email'])) $label .= ' (' . $pic['email'] . ')';
                                                                            $options["new_{$index}"] = strtoupper($label);
                                                                        }
                                                                    }
                                                                }
                                                            }

                                                            return $options;
                                                        })
                                                        ->default(function (ImplementerHandoverRequest $record) {
                                                            $handover = $record->softwareHandover;
                                                            $defaults = [];
                                                            if ($handover) {
                                                                $pics = is_string($handover->implementation_pics)
                                                                    ? json_decode($handover->implementation_pics, true) ?? []
                                                                    : $handover->implementation_pics ?? [];
                                                                foreach ($pics as $index => $pic) {
                                                                    if (($pic['status'] ?? 'Available') !== 'Resign' && !empty($pic['pic_name_impl'])) {
                                                                        $defaults[] = "orig_{$index}";
                                                                    }
                                                                }
                                                            }
                                                            return $defaults;
                                                        })
                                                        ->required()
                                                        ->multiple()
                                                        ->live()
                                                        ->searchable(),

                                                    Placeholder::make('pdf_preview')
                                                        ->label('PDF Attachment Preview')
                                                        ->content(function (callable $get, ImplementerHandoverRequest $record) {
                                                            $selectedPics = $get('selected_pic');
                                                            if (empty($selectedPics) || !is_array($selectedPics)) {
                                                                return new HtmlString('<p class="italic text-gray-500">Select contact person(s) to preview PDF...</p>');
                                                            }

                                                            $handover = $record->softwareHandover;
                                                            if (!$handover) {
                                                                return new HtmlString('<p class="italic text-gray-500">No handover found.</p>');
                                                            }

                                                            $url = \Illuminate\Support\Facades\URL::signedRoute('project-closing.pdf-preview', [
                                                                'handover' => $handover->id,
                                                                'pic' => implode(',', $selectedPics),
                                                            ]);

                                                            return new HtmlString(
                                                                '<iframe src="' . e($url) . '" style="width: 100%; min-height: 500px; border: 1px solid #e5e7eb; border-radius: 0.5rem;"></iframe>'
                                                            );
                                                        })
                                                        ->dehydrated(false),

                                                ])->columnSpan(1),
                                        ]),
                                ]),

                            // Hidden fields for implementer details
                            Hidden::make('implementer_name')
                                ->default(function (ImplementerHandoverRequest $record) {
                                    $handover = $record->softwareHandover;
                                    if ($handover?->implementer) {
                                        $implementer = User::where('name', $handover->implementer)->first();
                                        if ($implementer) {
                                            return $implementer->name;
                                        }
                                    }
                                    return auth()->user()->name ?? '';
                                }),

                            Hidden::make('implementer_designation')
                                ->default('Implementer'),

                            Hidden::make('implementer_company')
                                ->default('TimeTec Cloud Sdn Bhd'),

                            Hidden::make('implementer_phone')
                                ->default('03-80709933'),

                            Hidden::make('implementer_email')
                                ->default(function (ImplementerHandoverRequest $record) {
                                    $handover = $record->softwareHandover;
                                    if ($handover?->implementer) {
                                        $implementer = User::where('name', $handover->implementer)->first();
                                        if ($implementer) {
                                            return $implementer->email;
                                        }
                                    }
                                    return auth()->user()->email ?? '';
                                }),
                        ])
                        ->action(function (ImplementerHandoverRequest $record, array $data) {
                            $record->update([
                                'status' => 'approved',
                                'team_lead_remark' => $data['team_lead_remark'],
                                'approved_at' => now(),
                                'approved_by' => auth()->id(),
                            ]);

                            // Send notification to implementer
                            $this->sendNotification($record, 'approved');

                            // Send project closing email using form data
                            $this->sendProjectClosingEmail($record, $data);

                            $this->dispatch('refresh-implementer-tables');
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('team_lead_remark')
                                ->label('Team Lead Remark')
                                ->rows(3)
                                ->required()
                                ->extraAlpineAttributes([
                                    'x-on:input' => '
                                        const start = $el.selectionStart;
                                        const end = $el.selectionEnd;
                                        const value = $el.value;
                                        $el.value = value.toUpperCase();
                                        $el.setSelectionRange(start, end);
                                    '
                                ])
                                ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                        ])
                        ->modalHeading(false)
                        ->action(function (ImplementerHandoverRequest $record, array $data) {
                            $record->update([
                                'status' => 'rejected',
                                'team_lead_remark' => $data['team_lead_remark'],
                                'rejected_at' => now(),
                                'rejected_by' => auth()->id(),
                            ]);

                            // Clear the handover_requested_at so button appears again
                            $handover = $record->softwareHandover;
                            if ($handover) {
                                $handover->update([
                                    'handover_requested_at' => null,
                                    'handover_requested_by' => null,
                                ]);
                            }

                            // Send email notification
                            $this->sendNotification($record, 'rejected');

                            Notification::make()
                                ->title('Request Rejected')
                                ->body('Handover request has been rejected and implementer has been notified.')
                                ->success()
                                ->send();

                            $this->dispatch('refresh-implementer-tables');
                        }),
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
                ->visible(fn (): bool => auth()->check() && auth()->user()->role_id != 4)
            ])
            ->defaultSort('date_request', 'desc');
    }

    protected static function buildPlaceholders($lead, $handover, ?string $implementerName = null): array
    {
        $customerEmail = '';
        $customerPassword = '';
        if ($lead) {
            $customer = Customer::where('lead_id', $lead->id)->first();
            if ($customer) {
                $customerEmail = $customer->email ?? '';
                $customerPassword = $customer->plain_password ?? '';
            }
        }

        return [
            '{customer_name}' => $lead->contact_name ?? '',
            '{company_name}' => $handover->company_name ?? ($lead?->companyDetail?->company_name ?? 'Unknown Company'),
            '{implementer_name}' => $implementerName ?? ($handover->implementer ?? 'N/A'),
            '{lead_owner}' => $lead->lead_owner ?? '',
            '{customer_email}' => $customerEmail,
            '{customer_password}' => $customerPassword,
            '{customer_portal_url}' => str_replace('http://', 'https://', config('app.url')) . '/customer/login',
        ];
    }

    protected function sendNotification(ImplementerHandoverRequest $record, string $status)
    {
        try {
            $handover = $record->softwareHandover;
            $implementerEmail = null;

            // Get implementer email
            if ($handover && $handover->implementer) {
                $implementer = User::where('name', $handover->implementer)->first();
                if ($implementer) {
                    $implementerEmail = $implementer->email;
                }
            }

            if ($implementerEmail) {
                $mailInstance = Mail::to($implementerEmail);

                $ccEmail = null;
                // CC the authenticated user (team lead)
                if (auth()->check() && auth()->user()->email) {
                    $ccEmail = auth()->user()->email;
                    $mailInstance->cc($ccEmail);
                }

                $mailInstance->send(
                    new ProjectClosingNotification($record, $status)
                );

                // Log email recipients
                Log::info('Project Closing Notification Sent', [
                    'sw_id' => $handover ? $handover->formatted_handover_id : 'N/A',
                    'status' => strtoupper($status),
                    'sent_to' => $implementerEmail,
                    'cc_to' => $ccEmail ?? 'None',
                    'team_lead' => auth()->user()->name ?? 'Unknown',
                    'timestamp' => now()->format('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send project closing notification: ' . $e->getMessage());
        }
    }

    protected function sendProjectClosingEmail(ImplementerHandoverRequest $record, array $data)
    {
        try {
            $handover = $record->softwareHandover;
            if (!$handover) {
                Notification::make()
                    ->title('Error')
                    ->body('Software handover not found.')
                    ->danger()
                    ->send();
                return;
            }

            $lead = $handover->lead;
            if (!$lead) {
                Notification::make()
                    ->title('Error')
                    ->body('Lead not found.')
                    ->danger()
                    ->send();
                return;
            }

            $lead->load('companyDetail');

            // Get recipient emails from form
            $recipientStr = $data['required_attendees'] ?? '';

            if (empty($recipientStr)) {
                Notification::make()
                    ->title('Error')
                    ->body('No recipients specified.')
                    ->danger()
                    ->send();
                return;
            }

            // Get email content from form
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
            $placeholders = self::buildPlaceholders($lead, $handover, $data['implementer_name'] ?? auth()->user()->name ?? '');

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

            // Get sender details
            $authUser = auth()->user();
            $senderEmail = $data['implementer_email'] ?? $authUser->email;
            $senderName = $data['implementer_name'] ?? $authUser->name;

            // Generate System Go Live PDF (single PDF with all selected PICs)
            $pdfPath = $this->generateSystemGoLivePdf($handover, $lead, $data);

            // Send email with PDF attachment
            Mail::html($content, function ($message) use ($subject, $validRecipients, $senderEmail, $senderName, $pdfPath) {
                $message->from($senderEmail, $senderName)
                    ->to($validRecipients)
                    ->cc($senderEmail)
                    ->subject($subject);

                if ($pdfPath && file_exists($pdfPath)) {
                    $message->attach($pdfPath, [
                        'as' => basename($pdfPath),
                        'mime' => 'application/pdf',
                    ]);
                }
            });

            // Clean up temporary PDF
            if ($pdfPath && file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            // Update ALL software handovers with approved requests to Closed
            $allHandovers = SoftwareHandover::where('lead_id', $lead->id)
                ->where('status_handover', '!=', 'Closed')
                ->get();

            $closedCount = 0;
            foreach ($allHandovers as $hw) {
                $hasApprovedRequest = ImplementerHandoverRequest::where('sw_id', $hw->id)
                    ->where('status', 'approved')
                    ->exists();

                if ($hasApprovedRequest) {
                    $hw->update([
                        'status_handover' => 'Closed',
                        'go_live_date' => now(),
                        'closed_at' => now(),
                        'closed_by' => auth()->id(),
                    ]);
                    $closedCount++;
                }
            }

            // Log the email sent
            Log::info('Project Closing Email Sent', [
                'sw_id' => $handover->formatted_handover_id,
                'company_name' => $lead->companyDetail?->company_name ?? 'N/A',
                'lead_id' => $lead->id,
                'subject' => $subject,
                'recipients' => $validRecipients,
                'sent_by' => $senderName,
                'sent_from' => $senderEmail,
                'template_id' => $data['email_template'],
                'closed_handovers_count' => $closedCount,
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ]);

            $closedMessage = $closedCount > 1
                ? "and {$closedCount} software handovers marked as Closed."
                : "and software handover marked as Closed.";

            Notification::make()
                ->title('Request Approved & Email Sent Successfully')
                ->success()
                ->body('Email sent to ' . count($validRecipients) . ' recipient(s) ' . $closedMessage)
                ->send();

        } catch (\Exception $e) {
            Log::error('Project Closing Email Failed', [
                'request_id' => $record->id,
                'error' => $e->getMessage(),
            ]);

            Notification::make()
                ->title('Request Approved but Email Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Resolve PIC data from the selected_pic key (e.g. 'orig_0', 'new_1')
     */
    protected function resolveSelectedPic(SoftwareHandover $handover, string $picKey): array
    {
        if (str_starts_with($picKey, 'orig_')) {
            $index = (int) str_replace('orig_', '', $picKey);
            $pics = is_string($handover->implementation_pics)
                ? json_decode($handover->implementation_pics, true) ?? []
                : $handover->implementation_pics ?? [];
            if (isset($pics[$index])) {
                return $pics[$index];
            }
        } elseif (str_starts_with($picKey, 'new_')) {
            $index = (int) str_replace('new_', '', $picKey);
            $lead = $handover->lead;
            if ($lead && $lead->companyDetail) {
                $additionalPics = is_string($lead->companyDetail->additional_pic)
                    ? json_decode($lead->companyDetail->additional_pic, true) ?? []
                    : $lead->companyDetail->additional_pic ?? [];
                if (isset($additionalPics[$index])) {
                    $pic = $additionalPics[$index];
                    return [
                        'pic_name_impl' => $pic['name'] ?? 'N/A',
                        'position' => $pic['position'] ?? 'N/A',
                        'pic_email_impl' => $pic['email'] ?? 'N/A',
                        'pic_phone_impl' => $pic['hp_number'] ?? 'N/A',
                    ];
                }
            }
        }

        return ['pic_name_impl' => 'N/A', 'position' => 'N/A', 'pic_email_impl' => 'N/A', 'pic_phone_impl' => 'N/A'];
    }

    protected function generateSystemGoLivePdf(SoftwareHandover $handover, $lead, array $data): ?string
    {
        try {
            // Build module names from handover boolean fields
            $moduleMap = [
                'ta' => 'Attendance', 'tl' => 'Leave', 'tc' => 'Claim', 'tp' => 'Payroll',
                'tapp' => 'Appraisal', 'thire' => 'Hire', 'tacc' => 'Access', 'tpbi' => 'PowerBI',
            ];
            $activeModules = [];
            foreach ($moduleMap as $field => $name) {
                if ($handover->{$field}) {
                    $activeModules[] = strtoupper($name);
                }
            }

            // Resolve all selected PICs into contact persons array
            $selectedPicKeys = $data['selected_pic'] ?? ['orig_0'];
            if (!is_array($selectedPicKeys)) {
                $selectedPicKeys = [$selectedPicKeys];
            }

            $contactPersons = [];
            foreach ($selectedPicKeys as $picKey) {
                $pic = $this->resolveSelectedPic($handover, $picKey);
                $contactPersons[] = [
                    'name' => $pic['pic_name_impl'] ?? 'N/A',
                    'position' => $pic['position'] ?? 'N/A',
                    'email' => $pic['pic_email_impl'] ?? 'N/A',
                    'phone' => $pic['pic_phone_impl'] ?? 'N/A',
                ];
            }

            $companyDetail = $lead->companyDetail;
            $companyName = $handover->company_name ?? ($companyDetail?->company_name ?? 'N/A');

            $pdfData = [
                'path_img' => public_path('img/logo-ttc.png'),
                'stampImg' => public_path('storage/ttc-stamp.png'),
                'companyName' => $companyName,
                'contactPersons' => $contactPersons,
                'modules' => implode('/', $activeModules) ?: 'N/A',
                'implementationStartDate' => $handover->created_at ? $handover->created_at->format('d/m/Y') : 'N/A',
                'implementationCompletionDate' => now()->format('d/m/Y'),
                'implementerName' => $handover->implementer ?? ($data['implementer_name'] ?? 'N/A'),
                'teamLeadName' => auth()->user()->name ?? 'N/A',
            ];

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.system-go-live', $pdfData)
                ->setPaper('a4', 'portrait');

            $companySlug = Str::slug($companyName);
            $fileName = "System_Go_Live_{$companySlug}_" . now()->format('Ymd') . '.pdf';
            $tempPath = storage_path('app/temp/' . $fileName);

            if (!is_dir(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            $pdf->save($tempPath);

            return $tempPath;
        } catch (\Exception $e) {
            Log::error('Failed to generate System Go Live PDF', [
                'error' => $e->getMessage(),
                'sw_id' => $handover->id,
            ]);
            return null;
        }
    }

    public function render()
    {
        return view('livewire.implementer_dashboard.project-closing-new');
    }
}
