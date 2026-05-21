<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketingUser;
use App\Models\TicketingNotification;
use App\Models\UserNotificationSetting;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TicketNotificationService
{
    /**
     * Main entry point — handle a notification action for a ticket.
     *
     * @param string $action The scenario action name (e.g. 'created_p1', 'completed', 'reopened')
     * @param Ticket $ticket
     * @param TicketingUser|null $actionBy The ticketing user who performed the action (for exclusion + email context)
     * @param array $additionalData Extra data for templates
     */
    public function handleAction(string $action, Ticket $ticket, ?TicketingUser $actionBy = null, array $additionalData = [])
    {
        try {
            $excludeUserId = $actionBy?->id;

            $recipients = $this->getRecipients($action, $ticket)
                ->filter(fn($user) => $user && $user->id !== $excludeUserId);

            $ccRecipients = $this->getCcRecipients($action, $ticket);

            // Resolve the CRM user for email "action by" context
            $crmActionBy = $actionBy?->getCrmUser();

            $this->sendEmailIfNeeded($action, $ticket, $recipients, $ccRecipients, $additionalData, $crmActionBy);
            $this->storeInAppNotificationsIfNeeded($action, $ticket, $recipients, $additionalData, $crmActionBy);

            Log::info('TicketNotificationService: handled action', [
                'action' => $action,
                'ticket_id' => $ticket->ticket_id,
                'recipients' => $recipients->map(fn($u) => ['id' => $u->id, 'email' => $u->email])->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('TicketNotificationService: failed', [
                'action' => $action,
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Store in-app notifications via TicketingNotification model.
     */
    public function storeInAppNotificationsIfNeeded(string $action, Ticket $ticket, $recipients, array $additionalData = [], ?User $actionBy = null)
    {
        $config = config("notification-scenarios.ticket.{$action}");
        $channels = $config['channels'] ?? [];

        if (!in_array('in_app', $channels) && !in_array('push', $channels)) {
            return;
        }

        $notification = new TicketNotification(
            $ticket,
            $action,
            $actionBy,
            $additionalData
        );

        foreach ($recipients as $recipient) {
            $notificationData = $notification->toDatabase($recipient);

            TicketingNotification::create([
                'type' => get_class($notification),
                'notifiable_type' => 'App\\Models\\User',
                'notifiable_id' => $recipient->id,
                'data' => $notificationData,
                'read_at' => null,
            ]);
        }
    }

    /**
     * Send email if 'email' channel is enabled in config.
     */
    public function sendEmailIfNeeded(string $action, Ticket $ticket, $recipients, $ccRecipients, array $additionalData = [], ?User $actionBy = null)
    {
        $config = config("notification-scenarios.ticket.{$action}");
        $channels = $config['channels'] ?? [];

        if (!in_array('email', $channels)) {
            Log::info("TicketNotification email skipped: 'email' channel not in config for {$action}");
            return;
        }

        if ($recipients->isEmpty()) {
            Log::info("TicketNotification email skipped: no recipients for {$action}");
            return;
        }

        $notificationKey = "ticket-{$action}";

        $filteredRecipients = $recipients->filter(function ($recipient) use ($notificationKey) {
            return $this->shouldReceiveEmail($recipient->id, $notificationKey);
        });

        $filteredCcRecipients = $ccRecipients->filter(function ($recipient) use ($notificationKey) {
            return $this->shouldReceiveEmail($recipient->id, $notificationKey);
        });

        if ($filteredRecipients->isEmpty() && $filteredCcRecipients->isEmpty()) {
            Log::info("TicketNotification email skipped: all recipients filtered out by shouldReceiveEmail for {$action}");
            return;
        }

        $recipientEmails = $filteredRecipients->pluck('email')->toArray();
        $ccEmails = $filteredCcRecipients->filter(function ($cc) use ($recipientEmails) {
            return !in_array($cc->email, $recipientEmails);
        })->pluck('email')->toArray();

        Log::info("TicketNotification email sending", [
            'action' => $action,
            'ticket' => $ticket->ticket_id,
            'to' => $recipientEmails,
            'cc' => $ccEmails,
        ]);

        try {
            $firstRecipient = $filteredRecipients->first();

            // Get role name via raw query since TicketingUser doesn't have Spatie HasRoles trait
            $recipientRoleName = null;
            if ($firstRecipient) {
                $recipientRoleName = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('model_has_roles')
                    ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                    ->where('model_has_roles.model_id', $firstRecipient->id)
                    ->where('model_has_roles.model_type', 'App\\Models\\User')
                    ->value('roles.name');
            }
            $additionalData['recipient_role'] = $recipientRoleName;

            $mailable = new \App\Mail\EntityNotificationMail(
                $ticket,
                'ticket',
                $action,
                $actionBy,
                $additionalData,
                $firstRecipient
            );

            $mail = Mail::to($recipientEmails);

            if (!empty($ccEmails)) {
                $mail->cc($ccEmails);
            }

            // $mail->bcc('zilih.ng@timeteccloud.com');

            $mail->send($mailable);

            Log::info("TicketNotification email sent successfully for {$ticket->ticket_id}");
        } catch (\Exception $e) {
            Log::error("TicketNotification email FAILED for {$ticket->ticket_id}: " . $e->getMessage());
        }
    }

    /**
     * Check if user should receive email for this notification type.
     */
    public function shouldReceiveEmail(int $userId, string $notificationKey): bool
    {
        static $cache = [];
        $cacheKey = "{$userId}:{$notificationKey}";

        if (!isset($cache[$cacheKey])) {
            $setting = UserNotificationSetting::where('user_id', $userId)
                ->where('notification_key', $notificationKey)
                ->first();

            $cache[$cacheKey] = $setting ? $setting->enabled : true;
        }

        return $cache[$cacheKey];
    }

    /**
     * Get ticketing system users by Spatie role with product/module access filtering.
     */
    public function getUsersByRoleWithAccess(Ticket $ticket, string $roleName)
    {
        $vagueRoles = ['Manager', 'Lead'];

        $users = in_array($roleName, $vagueRoles)
            ? TicketingUser::byRoleLike($roleName)->get()
            : TicketingUser::byRole($roleName)->get();

        return $users->filter(function ($user) use ($ticket) {
            if (!$ticket->product_id) {
                return true;
            }

            if (!$user->canAccessProduct($ticket->product_id)) {
                return false;
            }

            if ($ticket->module_id) {
                return $user->canAccessModule($ticket->product_id, $ticket->module_id);
            }

            if ($ticket->solution_id) {
                $accessibleSolutionIds = $user->getAccessibleSolutionIds($ticket->product_id);
                return in_array($ticket->solution_id, $accessibleSolutionIds);
            }

            return true;
        });
    }

    /**
     * Get recipients based on scenario configuration.
     */
    public function getRecipients(string $action, Ticket $ticket)
    {
        $recipientType = config("notification-scenarios.ticket.{$action}.recipients");

        return match ($recipientType) {
            'assignees' => $ticket->getAssignees(),
            'creator' => collect([$ticket->ticketingRequestor])->filter(),
            'rnd_team' => collect()
                ->merge($this->getUsersByRoleWithAccess($ticket, 'RND Developer'))
                ->merge($this->getUsersByRoleWithAccess($ticket, 'RND TimeTec Project CRM Developer'))
                ->unique('id'),
            'pdt_team' => $this->getUsersByRoleWithAccess($ticket, 'PDT'),
            'qc_team' => $this->getUsersByRoleWithAccess($ticket, 'QC'),
            'all_teams' => collect()
                ->merge($this->getUsersByRoleWithAccess($ticket, 'PDT'))
                ->merge($this->getUsersByRoleWithAccess($ticket, 'RND Developer'))
                ->merge($this->getUsersByRoleWithAccess($ticket, 'RND TimeTec Project CRM Developer'))
                ->merge($this->getUsersByRoleWithAccess($ticket, 'QC'))
                ->unique('id'),
            'assigned_team' => $this->getAssignedTeam($ticket),
            'team_lead' => $this->getTeamLeadsByAssignees($ticket),
            'rnd_team_lead' => $this->getUsersByRoleWithAccess($ticket, 'RND Team Lead'),
            'pdt_team_lead' => $this->getUsersByRoleWithAccess($ticket, 'PDT Team Lead'),
            'qc_team_lead' => $this->getUsersByRoleWithAccess($ticket, 'QC Team Lead'),
            'fe_team_lead' => $this->getFETeamLeadByCreatorDepartment($ticket),
            'manager' => $this->getUsersByRoleWithAccess($ticket, 'Manager'),
            default => collect(),
        };
    }

    /**
     * Get CC recipients based on scenario configuration.
     */
    public function getCcRecipients(string $action, Ticket $ticket)
    {
        $ccTypes = config("notification-scenarios.ticket.{$action}.cc", []);

        if (empty($ccTypes)) {
            return collect();
        }

        $ccRecipients = collect();

        foreach ($ccTypes as $ccType) {
            $recipients = match ($ccType) {
                'assignees' => $ticket->getAssignees(),
                'creator' => collect([$ticket->ticketingRequestor])->filter(),
                'rnd_team' => collect()
                    ->merge($this->getUsersByRoleWithAccess($ticket, 'RND Developer'))
                    ->merge($this->getUsersByRoleWithAccess($ticket, 'RND TimeTec Project CRM Developer'))
                    ->unique('id'),
                'pdt_team' => $this->getUsersByRoleWithAccess($ticket, 'PDT'),
                'qc_team' => $this->getUsersByRoleWithAccess($ticket, 'QC'),
                'team_lead' => $this->getTeamLeadsByAssignees($ticket),
                'rnd_team_lead' => $this->getUsersByRoleWithAccess($ticket, 'RND Team Lead'),
                'pdt_team_lead' => $this->getUsersByRoleWithAccess($ticket, 'PDT Team Lead'),
                'qc_team_lead' => $this->getUsersByRoleWithAccess($ticket, 'QC Team Lead'),
                'fe_team_lead' => $this->getFETeamLeadByCreatorDepartment($ticket),
                'manager' => $this->getUsersByRoleWithAccess($ticket, 'Manager'),
                default => collect(),
            };

            $ccRecipients = $ccRecipients->merge($recipients);
        }

        return $ccRecipients->unique('id');
    }

    /**
     * Get FE team lead based on ticket creator's department.
     */
    public function getFETeamLeadByCreatorDepartment(Ticket $ticket)
    {
        $creator = $ticket->ticketingRequestor;

        if (!$creator) {
            return collect();
        }

        $creator->load('department');
        $deptName = $creator->department?->name;

        if (!$deptName) {
            return collect();
        }

        $roleMapping = match ($deptName) {
            'FE (Support)' => 'FE (Support) Team Lead',
            'FE (Implementor)' => 'FE (Implementor) Team Lead',
            'FE (Sales)' => 'FE (Sales) Team Lead',
            default => null,
        };

        if (!$roleMapping) {
            return collect();
        }

        return $this->getUsersByRoleWithAccess($ticket, $roleMapping);
    }

    /**
     * Get team leads based on the departments of ticket assignees.
     */
    public function getTeamLeadsByAssignees(Ticket $ticket)
    {
        $assignees = $ticket->getAssignees();

        if ($assignees->isEmpty()) {
            return collect();
        }

        $assignees->load('department');

        $departmentNames = $assignees
            ->pluck('department.name')
            ->filter()
            ->unique();

        $teamLeads = collect();
        foreach ($departmentNames as $deptName) {
            $roleMapping = match ($deptName) {
                'RND', 'RnD' => 'RND Team Lead',
                'PDT' => 'PDT Team Lead',
                'QC' => 'QC Team Lead',
                'FE (Support)' => 'FE (Support) Team Lead',
                'FE (Sales)' => 'FE (Sales) Team Lead',
                'FE (Implementor)' => 'FE (Implementor) Team Lead',
                default => null,
            };

            if ($roleMapping) {
                $leads = $this->getUsersByRoleWithAccess($ticket, $roleMapping);
                $teamLeads = $teamLeads->merge($leads);
            }
        }

        return $teamLeads->unique('id');
    }

    /**
     * Get assigned team: ticket assignees → task assignees → fallback by priority.
     */
    public function getAssignedTeam(Ticket $ticket)
    {
        // 1. Direct ticket assignees
        $assignees = $ticket->getAssignees();
        if ($assignees->isNotEmpty()) {
            return $assignees;
        }

        // 2. Task assignees
        $taskAssignees = $ticket->tasks->flatMap(fn($task) => $task->getAssignees())->unique('id');
        if ($taskAssignees->isNotEmpty()) {
            return $taskAssignees;
        }

        // 3. Fallback to team by priority
        return $this->getFallbackTeamByPriority($ticket);
    }

    /**
     * Fallback team when assigned_team is empty — use the same team as ticket creation.
     */
    public function getFallbackTeamByPriority(Ticket $ticket)
    {
        $priority = $ticket->priority?->name ?? '';

        return match ($priority) {
            'Software Bugs', 'Back End Assistance' => collect()
                ->merge($this->getUsersByRoleWithAccess($ticket, 'RND Developer'))
                ->merge($this->getUsersByRoleWithAccess($ticket, 'RND TimeTec Project CRM Developer'))
                ->unique('id'),
            'RFQ Customization' => collect()
                ->merge($this->getUsersByRoleWithAccess($ticket, 'PDT'))
                ->merge($this->getUsersByRoleWithAccess($ticket, 'RND Developer'))
                ->merge($this->getUsersByRoleWithAccess($ticket, 'RND TimeTec Project CRM Developer'))
                ->merge($this->getUsersByRoleWithAccess($ticket, 'QC'))
                ->unique('id'),
            default => $this->getUsersByRoleWithAccess($ticket, 'PDT'),
        };
    }

    /**
     * Map a status string to the corresponding notification action name.
     */
    public function determineActionFromStatus(string $newStatus): ?string
    {
        if (strpos($newStatus, 'In Progress') !== false) {
            return 'accepted';
        }

        return match ($newStatus) {
            'Closed' => 'closed',
            'On Hold' => 'on_hold',
            'Reopen' => 'reopened',
            'Closed System Configuration' => 'rejected_config',
            'Closed with New CR' => 'rejected_change_request',
            'PDT - Rejected' => 'pdt_rejected_request',
            'Mandays Updated' => 'all_mandays_completed',
            'Completed' => 'completed',
            default => null,
        };
    }
}
