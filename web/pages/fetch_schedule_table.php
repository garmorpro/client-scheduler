<?php
require_once '../includes/db.php';
session_start();

// Only allow logged-in users
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(403);
//     echo 'Forbidden';
//     exit();
// }

// ------------------------------
// Fetch all entries
// ------------------------------
$schedule = [];

// Replace with your mysqli query
$entryQuery = "
    SELECT e.entry_id, e.user_id, e.week_start, u.first_name, u.last_name
    FROM entries e
    JOIN users u ON e.user_id = u.user_id
    ORDER BY u.user_id, e.week_start
";
$result = $conn->query($entryQuery);
if ($result) {
    while ($entry = $result->fetch_assoc()) {
        $schedule[$entry['user_id']][$entry['week_start']][] = $entry;
    }
}

// ------------------------------
// Fetch all users
// ------------------------------
$users = [];
$userResult = $conn->query("SELECT user_id, first_name, last_name FROM users ORDER BY user_id");
if ($userResult) {
    while ($user = $userResult->fetch_assoc()) {
        $users[] = $user;
    }
}

// ------------------------------
// Define weeks to display (next 4 weeks)
// ------------------------------
$weeks = [];
$startDate = strtotime('monday this week');
for ($i = 0; $i < 4; $i++) {
    $weeks[] = date('Y-m-d', strtotime("+$i week", $startDate));
}

// ------------------------------
// Output table HTML
// ------------------------------
?>
<div class="table-responsive">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>User</th>
                <?php foreach ($weeks as $weekStart): ?>
                    <th><?= date('M d, Y', strtotime($weekStart)) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                    <?php foreach ($weeks as $weekStart): ?>
                        <td class="addable" data-user-id="<?= $user['user_id'] ?>" data-week-start="<?= $weekStart ?>">
                            <?php
                            if (isset($schedule[$user['user_id']][$weekStart])) {
                                foreach ($schedule[$user['user_id']][$weekStart] as $entry) {
                                    echo '<span class="draggable-badge" id="badge-entry-' . $entry['entry_id'] . '" draggable="true" data-entry-id="' . $entry['entry_id'] . '">';
                                    echo 'Entry #' . $entry['entry_id'];
                                    echo '</span>';
                                }
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
