<?php
require_once '../includes/db.php';
require_once __DIR__ . '/../includes/session_init.php';
require_once __DIR__ . '/../includes/permissions.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (isset($_GET['id'])) {
    $engagementId = (int)$_GET['id'];

    // Permission holders can view any engagement; everyone else can only view
    // engagements they're actually staffed on (e.g. via the My Schedule page),
    // not an arbitrary engagement_id.
    if (!user_has_permission($conn, 'view_clients_engagements')) {
        $accessStmt = $conn->prepare("SELECT 1 FROM entries WHERE engagement_id = ? AND user_id = ? LIMIT 1");
        $accessStmt->bind_param('ii', $engagementId, $_SESSION['user_id']);
        $accessStmt->execute();
        $hasAccess = (bool) $accessStmt->get_result()->fetch_row();
        $accessStmt->close();

        if (!$hasAccess) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    $engagementQuery = "SELECT client_name, status, budgeted_hours, manager, notes FROM engagements WHERE engagement_id = ?";
    $stmt = $conn->prepare($engagementQuery);
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $engagementResult = $stmt->get_result();
    $engagement = $engagementResult->fetch_assoc();

    // Assigned employees + their hours + role, for the View Engagement modal.
    // Grouped by (user, audit_type) rather than just user - someone can be
    // split across more than one audit type on the same engagement, and
    // collapsing those together would blend their hours into one misleading
    // total. Ordered by role seniority (manager > senior > staff > intern),
    // not hours.
    $employeeQuery = "SELECT u.full_name, u.role, a.audit_type_id, at.name AS audit_type_name, at.color AS audit_type_color, SUM(a.assigned_hours) AS total_hours
                      FROM entries a
                      JOIN users u ON a.user_id = u.user_id
                      LEFT JOIN audit_types at ON a.audit_type_id = at.audit_type_id
                      WHERE a.engagement_id = ?
                      GROUP BY a.user_id, u.full_name, u.role, a.audit_type_id, at.name, at.color
                      ORDER BY CASE u.role
                          WHEN 'manager' THEN 1
                          WHEN 'senior' THEN 2
                          WHEN 'staff' THEN 3
                          WHEN 'intern' THEN 4
                          ELSE 5
                      END, u.full_name ASC, at.name ASC";
    $stmt = $conn->prepare($employeeQuery);
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $employeeResult = $stmt->get_result();
    $assignedEmployees = [];
    while ($employee = $employeeResult->fetch_assoc()) {
        $assignedEmployees[] = [
            'name' => $employee['full_name'] ?? '',
            'role' => $employee['role'] ?? '',
            'hours' => (float)$employee['total_hours'],
            'audit_type_name' => $employee['audit_type_name'] ?? null,
            'audit_type_color' => $employee['audit_type_color'] ?? null,
        ];
    }

    // Total assigned hours
    $totalHoursQuery = "SELECT SUM(COALESCE(assigned_hours, 0)) AS total_hours FROM entries WHERE engagement_id = ?";
    $stmt = $conn->prepare($totalHoursQuery);
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $totalHoursResult = $stmt->get_result();
    $totalHours = (float)($totalHoursResult->fetch_assoc()['total_hours'] ?? 0);

    // ---------------------------------------------------------------
    // Audit tracking data (timeline/milestones/DOL/independence), added
    // for the Engagement Tracker migration. Gated server-side by the same
    // view_audit_timeline / view_dol permissions from Phase 1 — the
    // frontend never receives a section it isn't allowed to see, rather
    // than fetching and hiding it client-side.
    // ---------------------------------------------------------------
    $auditData = [];

    if (user_has_permission($conn, 'view_audit_timeline')) {
        $stmt = $conn->prepare("SELECT * FROM audit_engagement_timeline WHERE engagement_id = ?");
        $stmt->bind_param('i', $engagementId);
        $stmt->execute();
        $tl = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($tl) {
            $auditData['timeline'] = [
                ['label' => 'Internal Planning Call', 'date' => $tl['internal_planning_call_date'], 'completed' => $tl['internal_planning_call_completed_at']],
                ['label' => 'Planning Memo', 'date' => $tl['planning_memo_date'], 'completed' => $tl['planning_memo_completed_at']],
                ['label' => 'IRL Due', 'date' => $tl['irl_due_date'], 'completed' => $tl['irl_completed_at']],
                ['label' => 'Client Planning Call', 'date' => $tl['client_planning_call_date'], 'completed' => $tl['client_planning_call_completed_at']],
                ['label' => 'Fieldwork - Client Calls', 'date' => $tl['fieldwork_client_calls_end_date'], 'start_date' => $tl['fieldwork_client_calls_start_date'], 'completed' => $tl['fieldwork_client_calls_completed_at']],
                ['label' => 'Fieldwork - Documentation', 'date' => $tl['fieldwork_documentation_end_date'], 'start_date' => $tl['fieldwork_documentation_start_date'], 'completed' => $tl['fieldwork_documentation_completed_at']],
                ['label' => 'Leadsheet Due', 'date' => $tl['leadsheet_date'], 'completed' => $tl['leadsheet_completed_at']],
                ['label' => 'Conclusion Memo', 'date' => $tl['conclusion_memo_date'], 'completed' => $tl['conclusion_memo_completed_at']],
                ['label' => 'Draft Report Due', 'date' => $tl['draft_report_due_date'], 'completed' => $tl['draft_report_completed_at']],
                ['label' => 'Final Report', 'date' => $tl['final_report_date'], 'completed' => $tl['final_report_completed_at']],
                ['label' => 'Archive', 'date' => $tl['archive_date'], 'completed' => $tl['archive_completed_at']],
            ];

            if ($tl['weekly_status_call_day'] !== null) {
                $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $auditData['weekly_status_call'] = [
                    'day' => $dayNames[(int) $tl['weekly_status_call_day']] ?? null,
                    'group_name' => $tl['weekly_status_call_group_name'],
                ];
            }
        }

        $stmt = $conn->prepare("SELECT milestone_id, milestone_type, due_date, is_completed FROM audit_engagement_milestones WHERE engagement_id = ? ORDER BY due_date IS NULL, due_date ASC");
        $stmt->bind_param('i', $engagementId);
        $stmt->execute();
        $auditData['milestones'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    if (user_has_permission($conn, 'view_dol')) {
        $stmt = $conn->prepare("
            SELECT ada.criterion, at.name AS audit_type_name, at.color AS audit_type_color, u.user_id, u.full_name
            FROM audit_dol_assignments ada
            JOIN users u ON u.user_id = ada.user_id
            LEFT JOIN audit_types at ON at.audit_type_id = ada.audit_type_id
            WHERE ada.engagement_id = ?
            ORDER BY at.name, u.full_name, ada.criterion
        ");
        $stmt->bind_param('i', $engagementId);
        $stmt->execute();
        $auditData['dol'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    // Independence — no dedicated permission was carved out for this in
    // Phase 1, so it's visible to anyone who already passed the top-level
    // access check for this engagement (same as Engagement Tracker's own
    // Team card, which had no separate gate either).
    $stmt = $conn->prepare("
        SELECT ati.user_id, u.full_name, ati.independent
        FROM audit_team_independence ati
        JOIN users u ON u.user_id = ati.user_id
        WHERE ati.engagement_id = ?
        ORDER BY u.full_name
    ");
    $stmt->bind_param('i', $engagementId);
    $stmt->execute();
    $auditData['independence'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'client_name' => $engagement['client_name'] ?? '',
        'status' => $engagement['status'] ?? '',
        'total_hours' => $totalHours,
        'budgeted_hours' => (float)($engagement['budgeted_hours'] ?? 0),
        'manager' => $engagement['manager'] ?? '',
        'assigned_employees' => $assignedEmployees,
        'notes' => $engagement['notes'] ?? '',
        'audit' => $auditData,
    ]);
}
