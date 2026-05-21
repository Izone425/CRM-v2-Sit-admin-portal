<?php

return [
    'ticket' => [
        // Scenario 1: New Ticket Created - P1 (Critical)
        'created_p1' => [
            'title' => 'New P1 Ticket Created',
            'description' => 'Critical software bug requiring immediate attention',
            'message' => 'New Software bug ticket {ticket_id} "{title}" requires immediate attention.',
            'recipients' => 'rnd_team',
            'cc' => ['rnd_team_lead', 'manager'],
            'priority' => 'urgent',
            'channels' => ['in_app', 'push', 'email'],
            'action_url' => '/tickets/{ticket_id}',
            'email' => [
                'subject' => 'URGENT: New P1 Ticket - {ticket_id} Requires Immediate Attention',
                'greeting' => 'A new critical (P1) software bug ticket has been created and requires your immediate attention.',
                'show_description' => true,
                'show_customer' => true,
                'detail_mode' => 'created',
                'cta_text' => 'Please review and accept this ticket as soon as possible. Immediate action is required.',
                'button_text' => 'View Ticket',
            ],
        ],

        // Scenario 2: New Ticket Created - P2 (High Priority)
        'created_p2' => [
            'title' => 'New P2 Ticket Created',
            'description' => 'High priority backend assistance ticket',
            'message' => 'New Backend assistance ticket {ticket_id} "{title}" needs R&D support.',
            'recipients' => 'rnd_team',
            'cc' => ['rnd_team_lead'],
            'priority' => 'high',
            'channels' => ['in_app', 'push', 'email'],
            'action_url' => '/tickets/{ticket_id}',
            'email' => [
                'subject' => 'HIGH PRIORITY: New P2 Ticket - {ticket_id} Needs R&D Support',
                'greeting' => 'A new high-priority (P2) backend assistance ticket requires R&D support.',
                'show_description' => true,
                'show_customer' => true,
                'detail_mode' => 'created',
                'cta_text' => 'Please review this ticket and provide the necessary backend support.',
                'button_text' => 'View Ticket',
            ],
        ],

        // Scenario 3: New Ticket Created - P3-P5 (Enhancement)
        'created_p3_p5' => [
            'title' => 'New Enhancement Ticket',
            'description' => 'Enhancement/feature request requiring PDT review',
            'message' => 'New {priority} ticket {ticket_id} "{title}" requires PDT review.',
            'recipients' => 'pdt_team',
            'cc' => ['pdt_team_lead'],
            'priority' => 'normal',
            'channels' => ['in_app', 'push', 'email'],
            'action_url' => '/tickets/{ticket_id}',
            'email' => [
                'subject' => 'New Enhancement Ticket - {ticket_id} Requires PDT Review',
                'greeting' => 'A new enhancement/feature request ticket has been created and requires PDT review.',
                'show_description' => true,
                'show_customer' => true,
                'detail_mode' => 'created',
                'cta_text' => 'Please review this enhancement request and determine if it should be converted to a task for development.',
                'button_text' => 'View Ticket',
            ],
        ],

        // Scenario 4: Ticket Accepted
        'accepted' => [
            'title' => 'Ticket Accepted',
            'description' => 'Ticket accepted and assigned for work',
            'message' => 'Your ticket {ticket_id} "{title}" has been accepted by {user_name}.',
            'recipients' => 'creator',
            'priority' => 'normal',
            'channels' => ['in_app'],
            'action_url' => '/tickets/{ticket_id}',
        ],

        // Scenario 5: Ticket Completed (P1/P2)
        'completed' => [
            'title' => 'Ticket Completed',
            'description' => 'Ticket completed and ready for verification',
            'message' => 'Ticket {ticket_id} "{title}" has been marked as completed by {user_name}.',
            'recipients' => 'creator',
            'cc' => ['fe_team_lead'],
            'priority' => 'normal',
            'channels' => ['in_app', 'push', 'email'],
            'action_url' => '/tickets/{ticket_id}',
            'email' => [
                'subject' => 'Ticket {ticket_id} Completed - Ready for Verification',
                'greeting' => 'Your ticket has been completed and is ready for your verification.',
                'show_verification_steps' => true,
                'detail_mode' => 'completed',
                'dont_show_cta' => true,
                'button_text' => 'View Ticket & Verify',
            ],
        ],

        // Scenario 6: Ticket Verified Live
        'verified_live' => [
            'title' => 'Ready to Close',
            'description' => 'Ticket verified working in production, ready to close',
            'message' => 'Ticket {ticket_id} "{title}" has been verified live by {user_name}. Ready for closure.',
            'recipients' => 'creator',
            'priority' => 'normal',
            'channels' => ['in_app', 'push'],
            'action_url' => '/tickets/{ticket_id}',
        ],

        // Scenario 7: Ticket Closed
        'closed' => [
            'title' => 'Ticket Closed',
            'description' => 'Ticket closed and archived',
            'message' => 'Ticket {ticket_id} "{title}" has been closed by {user_name}.',
            'recipients' => 'all_stakeholders',
            'priority' => 'normal',
            'channels' => ['in_app'],
            'action_url' => '/tickets/{ticket_id}',
        ],

        // Scenario 8: Ticket Reopened
        'reopened' => [
            'title' => 'Ticket Reopened',
            'description' => 'Previously closed ticket reopened for action',
            'message' => 'Ticket {ticket_id} "{title}" has been reopened by {user_name}.',
            'recipients' => 'assigned_team',
            'cc' => ['team_lead', 'creator'],
            'priority' => 'high',
            'channels' => ['in_app', 'push', 'email'],
            'action_url' => '/tickets/{ticket_id}',
            'email' => [
                'subject' => 'HIGH PRIORITY: Ticket {ticket_id} Reopened - Action Required',
                'greeting' => 'A previously closed ticket has been reopened and requires your attention.',
                'show_description' => true,
                'show_reopen_reason' => true,
                'show_original_reporter' => true,
                'show_customer' => true,
                'detail_mode' => 'reopened',
                'cta_text' => 'Please investigate the issue and provide an updated resolution.',
                'button_text' => 'View Ticket & Reassign',
            ],
        ],

        // Scenario 9: Ticket On Hold
        'on_hold' => [
            'title' => 'Ticket On Hold',
            'description' => 'Ticket temporarily on hold',
            'message' => 'Ticket {ticket_id} "{title}" has been placed on hold by {user_name}.',
            'recipients' => 'creator',
            'priority' => 'normal',
            'channels' => ['in_app'],
            'action_url' => '/tickets/{ticket_id}',
        ],

        // Scenario 10: Ticket Rejected - System Configuration
        'rejected_config' => [
            'title' => 'Ticket Rejected - Sys Config',
            'description' => 'Ticket rejected as system configuration issue',
            'message' => 'Ticket {ticket_id} "{title}" has been rejected by {user_name}. Reason: System Configuration.',
            'recipients' => 'creator',
            'cc' => ['fe_team_lead'],
            'priority' => 'normal',
            'channels' => ['in_app', 'push', 'email'],
            'action_url' => '/tickets/{ticket_id}',
            'email' => [
                'subject' => 'Ticket {ticket_id} Rejected - System Configuration Issue',
                'greeting' => 'Your ticket has been reviewed and rejected as it relates to system configuration rather than a bug or enhancement.',
                'show_rejection_reason' => true,
                'detail_mode' => 'rejected_config',
                'dont_show_cta' => true,
                'button_text' => 'View Ticket Details',
            ],
        ],

        // Scenario 11: Ticket Rejected - Change Request
        'rejected_change_request' => [
            'title' => 'Ticket Rejected - Change Request',
            'description' => 'Ticket rejected, submit as enhancement (P3-P5)',
            'message' => 'Ticket {ticket_id} "{title}" has been rejected by {user_name}. Reason: Change Request - Please create as P3-P5.',
            'recipients' => 'creator',
            'cc' => ['fe_team_lead'],
            'priority' => 'normal',
            'channels' => ['in_app', 'push', 'email'],
            'action_url' => '/tickets/{ticket_id}',
            'email' => [
                'subject' => 'Ticket {ticket_id} Rejected - Please Submit as Enhancement (P3-P5)',
                'greeting' => 'Your ticket has been reviewed and identified as a feature change request rather than a bug.',
                'show_rejection_reason' => true,
                'show_change_request_next_steps' => true,
                'detail_mode' => 'rejected_change_request',
                'dont_show_cta' => true,
                'button_text' => 'View Ticket Details',
            ],
        ],

        // Scenario 12: P4a Ticket Created (RFQ)
        'created_p4a' => [
            'title' => 'RFQ Ticket for Review',
            'description' => 'Request for Quotation requiring manday estimation',
            'message' => 'New RFQ ticket {ticket_id} "{title}" requires manday estimation.',
            'recipients' => 'all_teams',
            'cc' => ['pdt_team_lead', 'rnd_team_lead', 'qc_team_lead'],
            'priority' => 'normal',
            'channels' => ['in_app', 'push', 'email'],
            'action_url' => '/tickets/{ticket_id}',
            'email' => [
                'subject' => 'RFQ Ticket - {ticket_id} Requires Manday Estimation',
                'greeting' => 'A new Request for Quotation (RFQ) ticket has been created and requires your team\'s manday estimation.',
                'show_description' => true,
                'show_customer' => true,
                'show_rfq_action' => true,
                'detail_mode' => 'created',
                'dont_show_cta' => true,
                'button_text' => 'View Ticket & Add Estimation',
            ],
        ],

        // Scenario 13: PDT Manday Added
        'pdt_manday_added' => [
            'title' => 'PDT Estimation Submitted',
            'description' => 'PDT team has submitted manday estimation',
            'message' => 'PDT has added {pdt_mandays} mandays for ticket {ticket_id}.',
            'recipients' => 'creator',
            'priority' => 'normal',
            'channels' => ['in_app'],
            'action_url' => '/tickets/{ticket_id}',
        ],

        // Scenario 14: R&D Manday Added
        'rnd_manday_added' => [
            'title' => 'R&D Estimation Submitted',
            'description' => 'R&D team has submitted manday estimation',
            'message' => 'R&D has added {rnd_mandays} mandays for ticket {ticket_id}.',
            'recipients' => 'creator',
            'priority' => 'normal',
            'channels' => ['in_app'],
            'action_url' => '/tickets/{ticket_id}',
        ],

        // Scenario 16: All Mandays Completed (RFQ Complete)
        'all_mandays_completed' => [
            'title' => 'RFQ Ready for Customer',
            'description' => 'RFQ ready for customer quotation',
            'message' => 'All estimations complete for ticket {ticket_id} "{title}". Total: {total_mandays} mandays (PDT: {pdt_mandays}, R&D: {rnd_mandays}).',
            'recipients' => 'creator',
            'cc' => ['fe_team_lead', 'manager'],
            'priority' => 'normal',
            'channels' => ['in_app', 'push', 'email'],
            'action_url' => '/tickets/{ticket_id}',
            'email' => [
                'subject' => 'RFQ Complete - {ticket_id} Ready for Customer Quotation',
                'greeting' => 'Great news! All team estimations for your RFQ ticket have been completed. The quotation is ready to be sent to the customer.',
                'detail_mode' => 'all_mandays_completed',
                'cta_text' => 'You can now review the complete estimation and prepare the quotation for the customer.',
                'button_text' => 'View Complete RFQ Details',
            ],
        ],

        // Scenario 17: Comment Added
        'comment_created' => [
            'title' => 'New Comment on Ticket',
            'description' => 'New comment added to ticket',
            'message' => '{user_name} added a comment on ticket {ticket_id}: "{comment_preview}..."',
            'recipients' => 'ticket_stakeholders',
            'cc' => [],
            'priority' => 'normal',
            'channels' => ['in_app', 'push', 'email'],
            'action_url' => '/tickets/{ticket_id}#comment-{comment_id}',
            'email' => [
                'subject' => 'New Comment on Ticket {ticket_id}: {title}',
                'greeting' => 'Someone commented on a ticket you\'re involved with.',
                'show_comment' => true,
                'show_ticket_details' => true,
                'detail_mode' => 'comment',
                'cta_text' => 'View the conversation and respond if needed.',
                'button_text' => 'View Ticket',
            ],
        ],

        // Scenario 18: Mention in Comment
        'mention_in_comment' => [
            'title' => 'You were mentioned',
            'description' => 'You were mentioned in a ticket comment',
            'message' => '{user_name} mentioned you in ticket {ticket_id}: "{comment_preview}..."',
            'recipients' => 'mentioned_user',
            'priority' => 'normal',
            'channels' => ['in_app', 'push'],
            'action_url' => '/tickets/{ticket_id}#comment-{comment_id}',
        ],

        // PDT Rejected Request
        'pdt_rejected_request' => [
            'title' => 'Ticket Rejected by PDT',
            'description' => 'Ticket rejected by PDT team',
            'message' => 'Ticket {ticket_id} "{title}" has been rejected by PDT. Reason: {rejection_reason}.',
            'recipients' => 'creator',
            'cc' => ['fe_team_lead'],
            'priority' => 'normal',
            'channels' => ['in_app', 'push', 'email'],
            'action_url' => '/tickets/{ticket_id}',
            'email' => [
                'subject' => 'Ticket {ticket_id} Rejected by PDT',
                'greeting' => 'Your ticket has been reviewed and rejected by the PDT team.',
                'show_rejection_reason' => true,
                'detail_mode' => 'rejected',
                'dont_show_cta' => true,
                'button_text' => 'View Ticket Details',
            ],
        ],
    ],
];
