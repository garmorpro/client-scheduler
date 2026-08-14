<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/import_engagements_shared.php';

header('Content-Type: application/json');

// Covers anything before the transaction's own try/catch below (e.g. a
// missing dependency) - same reasoning as the other two import endpoints.
set_exception_handler(function (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Something went wrong: ' . $e->getMessage()]);
});

if (!isset($_SESSION['user_id']) || !user_has_permission($conn, 'manage_clients_engagements')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!csrf_valid()) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit();
}

if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded or upload error']);
    exit();
}

// Re-parse and re-validate from scratch rather than trusting whatever the
// browser showed from the earlier /validate call - state may have changed
// (someone else added a same-named client, etc) in between the two
// requests, and this is the request that's actually about to write.
$result = parse_and_validate_engagement_import($conn, $_FILES['import_file']['tmp_name']);

if (!$result['ok']) {
    echo json_encode([
        'success' => false,
        'error' => 'This file no longer validates cleanly - nothing was imported.',
        'errors' => $result['errors'],
        'warnings' => $result['warnings'],
        'summary' => $result['summary'],
    ]);
    exit();
}

$conn->begin_transaction();

try {
    $insertedClients = 0;
    foreach ($result['clients_to_create'] as $c) {
        $stmt = $conn->prepare("INSERT INTO clients (client_name, onboarded_date, status, notes) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $c['client_name'], $c['onboarded_date'], $c['status'], $c['notes']);
        if (!$stmt->execute()) throw new \Exception('Failed to create client "' . $c['client_name'] . '": ' . $stmt->error);
        $stmt->close();
        $insertedClients++;
    }

    // client_name -> client_id, covering both pre-existing clients and the
    // ones just inserted above in one query.
    $clientIdByName = [];
    $res = $conn->query("SELECT client_id, client_name FROM clients");
    while ($row = $res->fetch_assoc()) $clientIdByName[strtolower(trim($row['client_name']))] = (int) $row['client_id'];

    $engagementIdByKey = [];
    $insertedEngagements = 0;
    foreach ($result['engagements_to_create'] as $key => $e) {
        $clientId = $clientIdByName[strtolower(trim($e['client_name']))] ?? null;
        if (!$clientId) throw new \Exception('Could not resolve client_id for "' . $e['client_name'] . '" - this should not happen.');

        $stmt = $conn->prepare("INSERT INTO engagements (client_id, client_name, engagement_name, budgeted_hours, status, year, manager, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('isssssss', $clientId, $e['client_name'], $e['engagement_name'], $e['budgeted_hours'], $e['status'], $e['year'], $e['manager'], $e['notes']);
        if (!$stmt->execute()) throw new \Exception('Failed to create engagement for "' . $e['client_name'] . '" (' . $e['year'] . '): ' . $stmt->error);
        $engagementId = $stmt->insert_id;
        $stmt->close();
        $engagementIdByKey[$key] = $engagementId;
        $insertedEngagements++;

        if (!empty($e['audit_type_ids'])) {
            $atStmt = $conn->prepare("INSERT INTO engagement_audit_types (engagement_id, audit_type_id) VALUES (?, ?)");
            foreach (array_unique($e['audit_type_ids']) as $atId) {
                $atStmt->bind_param('ii', $engagementId, $atId);
                if (!$atStmt->execute()) throw new \Exception('Failed to link audit type for "' . $e['client_name'] . '": ' . $atStmt->error);
            }
            $atStmt->close();
        }

        $tscValue = $e['tsc'] !== '' ? $e['tsc'] : null;
        $locationValue = $e['location'] !== '' ? $e['location'] : null;
        $pocValue = $e['poc'] !== '' ? $e['poc'] : null;
        $scopeValue = $e['scope'] !== '' ? $e['scope'] : null;

        $detailsStmt = $conn->prepare("
            INSERT INTO audit_engagement_details
                (engagement_id, location, poc, tsc, scope, repeat_flag, soc_type, as_of_date, review_period_start, review_period_end, roc_delivery_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $detailsStmt->bind_param(
            'issssisssss',
            $engagementId, $locationValue, $pocValue, $tscValue, $scopeValue, $e['repeat_flag'],
            $e['soc_type'], $e['as_of_date'], $e['review_period_start'], $e['review_period_end'], $e['roc_delivery_date']
        );
        if (!$detailsStmt->execute()) throw new \Exception('Failed to save engagement details for "' . $e['client_name'] . '": ' . $detailsStmt->error);
        $detailsStmt->close();

        $timelineStmt = $conn->prepare("INSERT IGNORE INTO audit_engagement_timeline (engagement_id) VALUES (?)");
        $timelineStmt->bind_param('i', $engagementId);
        $timelineStmt->execute();
        $timelineStmt->close();
    }

    // Fill in engagement_ids for hours rows that referenced an engagement
    // which already existed (not created above) - these keys weren't in
    // engagements_to_create at all, so look them up directly.
    $missingKeys = [];
    foreach ($result['hours_rows'] as $h) {
        if (!isset($engagementIdByKey[$h['engagement_key']])) $missingKeys[$h['engagement_key']] = true;
    }
    if (!empty($missingKeys)) {
        $res = $conn->query("SELECT engagement_id, client_name, engagement_name, year FROM engagements");
        while ($row = $res->fetch_assoc()) {
            $key = engagement_import_key($row['client_name'], $row['year'], $row['engagement_name'] ?? '');
            if (isset($missingKeys[$key])) $engagementIdByKey[$key] = (int) $row['engagement_id'];
        }
    }

    $insertedHours = 0;
    $totalHours = 0.0;
    $entryStmt = $conn->prepare("INSERT INTO entries (user_id, week_start, engagement_id, audit_type_id, assigned_hours) VALUES (?, ?, ?, ?, ?)");
    foreach ($result['hours_rows'] as $h) {
        $engagementId = $engagementIdByKey[$h['engagement_key']] ?? null;
        if (!$engagementId) throw new \Exception('Could not resolve engagement_id for a Weekly Hours row - this should not happen.');
        $entryStmt->bind_param('isiid', $h['user_id'], $h['week_start'], $engagementId, $h['audit_type_id'], $h['hours']);
        if (!$entryStmt->execute()) throw new \Exception('Failed to insert an hours row: ' . $entryStmt->error);
        $insertedHours++;
        $totalHours += $h['hours'];
    }
    $entryStmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'summary' => [
            'clients_created' => $insertedClients,
            'engagements_created' => $insertedEngagements,
            'hours_rows_inserted' => $insertedHours,
            'total_hours' => $totalHours,
        ],
        'warnings' => $result['warnings'],
    ]);
} catch (\Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
