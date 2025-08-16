<?php
require_once '../includes/db.php'; // Adjust path as needed

// Query all global PTO entries
$sql = "
    SELECT 
        timeoff_id, 
        DATE_FORMAT(week_start, '%m/%d/%Y') AS week_start, 
        assigned_hours, 
        timeoff_note
    FROM time_off
    WHERE is_global_timeoff = 1
    ORDER BY week_start DESC
";
$result = $conn->query($sql);

// Group by note
$groups = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $groups[$row['timeoff_note']][] = $row;
    }
}

if (!empty($groups)):
    $i = 0;
    foreach ($groups as $note => $entries):
        $i++;
        $total_hours = array_sum(array_column($entries, 'assigned_hours'));
?>
<div class="card shadow-sm global-pto-card" data-note="<?= htmlspecialchars($note) ?>" style="border-radius:6px;border:1px solid #e0e0e0;">
    <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;height:85px;" data-bs-toggle="collapse" data-bs-target="#collapse<?= $i ?>">
        <!-- Left -->
        <div>
            <p class="mb-1 fs-6 fw-semibold text-capitalize"><?= htmlspecialchars($note ?: 'No Note') ?></p>
            <small class="text-muted group-weeks">Weeks: <?= implode(', ', array_column($entries, 'week_start')) ?></small>
        </div>
        <!-- Right -->
        <div class="d-flex align-items-center gap-3">
            <span class="fw-semibold group-hours"><?= $total_hours ?> hrs</span>
            <i class="bi bi-chevron-down text-muted"></i>
        </div>
    </div>

    <!-- Accordion Body -->
    <div id="collapse<?= $i ?>" class="collapse" data-bs-parent="#ptoAccordion">
        <div class="card-body d-flex flex-column gap-2">
            <?php foreach ($entries as $entry): ?>
            <form action="update_global_pto.php" method="POST" class="updatePTOForm d-flex flex-row align-items-center gap-2 border p-2 rounded">
                <input type="hidden" name="timeoff_id" value="<?= $entry['timeoff_id'] ?>">

                <div>
                    <label class="form-label small mb-0">Week</label>
                    <input type="date" name="week_start" value="<?= date('Y-m-d', strtotime($entry['week_start'])) ?>" class="form-control form-control-sm" required>
                </div>
                <div>
                    <label class="form-label small mb-0">Hours</label>
                    <input type="number" name="assigned_hours" value="<?= $entry['assigned_hours'] ?>" class="form-control form-control-sm" min="0" required>
                </div>
                <div class="flex-fill">
                    <label class="form-label small mb-0">Note</label>
                    <input type="text" name="timeoff_note" value="<?= htmlspecialchars($note) ?>" class="form-control form-control-sm">
                </div>

                <div class="d-flex gap-2 align-items-end">
                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    <a href="delete_global_pto.php?id=<?= $entry['timeoff_id'] ?>" class="btn btn-sm btn-outline-danger">Delete</a>
                </div>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
    endforeach;
else:
?>
<p class="text-muted text-center">No global PTO entries found.</p>
<?php endif; ?>
