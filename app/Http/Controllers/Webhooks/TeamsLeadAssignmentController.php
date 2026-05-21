<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\TeamsLeadAssignmentParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamsLeadAssignmentController extends Controller
{
    public function __invoke(Request $request, TeamsLeadAssignmentParser $parser): JsonResponse
    {
        $secret = config('teams_lead_assignment.webhook_secret');
        if (!$secret || !hash_equals($secret, (string) $request->header('X-Webhook-Secret'))) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $messageId = (string) $request->input('message_id', '');
        $messageText = (string) $request->input('message', '');

        if ($messageText === '') {
            return response()->json(['error' => 'empty message'], 422);
        }

        $parsed = $parser->parse($messageText);

        if (empty($parsed['lead_ids']) || !$parsed['owner_name']) {
            Log::warning('Teams lead assignment: unparseable', [
                'message_id' => $messageId,
                'message' => $messageText,
                'parsed' => $parsed,
            ]);

            return response()->json([
                'status' => 'unparseable',
                'parsed' => $parsed,
            ], 422);
        }

        $defaults = config('teams_lead_assignment.defaults');

        $updatePayload = array_merge($defaults, [
            'lead_owner' => $parsed['owner_name'],
        ]);

        $updatedIds = [];
        $missingIds = [];

        DB::transaction(function () use ($parsed, $updatePayload, &$updatedIds, &$missingIds) {
            foreach ($parsed['lead_ids'] as $leadId) {
                $affected = Lead::where('id', $leadId)->update($updatePayload);

                if ($affected > 0) {
                    $updatedIds[] = $leadId;
                } else {
                    $missingIds[] = $leadId;
                }
            }
        });

        Log::info('Teams lead assignment processed', [
            'message_id' => $messageId,
            'owner' => $parsed['owner_name'],
            'updated' => $updatedIds,
            'missing' => $missingIds,
        ]);

        return response()->json([
            'status' => 'ok',
            'owner' => $parsed['owner_name'],
            'updated' => $updatedIds,
            'missing' => $missingIds,
        ]);
    }
}
