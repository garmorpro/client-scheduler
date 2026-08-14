<?php
// Shared parse+validate logic for the bulk Engagement/Hours import
// (Settings > Import Engagements). Used identically by the "dry run"
// validate endpoint and the commit endpoint - the commit endpoint re-runs
// this same function immediately before writing, rather than trusting a
// report the browser already showed, since time may have passed between
// the two requests (someone else could have added a same-named client,
// the file could have been re-picked, etc).
//
// Returns an array shaped like:
// [
//   'ok' => bool,                 // no blocking errors - safe to commit
//   'errors' => [ ['sheet','row','message'], ... ],
//   'warnings' => [ ['sheet','row','message'], ... ],
//   'summary' => [ 'new_clients', 'existing_clients', 'new_engagements',
//                  'existing_engagements', 'hours_rows', 'total_hours' ],
//   'clients_to_create' => [ ['client_name','onboarded_date','status','notes'], ... ],
//   'engagements_to_create' => [ engagement_key => [...fields...], ... ],
//   'hours_rows' => [ ['engagement_key','user_id','week_start','hours','audit_type_id'], ... ],
// ]
//
// Nothing in this file writes to the database - every $conn use below is a
// read (existence checks), even inside the code paths the commit endpoint
// calls right before it starts writing.

require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// engagement_name is optional (see storage/migrations/2026-08-13_add_engagement_name.sql) -
// a client with just one engagement a year doesn't need one, but a client
// running several concurrent engagements under different products (e.g.
// LivePerson: Conversation Cloud, Tenfold, Voicebase) needs it to keep
// them from colliding under the old client+year-only key, where the 2nd
// and 3rd same-year engagement for one client would silently look like a
// duplicate of the 1st and get skipped. Blank/null normalizes to '' on
// both sides (DB rows and sheet rows), so a client with a single unnamed
// engagement still keys exactly as it always did.
function engagement_import_key(string $clientName, $year, string $engagementName = ''): string {
    return strtolower(trim($clientName)) . '|' . trim((string) $year) . '|' . strtolower(trim($engagementName));
}

