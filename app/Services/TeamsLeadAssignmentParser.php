<?php

namespace App\Services;

class TeamsLeadAssignmentParser
{
    public function parse(string $message): array
    {
        $text = trim(strip_tags($message));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        preg_match_all('/Lead\s*ID\s*[-:]\s*(\d+)/i', $text, $idMatches);
        $leadIds = array_map('intval', $idMatches[1] ?? []);

        $ownerAlias = null;
        if (preg_match('/Assign\s*to\s*[-:]?\s*([^\r\n]+)/i', $text, $ownerMatch)) {
            $ownerAlias = trim($ownerMatch[1]);
        }

        $ownerName = $this->resolveOwner($ownerAlias);

        return [
            'lead_ids' => array_values(array_unique($leadIds)),
            'owner_alias' => $ownerAlias,
            'owner_name' => $ownerName,
        ];
    }

    private function resolveOwner(?string $alias): ?string
    {
        if (!$alias) {
            return null;
        }

        $aliases = config('teams_lead_assignment.owner_aliases', []);
        $key = strtolower(trim($alias));

        return $aliases[$key] ?? $alias;
    }
}
