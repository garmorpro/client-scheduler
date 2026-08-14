<?php
// Shared "what do we call this engagement" logic. engagement_name is an
// optional column on `engagements` (see storage/migrations/2026-08-13_add_engagement_name.sql)
// for clients that run more than one engagement under different products
// (e.g. LivePerson: Conversation Cloud, Tenfold, Voicebase). When it's
// blank, the client's own name doubles as the engagement's name, exactly
// as it always implicitly did before this column existed - per Garrett,
// engagement_name should be optional everywhere, never required.

// The engagement's own name: engagement_name if set, otherwise client_name.
// Use this wherever a single, short label is needed (schedule pills, page
// titles) - it never repeats the client name alongside itself.
function engagement_display_name(?string $clientName, ?string $engagementName): string {
    $engagementName = trim((string) $engagementName);
    return $engagementName !== '' ? $engagementName : (string) $clientName;
}

// "LivePerson — Conversation Cloud" when the engagement has its own name
// distinct from the client, otherwise just "LivePerson" - the combined
// form only actually disambiguates anything when the two differ. Use this
// wherever multiple engagements for the same client need to be told apart
// (staffing autocomplete, engagement list rows).
function engagement_combined_label(?string $clientName, ?string $engagementName): string {
    $clientName = (string) $clientName;
    $engagementName = trim((string) $engagementName);
    if ($engagementName === '' || strcasecmp($engagementName, $clientName) === 0) {
        return $clientName;
    }
    return $clientName . ' — ' . $engagementName;
}