function engagement_import_parse_date($value): ?string {
    if ($value === null || $value === '') return null;
    if (is_numeric($value)) {
        // Excel serial date (PhpSpreadsheet hands these back as numbers when
        // a cell is formatted as a date but read via getValue()).
        try {
            $dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
            return $dt->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
    $value = trim((string) $value);
    $ts = strtotime($value);
    if ($ts === false) return null;
    return date('Y-m-d', $ts);
}

function engagement_import_read_sheet($spreadsheet, string $name): ?array {
    // Case-insensitive sheet name match ("weekly hours" vs "Weekly Hours").
    foreach ($spreadsheet->getSheetNames() as $sheetName) {
        if (strcasecmp(trim($sheetName), $name) === 0) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            $rows = $sheet->toArray(null, true, true, false);
            if (empty($rows)) return [];
            $header = array_map(function ($h) { return strtolower(trim((string) $h)); }, array_shift($rows));
            $out = [];
            foreach ($rows as $rowIdx => $row) {
                // Skip fully blank rows (common at the end of a filled-in
                // template, or between example rows someone left as spacers).
                $allBlank = true;
                foreach ($row as $cell) {
                    if (trim((string) $cell) !== '') { $allBlank = false; break; }
                }
                if ($allBlank) continue;
                $assoc = [];
                foreach ($header as $i => $colName) {
                    if ($colName === '') continue;
                    $assoc[$colName] = $row[$i] ?? null;
                }
                // +2: header is row 1, $rowIdx is 0-based after the shift.
                $assoc['__row'] = $rowIdx + 2;
                $out[] = $assoc;
            }
            return $out;
        }
    }
    return null; // sheet not present at all
}

function parse_and_validate_engagement_import(mysqli $conn, string $filePath): array {
    $errors = [];
    $warnings = [];

    try {
        $spreadsheet = IOFactory::load($filePath);
    } catch (\Throwable $e) {
        return [
            'ok' => false,
            'errors' => [['sheet' => 'File', 'row' => null, 'message' => 'Could not read this file as an Excel workbook: ' . $e->getMessage()]],
            'warnings' => [],
            'summary' => null,
            'clients_to_create' => [],
            'engagements_to_create' => [],
            'hours_rows' => [],
        ];
    }

    $clientsRows = engagement_import_read_sheet($spreadsheet, 'Clients') ?? [];
    $engagementsRows = engagement_import_read_sheet($spreadsheet, 'Engagements') ?? [];
    $hoursRows = engagement_import_read_sheet($spreadsheet, 'Weekly Hours');

    if ($hoursRows === null) {
        $errors[] = ['sheet' => 'Weekly Hours', 'row' => null, 'message' => "This workbook has no 'Weekly Hours' sheet - that's the one required sheet (Clients and Engagements are only needed for records that don't already exist)."];
        return [
            'ok' => false, 'errors' => $errors, 'warnings' => $warnings, 'summary' => null,
            'clients_to_create' => [], 'engagements_to_create' => [], 'hours_rows' => [],
        ];
    }

    // ---------------------------------------------------------------
    // Existing DB lookups
    // ---------------------------------------------------------------
    $existingClients = []; // lowercased trimmed name => true
    $res = $conn->query("SELECT client_name FROM clients");
    while ($row = $res->fetch_assoc()) $existingClients[strtolower(trim($row['client_name']))] = true;

    $existingEngagements = []; // "client|year|engagement_name" => ['engagement_id' => id, 'audit_type_ids' => [...]]
    $res = $conn->query("SELECT engagement_id, client_name, engagement_name, year FROM engagements");
    while ($row = $res->fetch_assoc()) {
        $key = engagement_import_key($row['client_name'], $row['year'], $row['engagement_name'] ?? '');
        $existingEngagements[$key] = ['engagement_id' => (int) $row['engagement_id'], 'audit_type_ids' => []];
    }
    if (!empty($existingEngagements)) {
        $ids = array_map(function ($e) { return $e['engagement_id']; }, $existingEngagements);
        $idList = implode(',', array_map('intval', $ids));
        $res = $conn->query("SELECT engagement_id, audit_type_id FROM engagement_audit_types WHERE engagement_id IN ($idList)");
        $byId = [];
        foreach ($existingEngagements as $key => $e) $byId[$e['engagement_id']] = $key;
        while ($row = $res->fetch_assoc()) {
            $key = $byId[(int) $row['engagement_id']] ?? null;
            if ($key) $existingEngagements[$key]['audit_type_ids'][] = (int) $row['audit_type_id'];
        }
    }

    $auditTypesByName = []; // lowercased name => ['id'=>, 'name'=>]
    $res = $conn->query("SELECT audit_type_id, name FROM audit_types WHERE is_active = 1");
    while ($row = $res->fetch_assoc()) $auditTypesByName[strtolower(trim($row['name']))] = ['id' => (int) $row['audit_type_id'], 'name' => $row['name']];

    $usersByName = []; // lowercased full_name => [user_id, user_id, ...] (array to detect ambiguity)
    $res = $conn->query("SELECT user_id, full_name FROM users WHERE status = 'active'");
    while ($row = $res->fetch_assoc()) {
        $key = strtolower(trim($row['full_name']));
        $usersByName[$key][] = (int) $row['user_id'];
    }

    // ---------------------------------------------------------------
    // Clients sheet
    // ---------------------------------------------------------------
    $clientsToCreate = [];
    $clientsSeenInFile = []; // lowercased name => row number first seen
    $knownClientNames = $existingClients; // union, grows as we validate

    foreach ($clientsRows as $row) {
        $rowNum = $row['__row'];
        $name = trim((string) ($row['client_name'] ?? ''));
        if ($name === '') { $errors[] = ['sheet' => 'Clients', 'row' => $rowNum, 'message' => 'client_name is required.']; continue; }
        $lname = strtolower($name);

        if (isset($clientsSeenInFile[$lname])) {
            $errors[] = ['sheet' => 'Clients', 'row' => $rowNum, 'message' => "\"$name\" is listed more than once in this sheet (also row {$clientsSeenInFile[$lname]})."];
            continue;
        }
        $clientsSeenInFile[$lname] = $rowNum;

        if (isset($existingClients[$lname])) {
            // Already a real client - nothing to create, just informational.
            $warnings[] = ['sheet' => 'Clients', 'row' => $rowNum, 'message' => "\"$name\" already exists as a client - this row will be skipped, not duplicated."];
            $knownClientNames[$lname] = true;
            continue;
        }

        $onboardedDate = engagement_import_parse_date($row['onboarded_date'] ?? null);
        if ($onboardedDate === null) {
            $errors[] = ['sheet' => 'Clients', 'row' => $rowNum, 'message' => 'onboarded_date is required and must be a valid date (e.g. 2026-01-15).'];
            continue;
        }

        $status = strtolower(trim((string) ($row['status'] ?? 'active')));
        if (!in_array($status, ['active', 'inactive'], true)) {
            $errors[] = ['sheet' => 'Clients', 'row' => $rowNum, 'message' => "status must be Active or Inactive, got \"{$row['status']}\"."];
            continue;
        }

        $knownClientNames[$lname] = true;
        $clientsToCreate[] = [
            'client_name' => $name,
            'onboarded_date' => $onboardedDate,
            'status' => $status,
            'notes' => trim((string) ($row['notes'] ?? '')),
        ];
    }

    // ---------------------------------------------------------------
    // Engagements sheet
    // ---------------------------------------------------------------
    $engagementsToCreate = []; // key => fields
    $engagementKeysInFile = []; // key => row number first seen
    $resolvedEngagements = $existingEngagements; // key => ['engagement_id'=>id-or-null, 'audit_type_ids'=>[]]

    $statusMap = ['confirmed' => 'confirmed', 'pending' => 'pending', 'not confirmed' => 'not_confirmed', 'not_confirmed' => 'not_confirmed'];

    foreach ($engagementsRows as $row) {
        $rowNum = $row['__row'];
        $clientName = trim((string) ($row['client_name'] ?? ''));
        $year = trim((string) ($row['year'] ?? ''));
        // Optional - only needed when a client has more than one engagement
        // in the same year. See engagement_import_key() above.
        $engagementName = trim((string) ($row['engagement_name'] ?? ''));
        $engLabel = $engagementName !== '' ? "$clientName ($engagementName)" : $clientName;

        if ($clientName === '') { $errors[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => 'client_name is required.']; continue; }
        if ($year === '' || !ctype_digit($year)) { $errors[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => 'year is required and must be a 4-digit number.']; continue; }

        $lclient = strtolower($clientName);
        if (!isset($knownClientNames[$lclient])) {
            $errors[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => "\"$clientName\" isn't an existing client and has no row in the Clients sheet - add one there first."];
            continue;
        }

        $key = engagement_import_key($clientName, $year, $engagementName);
        if (isset($existingEngagements[$key])) {
            $warnings[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => "$engLabel already has a $year engagement - this row will be skipped, not duplicated."];
            continue;
        }
        if (isset($engagementKeysInFile[$key])) {
            $errors[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => "$engLabel / $year is listed more than once in this sheet (also row {$engagementKeysInFile[$key]})."];
            continue;
        }
        $engagementKeysInFile[$key] = $rowNum;

        $statusRaw = strtolower(trim((string) ($row['status'] ?? 'pending')));
        $status = $statusMap[$statusRaw] ?? null;
        if ($status === null) {
            $errors[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => "status must be Confirmed, Pending, or Not Confirmed, got \"{$row['status']}\"."];
            continue;
        }

        $budgetedHours = trim((string) ($row['budgeted_hours'] ?? ''));
        if ($budgetedHours === '' || !is_numeric($budgetedHours) || (float) $budgetedHours < 0) {
            $errors[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => 'budgeted_hours is required and must be a non-negative number.'];
            continue;
        }

        $manager = trim((string) ($row['manager'] ?? ''));
        if ($manager !== '' && !isset($usersByName[strtolower($manager)])) {
            $warnings[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => "Manager \"$manager\" doesn't match any active employee's name - it'll be stored as free text, same as it would if typed into Add Engagement."];
        }

        $auditTypeIds = [];
        $auditTypesRaw = trim((string) ($row['audit_types'] ?? ''));
        if ($auditTypesRaw !== '') {
            foreach (array_filter(array_map('trim', explode(',', $auditTypesRaw))) as $atName) {
                $match = $auditTypesByName[strtolower($atName)] ?? null;
                if (!$match) {
                    $errors[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => "Audit type \"$atName\" doesn't match any configured audit type (System Settings > Audit Types)."];
                    continue 2;
                }
                $auditTypeIds[] = $match['id'];
            }
        }

        $socType = trim((string) ($row['soc_type'] ?? ''));
        if ($socType !== '' && !in_array($socType, ['Type 1', 'Type 2', 'RA'], true)) {
            $errors[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => 'soc_type must be "Type 1", "Type 2", or "RA" (or left blank).'];
            continue;
        }

        $asOfDate = engagement_import_parse_date($row['as_of_date'] ?? null);
        $reviewStart = engagement_import_parse_date($row['review_period_start'] ?? null);
        $reviewEnd = engagement_import_parse_date($row['review_period_end'] ?? null);
        if ($reviewStart && $reviewEnd && $reviewStart > $reviewEnd) {
            $errors[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => 'review_period_start is after review_period_end.'];
            continue;
        }

        // PCI Assessment Type + Delivery Date - only relevant for PCI
        // engagements, but harmless to store regardless (same reasoning as
        // TSC below). Not every PCI engagement produces just a ROC (Report
        // on Compliance) - a smaller merchant/service provider might file
        // an AOC and a SAQ variant at once, so this accepts a comma-
        // separated list, same as audit_types above.
        $validPciTypes = ['ROC', 'AOC', 'SAQ A', 'SAQ A-EP', 'SAQ B', 'SAQ B-IP', 'SAQ C', 'SAQ C-VT', 'SAQ D (Merchant)', 'SAQ D (Service Provider)', 'P2PE'];
        $validPciTypesLower = array_map('strtolower', $validPciTypes);
        $pciAssessmentTypeRaw = trim((string) ($row['pci_assessment_type'] ?? ''));
        $pciAssessmentTypes = [];
        if ($pciAssessmentTypeRaw !== '') {
            foreach (array_filter(array_map('trim', explode(',', $pciAssessmentTypeRaw))) as $pciTypeName) {
                $idx = array_search(strtolower($pciTypeName), $validPciTypesLower, true);
                if ($idx === false) {
                    $errors[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => "pci_assessment_type \"$pciTypeName\" must be one of: " . implode(', ', $validPciTypes) . '.'];
                    continue 2;
                }
                $pciAssessmentTypes[] = $validPciTypes[$idx]; // normalize to canonical casing
            }
        }
        $pciAssessmentType = implode(', ', $pciAssessmentTypes);

        $pciDeliveryDateRaw = trim((string) ($row['pci_delivery_date'] ?? ''));
        $pciDeliveryDate = null;
        if ($pciDeliveryDateRaw !== '') {
            $pciDeliveryDate = engagement_import_parse_date($pciDeliveryDateRaw);
            if ($pciDeliveryDate === null) {
                $errors[] = ['sheet' => 'Engagements', 'row' => $rowNum, 'message' => "pci_delivery_date \"$pciDeliveryDateRaw\" isn't a valid date."];
                continue;
            }
        }

        $repeatRaw = strtolower(trim((string) ($row['repeat'] ?? '')));
        $repeatFlag = in_array($repeatRaw, ['yes', 'y', 'true', '1'], true) ? 1 : 0;

        $engagementsToCreate[$key] = [
            'client_name' => $clientName,
            'engagement_name' => $engagementName !== '' ? $engagementName : null,
            'year' => (int) $year,
            'status' => $status,
            'budgeted_hours' => (float) $budgetedHours,
            'manager' => $manager,
            'audit_type_ids' => $auditTypeIds,
            'tsc' => trim((string) ($row['tsc'] ?? '')),
            'soc_type' => $socType !== '' ? $socType : null,
            'as_of_date' => $asOfDate,
            'review_period_start' => $reviewStart,
            'review_period_end' => $reviewEnd,
            'pci_assessment_type' => $pciAssessmentType !== '' ? $pciAssessmentType : null,
            'pci_delivery_date' => $pciDeliveryDate,
            'location' => trim((string) ($row['location'] ?? '')),
            'poc' => trim((string) ($row['poc'] ?? '')),
            'scope' => trim((string) ($row['scope'] ?? '')),
            'repeat_flag' => $repeatFlag,
            'notes' => trim((string) ($row['notes'] ?? '')),
        ];
        $resolvedEngagements[$key] = ['engagement_id' => null, 'audit_type_ids' => $auditTypeIds];
    }

    // ---------------------------------------------------------------
    // Weekly Hours sheet - a grid: one row per person per engagement,
    // every other column is a week (its header is that week's Monday
    // date). Fixed columns are client_name/year/employee_full_name/
    // audit_type; anything else in the header row that parses as a date
    // is treated as a week column, so the sheet isn't locked to exactly
    // the weeks the template shipped with - a hand-added extra week
    // column still works the same way.
    // ---------------------------------------------------------------
    $fixedCols = ['client_name', 'engagement_name', 'year', 'employee_full_name', 'audit_type', '__row'];
    $weekColumns = []; // header text => parsed Monday date
    if (!empty($hoursRows)) {
        foreach (array_keys($hoursRows[0]) as $colName) {
            if (in_array($colName, $fixedCols, true)) continue;
            $parsed = engagement_import_parse_date($colName);
            if ($parsed === null) continue; // not a date-shaped header - ignore rather than hard error
            if ((int) date('N', strtotime($parsed)) !== 1) {
                $errors[] = ['sheet' => 'Weekly Hours', 'row' => null, 'message' => "Column header \"$colName\" isn't a Monday - week columns must be Mondays, same as how the app buckets weeks on Master Schedule."];
                continue;
            }
            $weekColumns[$colName] = $parsed;
        }
    }

    $parsedHoursRows = [];
    $rowSeenKeys = []; // "engagement|user|audit_type" => row number first seen, to catch duplicate person+engagement+audit_type rows

    foreach ($hoursRows as $row) {
        $rowNum = $row['__row'];
        $clientName = trim((string) ($row['client_name'] ?? ''));
        $year = trim((string) ($row['year'] ?? ''));
        $employeeName = trim((string) ($row['employee_full_name'] ?? ''));
        // Optional - only needed to pick out which of a client's several
        // engagements these hours belong to. Leave blank when the client
        // only has one engagement that year.
        $engagementName = trim((string) ($row['engagement_name'] ?? ''));
        $engLabel = $engagementName !== '' ? "$clientName ($engagementName)" : $clientName;

        if ($clientName === '' || $year === '') { $errors[] = ['sheet' => 'Weekly Hours', 'row' => $rowNum, 'message' => 'client_name and year are required.']; continue; }
        if ($employeeName === '') { $errors[] = ['sheet' => 'Weekly Hours', 'row' => $rowNum, 'message' => 'employee_full_name is required.']; continue; }

        $key = engagement_import_key($clientName, $year, $engagementName);
        if (!isset($resolvedEngagements[$key])) {
            $errors[] = ['sheet' => 'Weekly Hours', 'row' => $rowNum, 'message' => "No engagement found for $engLabel / $year - add it to the Engagements sheet, or check the client name/engagement_name/year matches an existing engagement exactly."];
            continue;
        }

        $matches = $usersByName[strtolower($employeeName)] ?? [];
        if (count($matches) === 0) {
            $errors[] = ['sheet' => 'Weekly Hours', 'row' => $rowNum, 'message' => "\"$employeeName\" doesn't match any active employee's full name exactly."];
            continue;
        }
        if (count($matches) > 1) {
            $errors[] = ['sheet' => 'Weekly Hours', 'row' => $rowNum, 'message' => "\"$employeeName\" matches more than one active employee - rename one of them, or use a more specific name, and try again."];
            continue;
        }
        $userId = $matches[0];

        $auditTypeId = null;
        $auditTypeRaw = trim((string) ($row['audit_type'] ?? ''));
        if ($auditTypeRaw !== '') {
            $match = $auditTypesByName[strtolower($auditTypeRaw)] ?? null;
            if (!$match) {
                $errors[] = ['sheet' => 'Weekly Hours', 'row' => $rowNum, 'message' => "Audit type \"$auditTypeRaw\" doesn't match any configured audit type."];
                continue;
            }
            $auditTypeId = $match['id'];
            $engAuditTypeIds = $resolvedEngagements[$key]['audit_type_ids'] ?? [];
            if (!empty($engAuditTypeIds) && !in_array($auditTypeId, $engAuditTypeIds, true)) {
                $warnings[] = ['sheet' => 'Weekly Hours', 'row' => $rowNum, 'message' => "\"$auditTypeRaw\" isn't one of $engLabel's selected audit types for this engagement - double check this row."];
            }
        }

        $rowKey = $key . '|' . $userId . '|' . ($auditTypeId ?? '0');
        if (isset($rowSeenKeys[$rowKey])) {
            $errors[] = ['sheet' => 'Weekly Hours', 'row' => $rowNum, 'message' => "$employeeName already has a row for $engLabel / $year with this audit_type (also row {$rowSeenKeys[$rowKey]}) - combine them into one row instead of two."];
            continue;
        }
        $rowSeenKeys[$rowKey] = $rowNum;

        $rowHadAnyHours = false;
        foreach ($weekColumns as $colName => $weekStart) {
            $cell = trim((string) ($row[$colName] ?? ''));
            if ($cell === '') continue; // blank week = no hours that week, not an error

            if (!is_numeric($cell) || (float) $cell <= 0) {
                $errors[] = ['sheet' => 'Weekly Hours', 'row' => $rowNum, 'message' => "Week of $weekStart: \"$cell\" isn't a positive number."];
                continue;
            }
            $hours = (float) $cell;
            if ($hours > 60) {
                $warnings[] = ['sheet' => 'Weekly Hours', 'row' => $rowNum, 'message' => "$hours hours for $employeeName in the week of $weekStart looks unusually high - double check this cell."];
            }

            $rowHadAnyHours = true;
            $parsedHoursRows[] = [
                'engagement_key' => $key,
                'user_id' => $userId,
                'week_start' => $weekStart,
                'hours' => $hours,
                'audit_type_id' => $auditTypeId,
                'first_row' => $rowNum,
            ];
        }
        if (!$rowHadAnyHours) {
            $warnings[] = ['sheet' => 'Weekly Hours', 'row' => $rowNum, 'message' => "$employeeName's row for $engLabel / $year has no hours filled in on any week - this row won't create anything."];
        }
    }

    // Flag rows where the employee already has hours logged for that exact
    // engagement/week, so the import doesn't silently double someone's
    // hours if it's run more than once or after manual entries already
    // exist - it still proceeds (this is a warning, not a blocker), since
    // legitimately adding a second chunk of hours to the same week is
    // normal (e.g. splitting across audit types).
    if (!empty($parsedHoursRows)) {
        $existingEntryKeys = [];
        $engagementIdsToCheck = [];
        foreach ($parsedHoursRows as $r) {
            $eid = $resolvedEngagements[$r['engagement_key']]['engagement_id'] ?? null;
            if ($eid) $engagementIdsToCheck[$eid] = true;
        }
        if (!empty($engagementIdsToCheck)) {
            $idList = implode(',', array_map('intval', array_keys($engagementIdsToCheck)));
            $res = $conn->query("SELECT user_id, engagement_id, week_start, SUM(assigned_hours) AS total FROM entries WHERE engagement_id IN ($idList) GROUP BY user_id, engagement_id, week_start");
            while ($row = $res->fetch_assoc()) {
                $existingEntryKeys[$row['user_id'] . '|' . $row['engagement_id'] . '|' . $row['week_start']] = (float) $row['total'];
            }
        }
        foreach ($parsedHoursRows as $r) {
            $eid = $resolvedEngagements[$r['engagement_key']]['engagement_id'] ?? null;
            if (!$eid) continue; // brand-new engagement, can't already have hours
            $existingTotal = $existingEntryKeys[$r['user_id'] . '|' . $eid . '|' . $r['week_start']] ?? null;
            if ($existingTotal !== null) {
                $warnings[] = ['sheet' => 'Weekly Hours', 'row' => $r['first_row'], 'message' => "Already has {$existingTotal}h logged for this engagement/week - this import adds {$r['hours']}h on top, it doesn't replace it."];
            }
        }
    }

    $newClientCount = count($clientsToCreate);
    $existingClientRefCount = count($clientsSeenInFile) - $newClientCount;
    $newEngagementCount = count($engagementsToCreate);
    $existingEngagementRefCount = count($engagementKeysInFile) - $newEngagementCount;
    $totalHours = array_sum(array_column($parsedHoursRows, 'hours'));

    return [
        'ok' => empty($errors),
        'errors' => $errors,
        'warnings' => $warnings,
        'summary' => [
            'new_clients' => $newClientCount,
            'existing_clients' => max(0, $existingClientRefCount),
            'new_engagements' => $newEngagementCount,
            'existing_engagements' => max(0, $existingEngagementRefCount),
            'hours_rows' => count($parsedHoursRows),
            'total_hours' => $totalHours,
        ],
        'clients_to_create' => $clientsToCreate,
        'engagements_to_create' => $engagementsToCreate,
        'hours_rows' => $parsedHoursRows,
    ];
}
