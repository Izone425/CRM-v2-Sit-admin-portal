<?php

namespace App\Console\Commands;

use App\Models\ImplementerAppointment;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Microsoft\Graph\Graph;

class FixOnlineMeetingId extends Command
{
    protected $signature = 'teams:fix-meeting-id {appointmentId? : Specific appointment ID to fix} {--all : Fix all appointments missing online_meeting_id}';
    protected $description = 'Retrieve and set online_meeting_id for appointments that have meeting_link but missing online_meeting_id';

    public function handle()
    {
        $appointmentId = $this->argument('appointmentId');
        $fixAll = $this->option('all');

        if (!$appointmentId && !$fixAll) {
            $this->error('Please provide an appointment ID or use --all flag');
            return 1;
        }

        try {
            $accessToken = MicrosoftGraphService::getAccessToken();
            $graph = new Graph();
            $graph->setAccessToken($accessToken);
            $this->info('✅ Graph API access token retrieved');
        } catch (\Exception $e) {
            $this->error('❌ Failed to get access token: ' . $e->getMessage());
            return 1;
        }

        if ($appointmentId) {
            $appointments = ImplementerAppointment::where('id', $appointmentId)->get();
            if ($appointments->isEmpty()) {
                $this->error("Appointment #{$appointmentId} not found");
                return 1;
            }
        } else {
            $appointments = ImplementerAppointment::whereNotNull('meeting_link')
                ->where('meeting_link', '!=', '')
                ->where('meeting_link', '!=', 'N/A')
                ->where(function ($q) {
                    $q->whereNull('online_meeting_id')
                      ->orWhere('online_meeting_id', '');
                })
                ->whereNotNull('event_id')
                ->where('event_id', '!=', '')
                ->get();
        }

        $this->info("Found {$appointments->count()} appointment(s) to process");

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;

        foreach ($appointments as $appointment) {
            $this->info("\n🔍 Processing appointment #{$appointment->id}");
            $this->info("   Implementer: {$appointment->implementer}");
            $this->info("   Meeting Link: " . substr($appointment->meeting_link, 0, 80) . '...');

            // Skip if already has online_meeting_id (for single appointment mode)
            if (!empty($appointment->online_meeting_id) && !$appointmentId) {
                $this->info("   ⏭️ Already has online_meeting_id, skipping");
                $skippedCount++;
                continue;
            }

            // Find the implementer user
            $implementer = User::where('name', $appointment->implementer)->first();
            if (!$implementer) {
                $this->warn("   ⚠️ Implementer not found: {$appointment->implementer}");
                $failCount++;
                continue;
            }

            $userIdentifier = $implementer->azure_user_id ?? $implementer->email;
            if (!$userIdentifier) {
                $this->warn("   ⚠️ No Azure user ID or email for implementer");
                $failCount++;
                continue;
            }

            $this->info("   User Identifier: {$userIdentifier}");

            try {
                // Query Graph API for online meeting by joinWebUrl
                $meetingLink = $appointment->meeting_link;
                $filterQuery = "joinWebUrl eq '$meetingLink'";

                $response = $graph->createRequest("GET", "/users/$userIdentifier/onlineMeetings?\$filter=$filterQuery")
                    ->execute();

                $responseBody = $response->getBody();

                if (isset($responseBody['value']) && count($responseBody['value']) > 0) {
                    $onlineMeetingId = $responseBody['value'][0]['id'] ?? null;

                    if ($onlineMeetingId) {
                        $appointment->update(['online_meeting_id' => $onlineMeetingId]);
                        $this->info("   ✅ online_meeting_id set: {$onlineMeetingId}");

                        // Enable auto-recording
                        try {
                            $graph->createRequest("PATCH", "/users/$userIdentifier/onlineMeetings/$onlineMeetingId")
                                ->attachBody(['recordAutomatically' => true])
                                ->execute();
                            $this->info("   ✅ Auto-recording enabled");
                        } catch (\Exception $e) {
                            $this->warn("   ⚠️ Failed to enable auto-recording: " . $e->getMessage());
                        }

                        $successCount++;
                    } else {
                        $this->warn("   ⚠️ Online meeting found but no ID in response");
                        $failCount++;
                    }
                } else {
                    $this->warn("   ⚠️ No online meeting found for this joinWebUrl");
                    $failCount++;
                }
            } catch (\Exception $e) {
                $this->error("   ❌ Graph API error: " . $e->getMessage());
                $failCount++;

                Log::error('Failed to fix online_meeting_id', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage(),
                ]);
            }

            usleep(300000); // 0.3s delay between API calls
        }

        $this->info("\n📊 Summary:");
        $this->info("✅ Fixed: {$successCount}");
        $this->info("⏭️ Skipped: {$skippedCount}");
        $this->error("❌ Failed: {$failCount}");

        return 0;
    }
}
