<?php

namespace App\Console\Commands;

use App\Models\TrainingSession;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Microsoft\Graph\Graph;
use Aws\S3\S3Client;
use Carbon\Carbon;

class FetchTrainingRecordings extends Command
{
    protected $signature = 'training:fetch-recordings {--debug : Show detailed debug information} {--session= : Fetch for a specific session ID (bypasses date filter)}';
    protected $description = 'Fetch and download Teams meeting recordings for training sessions to S3';

    public function handle()
    {
        $this->info('Starting to fetch Teams training session recordings...');

        // Verify S3 configuration
        if (!env('AWS_ACCESS_KEY_ID') || !env('AWS_BUCKET')) {
            $this->error('AWS S3 credentials not configured!');
            $this->error('Please set AWS_ACCESS_KEY_ID and AWS_BUCKET in .env');
            return 1;
        }

        // Test S3 connection first
        if (!$this->testS3Connection()) {
            $this->error('S3 connection test failed! Check your credentials and bucket.');
            return 1;
        }

        try {
            $now = Carbon::now();

            $this->info("Current time: " . $now->format('Y-m-d H:i:s'));
            $this->info("Checking training sessions with meeting URLs that need recordings...");

            // Check if specific session ID is provided
            $sessionId = $this->option('session');

            if ($sessionId) {
                // Fetch specific session (bypass date filter)
                $this->info("Fetching specific session ID: {$sessionId}");
                $sessions = TrainingSession::where('id', $sessionId)
                    ->whereNotNull('organizer_email')
                    ->where('organizer_email', '!=', '')
                    ->get();
            } else {
                // Find training sessions that need checking:
                // 1. Has meeting link
                // 2. Meeting happened today
                // 3. Missing recording OR missing attendance
                $today = $now->format('Y-m-d');

                $sessions = TrainingSession::where(function ($query) use ($today) {
                    // Check for each day - has meeting link AND day is today AND (missing recording OR missing attendance)
                    for ($day = 1; $day <= 3; $day++) {
                        $query->orWhere(function ($q) use ($day, $today) {
                            $q->whereNotNull("day{$day}_meeting_link")
                                ->where("day{$day}_meeting_link", '!=', '')
                                ->where("day{$day}_meeting_link", '!=', 'N/A')
                                // Only check sessions from today
                                ->where("day{$day}_date", '=', $today)
                                // Missing recording OR missing attendance
                                ->where(function ($subQ) use ($day) {
                                    $subQ->where(function ($rq) use ($day) {
                                        $rq->whereNull("day{$day}_recording_link")
                                            ->orWhere("day{$day}_recording_link", '');
                                    })
                                    ->orWhereNull("day{$day}_attendance_report");
                                });
                        });
                    }
                })
                ->whereNotNull('organizer_email')
                ->where('organizer_email', '!=', '')
                ->whereIn('status', ['DRAFT', 'SCHEDULED', 'COMPLETED'])
                ->orderBy('day1_date', 'desc')
                ->get();
            }

            $this->info("Found {$sessions->count()} training sessions to check");

            if ($sessions->count() === 0) {
                $this->info("No training sessions found that match criteria. Exiting.");
                return 0;
            }

            $this->info("S3 Bucket: " . env('AWS_BUCKET'));
            $this->info("S3 Region: " . env('AWS_DEFAULT_REGION'));

            $successCount = 0;
            $failCount = 0;
            $noRecordingCount = 0;
            $skippedCount = 0;

            foreach ($sessions as $session) {
                $this->info("\n Processing Session #{$session->id} - {$session->session_number}");

                // Process each day (1, 2, 3)
                for ($dayNumber = 1; $dayNumber <= 3; $dayNumber++) {
                    $onlineMeetingId = $session->{"day{$dayNumber}_online_meeting_id"};
                    $meetingLink = $session->{"day{$dayNumber}_meeting_link"};
                    $existingRecording = $session->{"day{$dayNumber}_recording_link"};
                    $dayDate = $session->{"day{$dayNumber}_date"};
                    $lastFetched = $session->{"day{$dayNumber}_recording_fetched_at"};

                    // Skip if no meeting link
                    if (empty($meetingLink) || $meetingLink === 'N/A') {
                        $this->info("  Day {$dayNumber}: Skipped - No meeting link");
                        $skippedCount++;
                        continue;
                    }

                    // Fetch online_meeting_id if needed (for both recording and attendance)
                    if (empty($onlineMeetingId)) {
                        $this->info("    Fetching online_meeting_id...");
                        $onlineMeetingId = $this->fetchOnlineMeetingId($session, $dayNumber, $meetingLink);
                        if ($onlineMeetingId) {
                            $session->update([
                                "day{$dayNumber}_online_meeting_id" => $onlineMeetingId,
                            ]);
                        }
                    }

                    if (!empty($existingRecording)) {
                        $this->info("  Day {$dayNumber}: Recording exists, checking for additional parts and attendance...");

                        // Check if there might be more recording parts
                        if (!empty($onlineMeetingId)) {
                            $this->checkForAdditionalRecordings($session, $dayNumber, $onlineMeetingId, $existingRecording);

                            // Always try to update attendance report
                            $this->fetchAndStoreAttendanceReport($session, $dayNumber, $onlineMeetingId);
                        }

                        $skippedCount++;
                        continue;
                    }

                    // Check if the day has passed (only fetch recordings for past days)
                    // NOTE: Commented out for testing - uncomment in production
                    $dayDateCarbon = Carbon::parse($dayDate);
                    // if ($dayDateCarbon->isFuture()) {
                    //     $this->info("  Day {$dayNumber}: Skipped - Future date");
                    //     $skippedCount++;
                    //     continue;
                    // }

                    // Check if recently checked (prevent too frequent checks)
                    // if ($lastFetched) {
                    //     $lastCheckedTime = Carbon::parse($lastFetched);
                    //     if ($lastCheckedTime->diffInMinutes($now) < 10) {
                    //         $this->info("  Day {$dayNumber}: Skipped - Recently checked");
                    //         $skippedCount++;
                    //         continue;
                    //     }
                    // }

                    $this->info("  Day {$dayNumber}: Checking for recording...");

                    // If no online_meeting_id, try to fetch it using meeting link
                    if (empty($onlineMeetingId)) {
                        $this->info("    No online_meeting_id stored, fetching from MS Graph...");
                        $onlineMeetingId = $this->fetchOnlineMeetingId($session, $dayNumber, $meetingLink);

                        if ($onlineMeetingId) {
                            // Store the online_meeting_id for future use
                            $session->update([
                                "day{$dayNumber}_online_meeting_id" => $onlineMeetingId,
                            ]);
                            $this->info("    Fetched and stored online_meeting_id: {$onlineMeetingId}");
                        } else {
                            $this->warn("    Could not fetch online_meeting_id");
                            $session->update([
                                "day{$dayNumber}_recording_fetched_at" => now(),
                            ]);
                            $skippedCount++;
                            continue;
                        }
                    }

                    $this->info("    Online Meeting ID: {$onlineMeetingId}");

                    try {
                        $recordingInfo = $this->fetchAndDownloadRecording($session, $dayNumber, $onlineMeetingId);

                        if ($recordingInfo) {
                            $session->update([
                                "day{$dayNumber}_recording_link" => $recordingInfo['public_url'],
                                "day{$dayNumber}_recording_fetched_at" => now(),
                            ]);

                            $successCount++;
                            $this->info("  Day {$dayNumber}: Recording processed successfully");
                            $this->info("    Total parts: " . ($recordingInfo['total_parts'] ?? 1));

                            Log::info('Training recording fetched successfully', [
                                'session_id' => $session->id,
                                'day' => $dayNumber,
                                'file_path' => $recordingInfo['file_path'],
                                'public_url' => $recordingInfo['public_url'],
                            ]);

                            // Also fetch attendance report
                            $this->fetchAndStoreAttendanceReport($session, $dayNumber, $onlineMeetingId);
                        } else {
                            // Update last checked time even if no recordings found
                            $session->update([
                                "day{$dayNumber}_recording_fetched_at" => now(),
                            ]);

                            $noRecordingCount++;
                            $this->warn("  Day {$dayNumber}: No recording available yet");

                            // Still try to fetch attendance report even if no recording
                            $this->fetchAndStoreAttendanceReport($session, $dayNumber, $onlineMeetingId);
                        }
                    } catch (\Exception $e) {
                        $failCount++;
                        $this->error("  Day {$dayNumber}: Failed - {$e->getMessage()}");

                        if ($this->option('debug')) {
                            $this->error("Stack trace: " . $e->getTraceAsString());
                        }

                        Log::error('Failed to fetch training recording', [
                            'session_id' => $session->id,
                            'day' => $dayNumber,
                            'error' => $e->getMessage(),
                        ]);
                    }

                    usleep(500000); // 0.5 second delay between days
                }
            }

            $this->info("\n Summary:");
            $this->info(" Successfully processed: {$successCount}");
            $this->info(" Skipped: {$skippedCount}");
            $this->warn(" No recordings available: {$noRecordingCount}");
            $this->error(" Failed: {$failCount}");

            Log::info('Training recordings fetch completed', [
                'total_sessions' => $sessions->count(),
                'success_count' => $successCount,
                'skipped_count' => $skippedCount,
                'no_recording_count' => $noRecordingCount,
                'fail_count' => $failCount,
            ]);

        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");
            Log::error('Training recordings fetch command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }

        return 0;
    }

    private function testS3Connection(): bool
    {
        try {
            $this->info("Testing S3 connection...");

            $testContent = 'S3 connection test - ' . now()->toDateTimeString();
            $testPath = 'test/training-connection-test-' . time() . '.txt';

            $result = Storage::disk('s3')->put($testPath, $testContent);

            if ($result) {
                $this->info(" S3 write test successful");

                if (Storage::disk('s3')->exists($testPath)) {
                    $this->info(" S3 read test successful");
                    Storage::disk('s3')->delete($testPath);
                    $this->info(" S3 delete test successful");
                    return true;
                }
            }

            $this->error(" S3 connection test failed");
            return false;

        } catch (\Exception $e) {
            $this->error(" S3 connection error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetch the online meeting ID using the meeting link
     */
    private function fetchOnlineMeetingId(TrainingSession $session, int $dayNumber, string $meetingLink): ?string
    {
        $organizerEmail = $session->organizer_email;

        if (empty($organizerEmail)) {
            Log::warning('No organizer email for session', ['session_id' => $session->id]);
            return null;
        }

        try {
            $accessToken = MicrosoftGraphService::getAccessToken();
            $graph = new Graph();
            $graph->setAccessToken($accessToken);

            // Try with organizer email first
            $organizer = User::where('email', $organizerEmail)->first();
            $queryIdentifier = $organizer->azure_user_id ?? $organizerEmail;

            $filterQuery = "joinWebUrl eq '$meetingLink'";

            Log::info("Querying online meeting ID", [
                'session_id' => $session->id,
                'day' => $dayNumber,
                'query_identifier' => $queryIdentifier,
                'meeting_link' => $meetingLink,
            ]);

            $response = $graph->createRequest("GET", "/users/$queryIdentifier/onlineMeetings?\$filter=$filterQuery")
                ->execute();

            $responseBody = $response->getBody();

            if (isset($responseBody['value']) && count($responseBody['value']) > 0) {
                $meetingData = $responseBody['value'][0];
                $onlineMeetingId = $meetingData['id'] ?? null;

                Log::info("Found online meeting ID", [
                    'session_id' => $session->id,
                    'day' => $dayNumber,
                    'online_meeting_id' => $onlineMeetingId,
                ]);

                return $onlineMeetingId;
            }

            Log::warning("No meeting found for link", [
                'session_id' => $session->id,
                'day' => $dayNumber,
                'meeting_link' => $meetingLink,
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error("Failed to fetch online meeting ID", [
                'session_id' => $session->id,
                'day' => $dayNumber,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function fetchAndDownloadRecording(TrainingSession $session, int $dayNumber, string $onlineMeetingId): ?array
    {
        // Get the organizer email to query the meeting
        $organizerEmail = $session->organizer_email;

        if (empty($organizerEmail)) {
            throw new \Exception("No organizer email found for session");
        }

        // Try to find the user to get azure_user_id
        $organizer = User::where('email', $organizerEmail)->first();
        $userIdentifier = $organizer->azure_user_id ?? $organizerEmail;

        $accessToken = MicrosoftGraphService::getAccessToken();
        $graph = new Graph();
        $graph->setAccessToken($accessToken);

        $endpoint = "/users/{$userIdentifier}/onlineMeetings/{$onlineMeetingId}/recordings";

        try {
            $response = $graph->createRequest("GET", $endpoint)->execute();
            $responseBody = $response->getBody();

            Log::info('Training recording API response', [
                'session_id' => $session->id,
                'day' => $dayNumber,
                'total_recordings' => count($responseBody['value'] ?? []),
            ]);

            if (isset($responseBody['value']) && count($responseBody['value']) > 0) {
                $recordings = $responseBody['value'];
                $uploadedRecordings = [];
                $publicUrls = [];

                $s3Client = new S3Client([
                    'version' => 'latest',
                    'region' => env('AWS_DEFAULT_REGION'),
                    'credentials' => [
                        'key' => env('AWS_ACCESS_KEY_ID'),
                        'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    ],
                ]);

                foreach ($recordings as $index => $recording) {
                    $recordingId = $recording['id'];
                    $partNumber = $index + 1;
                    $totalParts = count($recordings);

                    $createdAt = Carbon::parse($recording['createdDateTime'])->format('Y-m-d_His');

                    if ($totalParts > 1) {
                        $filename = "training_recording_{$session->id}_day{$dayNumber}_{$createdAt}_part{$partNumber}.mp4";
                    } else {
                        $filename = "training_recording_{$session->id}_day{$dayNumber}_{$createdAt}.mp4";
                    }

                    $directory = "training-recordings/" . date('Y/m');
                    $filePath = "{$directory}/{$filename}";

                    // Check if file already exists in S3
                    try {
                        $fileExists = $s3Client->doesObjectExist(env('AWS_BUCKET'), $filePath);

                        if ($fileExists) {
                            $this->info("    Part {$partNumber} already exists in S3, skipping upload");

                            $bucket = env('AWS_BUCKET');
                            $region = env('AWS_DEFAULT_REGION');
                            $publicUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$filePath}";
                            $publicUrls[] = $publicUrl;

                            $uploadedRecordings[] = [
                                'part' => $partNumber,
                                'file_path' => $filePath,
                                'public_url' => $publicUrl,
                                'status' => 'duplicate_skipped',
                            ];

                            continue;
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to check S3 file existence, proceeding with upload', [
                            'session_id' => $session->id,
                            'day' => $dayNumber,
                            'error' => $e->getMessage()
                        ]);
                    }

                    // Download the recording
                    $contentUrl = $recording['recordingContentUrl'];

                    Log::info("Downloading training recording Part {$partNumber}/{$totalParts}", [
                        'session_id' => $session->id,
                        'day' => $dayNumber,
                        'part_number' => $partNumber,
                    ]);

                    try {
                        $videoResponse = Http::timeout(600)->withHeaders([
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Accept' => 'application/octet-stream',
                        ])->get($contentUrl);

                        if (!$videoResponse->successful()) {
                            $this->warn("    Failed to download Part {$partNumber}: " . $videoResponse->body());
                            continue;
                        }

                        $recordingContent = $videoResponse->body();
                        $fileSize = strlen($recordingContent);
                        $fileSizeMB = round($fileSize / 1024 / 1024, 2);

                        // Upload to S3
                        $s3Client->putObject([
                            'Bucket' => env('AWS_BUCKET'),
                            'Key' => $filePath,
                            'Body' => $recordingContent,
                            'ContentType' => 'video/mp4',
                            'CacheControl' => 'max-age=31536000',
                            'Metadata' => [
                                'session-id' => (string)$session->id,
                                'session-number' => $session->session_number,
                                'day' => (string)$dayNumber,
                                'part-number' => (string)$partNumber,
                                'total-parts' => (string)$totalParts,
                                'upload-timestamp' => now()->toISOString(),
                            ]
                        ]);

                        // Verify upload
                        $exists = $s3Client->doesObjectExist(env('AWS_BUCKET'), $filePath);

                        if (!$exists) {
                            throw new \Exception("Part {$partNumber} file not found in S3 after upload");
                        }

                        $bucket = env('AWS_BUCKET');
                        $region = env('AWS_DEFAULT_REGION');
                        $publicUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/{$filePath}";

                        $uploadedRecordings[] = [
                            'part' => $partNumber,
                            'file_path' => $filePath,
                            'public_url' => $publicUrl,
                            'file_size_mb' => $fileSizeMB,
                            'status' => 'newly_uploaded',
                        ];

                        $publicUrls[] = $publicUrl;

                        $this->info("    Part {$partNumber}/{$totalParts} uploaded successfully ({$fileSizeMB}MB)");

                        Log::info("Training recording Part {$partNumber} uploaded to S3", [
                            'session_id' => $session->id,
                            'day' => $dayNumber,
                            'part_number' => $partNumber,
                            'file_path' => $filePath,
                            'file_size_mb' => $fileSizeMB,
                        ]);

                    } catch (\Exception $e) {
                        $this->error("    Failed to process Part {$partNumber}: {$e->getMessage()}");
                        Log::error("Failed to process training recording part", [
                            'session_id' => $session->id,
                            'day' => $dayNumber,
                            'part_number' => $partNumber,
                            'error' => $e->getMessage(),
                        ]);
                        continue;
                    }

                    usleep(250000); // 0.25 second delay between parts
                }

                if (!empty($publicUrls)) {
                    return [
                        'file_path' => $uploadedRecordings[0]['file_path'],
                        'public_url' => implode(';', $publicUrls),
                        'all_parts' => $uploadedRecordings,
                        'total_parts' => count($publicUrls),
                    ];
                }
            }

            return null;

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                Log::debug('No training recordings available yet', [
                    'session_id' => $session->id,
                    'day' => $dayNumber,
                ]);
                return null;
            }

            throw $e;
        }
    }

    /**
     * Check for additional recordings that might have been added after initial fetch
     */
    private function checkForAdditionalRecordings(TrainingSession $session, int $dayNumber, string $onlineMeetingId, string $existingRecordingUrls): void
    {
        $organizerEmail = $session->organizer_email;

        if (empty($organizerEmail)) {
            return;
        }

        try {
            $organizer = User::where('email', $organizerEmail)->first();
            $userIdentifier = $organizer->azure_user_id ?? $organizerEmail;

            $accessToken = MicrosoftGraphService::getAccessToken();
            $graph = new Graph();
            $graph->setAccessToken($accessToken);

            $endpoint = "/users/{$userIdentifier}/onlineMeetings/{$onlineMeetingId}/recordings";
            $response = $graph->createRequest("GET", $endpoint)->execute();
            $responseBody = $response->getBody();

            if (isset($responseBody['value']) && count($responseBody['value']) > 0) {
                $totalApiRecordings = count($responseBody['value']);
                $existingCount = substr_count($existingRecordingUrls, ';') + 1;

                if ($totalApiRecordings > $existingCount) {
                    $this->info("    Found {$totalApiRecordings} recordings in API, but only {$existingCount} saved. Fetching new ones...");

                    // Re-fetch all recordings (the method handles duplicates)
                    $recordingInfo = $this->fetchAndDownloadRecording($session, $dayNumber, $onlineMeetingId);

                    if ($recordingInfo) {
                        $session->update([
                            "day{$dayNumber}_recording_link" => $recordingInfo['public_url'],
                            "day{$dayNumber}_recording_fetched_at" => now(),
                        ]);

                        $this->info("    Updated recordings: " . ($recordingInfo['total_parts'] ?? 1) . " total parts");

                        Log::info('Additional training recordings fetched', [
                            'session_id' => $session->id,
                            'day' => $dayNumber,
                            'total_parts' => $recordingInfo['total_parts'] ?? 1,
                        ]);
                    }
                } else {
                    $this->info("    No additional recordings found ({$existingCount} parts already saved)");
                }
            }

        } catch (\Exception $e) {
            // Silently ignore errors when checking for additional recordings
            Log::debug('Error checking for additional recordings', [
                'session_id' => $session->id,
                'day' => $dayNumber,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Fetch and store the attendance report for a training session day
     * Uses $expand=attendanceRecords as per MS Graph API documentation
     * Will keep trying to fetch/update until complete data is available
     */
    private function fetchAndStoreAttendanceReport(TrainingSession $session, int $dayNumber, string $onlineMeetingId): void
    {
        $existingReport = $session->{"day{$dayNumber}_attendance_report"};

        // Always try to fetch latest attendance data (attendees might increase)

        $organizerEmail = $session->organizer_email;

        if (empty($organizerEmail)) {
            $this->warn("    No organizer email for attendance report");
            return;
        }

        try {
            $organizer = User::where('email', $organizerEmail)->first();
            $userIdentifier = $organizer->azure_user_id ?? $organizerEmail;

            $accessToken = MicrosoftGraphService::getAccessToken();
            $graph = new Graph();
            $graph->setAccessToken($accessToken);

            // Get attendance reports for the meeting with expanded attendance records
            // Using $expand=attendanceRecords as per MS Graph API documentation
            $endpoint = "/users/{$userIdentifier}/onlineMeetings/{$onlineMeetingId}/attendanceReports?\$expand=attendanceRecords";

            if ($this->option('debug')) {
                $this->info("    API Endpoint: {$endpoint}");
            }

            $response = $graph->createRequest("GET", $endpoint)->execute();
            $responseBody = $response->getBody();

            if ($this->option('debug')) {
                $this->info("    Response: " . json_encode($responseBody, JSON_PRETTY_PRINT));
            }

            Log::info('Training attendance report API response', [
                'session_id' => $session->id,
                'day' => $dayNumber,
                'total_reports' => count($responseBody['value'] ?? []),
                'endpoint' => $endpoint,
            ]);

            if (isset($responseBody['value']) && count($responseBody['value']) > 0) {
                $allAttendees = [];
                $meetingStartTime = null;
                $meetingEndTime = null;

                // Process each attendance report
                foreach ($responseBody['value'] as $report) {
                    $reportId = $report['id'];
                    $totalParticipants = $report['totalParticipantCount'] ?? 0;
                    $meetingStartTime = $report['meetingStartDateTime'] ?? $meetingStartTime;
                    $meetingEndTime = $report['meetingEndDateTime'] ?? $meetingEndTime;

                    $this->info("    Processing attendance report (Report ID: {$reportId}, Participants: {$totalParticipants})");

                    // Attendance records should be inline with $expand
                    if (isset($report['attendanceRecords']) && is_array($report['attendanceRecords'])) {
                        foreach ($report['attendanceRecords'] as $record) {
                            $attendeeEmail = $record['emailAddress'] ?? '';
                            $attendeeName = $record['identity']['displayName'] ?? 'Unknown';
                            $role = $record['role'] ?? 'Attendee';
                            $totalAttendanceInSeconds = $record['totalAttendanceInSeconds'] ?? 0;

                            // Get attendance intervals
                            $intervals = [];
                            if (isset($record['attendanceIntervals'])) {
                                foreach ($record['attendanceIntervals'] as $interval) {
                                    $intervals[] = [
                                        'join_time' => $interval['joinDateTime'] ?? null,
                                        'leave_time' => $interval['leaveDateTime'] ?? null,
                                        'duration_seconds' => $interval['durationInSeconds'] ?? 0,
                                    ];
                                }
                            }

                            $allAttendees[] = [
                                'email' => $attendeeEmail,
                                'name' => $attendeeName,
                                'role' => $role,
                                'total_attendance_seconds' => $totalAttendanceInSeconds,
                                'total_attendance_minutes' => round($totalAttendanceInSeconds / 60, 2),
                                'intervals' => $intervals,
                            ];
                        }

                        $this->info("      Found " . count($report['attendanceRecords']) . " attendance records in report");
                    } else {
                        // Fallback: If attendanceRecords not in response, try fetching separately
                        $this->warn("    No inline attendanceRecords, trying separate endpoint...");

                        $recordsEndpoint = "/users/{$userIdentifier}/onlineMeetings/{$onlineMeetingId}/attendanceReports/{$reportId}/attendanceRecords";

                        try {
                            $recordsResponse = $graph->createRequest("GET", $recordsEndpoint)->execute();
                            $recordsBody = $recordsResponse->getBody();

                            if (isset($recordsBody['value'])) {
                                foreach ($recordsBody['value'] as $record) {
                                    $attendeeEmail = $record['emailAddress'] ?? '';
                                    $attendeeName = $record['identity']['displayName'] ?? 'Unknown';
                                    $role = $record['role'] ?? 'Attendee';
                                    $totalAttendanceInSeconds = $record['totalAttendanceInSeconds'] ?? 0;

                                    // Get attendance intervals
                                    $intervals = [];
                                    if (isset($record['attendanceIntervals'])) {
                                        foreach ($record['attendanceIntervals'] as $interval) {
                                            $intervals[] = [
                                                'join_time' => $interval['joinDateTime'] ?? null,
                                                'leave_time' => $interval['leaveDateTime'] ?? null,
                                                'duration_seconds' => $interval['durationInSeconds'] ?? 0,
                                            ];
                                        }
                                    }

                                    $allAttendees[] = [
                                        'email' => $attendeeEmail,
                                        'name' => $attendeeName,
                                        'role' => $role,
                                        'total_attendance_seconds' => $totalAttendanceInSeconds,
                                        'total_attendance_minutes' => round($totalAttendanceInSeconds / 60, 2),
                                        'intervals' => $intervals,
                                    ];
                                }

                                $this->info("      Found " . count($recordsBody['value']) . " attendance records via separate endpoint");
                            }
                        } catch (\Exception $e) {
                            Log::warning("Failed to fetch attendance records for report {$reportId}", [
                                'session_id' => $session->id,
                                'day' => $dayNumber,
                                'error' => $e->getMessage(),
                            ]);

                            if ($this->option('debug')) {
                                $this->error("      Fallback endpoint error: " . $e->getMessage());
                            }
                        }
                    }
                }

                if (!empty($allAttendees)) {
                    // Check if this is an update or new data
                    $previousCount = 0;
                    if (!empty($existingReport)) {
                        $existingData = is_array($existingReport) ? $existingReport : json_decode($existingReport, true);
                        $previousCount = $existingData['total_attendees'] ?? 0;
                    }

                    // Store attendance report
                    $attendanceData = [
                        'fetched_at' => now()->toISOString(),
                        'total_attendees' => count($allAttendees),
                        'meeting_start' => $meetingStartTime,
                        'meeting_end' => $meetingEndTime,
                        'attendees' => $allAttendees,
                    ];

                    $session->update([
                        "day{$dayNumber}_attendance_report" => $attendanceData,
                    ]);

                    // Show appropriate message based on whether this is new or updated
                    if ($previousCount > 0) {
                        if (count($allAttendees) != $previousCount) {
                            $this->info("    Attendance report updated: {$previousCount} -> " . count($allAttendees) . " attendees");
                        } else {
                            $this->info("    Attendance report unchanged: " . count($allAttendees) . " attendees");
                        }
                    } else {
                        $this->info("    Attendance report saved: " . count($allAttendees) . " attendees");
                    }

                    Log::info('Training attendance report saved', [
                        'session_id' => $session->id,
                        'day' => $dayNumber,
                        'total_attendees' => count($allAttendees),
                        'previous_count' => $previousCount,
                    ]);
                } else {
                    $this->warn("    No attendance records found in any reports (will retry later)");
                }
            } else {
                $this->warn("    No attendance reports available yet");

                if ($this->option('debug')) {
                    $this->info("    Full response: " . json_encode($responseBody, JSON_PRETTY_PRINT));
                }
            }

        } catch (\Exception $e) {
            if (strpos($e->getMessage(), '404') !== false) {
                $this->warn("    Attendance reports not available yet (404)");
                Log::debug('Attendance reports not available yet', [
                    'session_id' => $session->id,
                    'day' => $dayNumber,
                ]);
            } elseif (strpos($e->getMessage(), '403') !== false) {
                $this->error("    Permission denied (403) - Check OnlineMeetingArtifact.Read.All permission");
                Log::error('Attendance report permission denied', [
                    'session_id' => $session->id,
                    'day' => $dayNumber,
                    'error' => $e->getMessage(),
                ]);
            } else {
                $this->error("    Failed to fetch attendance report: {$e->getMessage()}");
                Log::error('Failed to fetch training attendance report', [
                    'session_id' => $session->id,
                    'day' => $dayNumber,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
