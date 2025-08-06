<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle start and end date from GET params, or set defaults
$startDate = isset($_GET['start']) ? date('Y-m-d', strtotime($_GET['start'])) : date('Y-m-d', strtotime('monday this week'));
$endDate = isset($_GET['end']) ? date('Y-m-d', strtotime($_GET['end'])) : date('Y-m-d', strtotime('+5 weeks', strtotime($startDate)));

// Get all Mondays between start and end
$mondays = [];
$current = strtotime($startDate);
while ($current <= strtotime($endDate)) {
    if (date('N', $current) == 1) { // 1 = Monday
        $mondays[] = $current;
    }
    $current = strtotime('+1 week', $current);
}

// Example employees (replace this with DB call if needed)
$employees = ['John Doe', 'Jane Smith', 'Alex Johnson'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Master Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="d-flex">

  <?php include_once '../templates/sidebar.php'; ?>

  <div class="flex-grow-1 p-4">
    <h3 class="mb-0">Master Schedule</h3>
    <p class="text-muted mb-4">Complete overview of all client engagements and team assignments</p>

    <!-- Date Selector Toolbar -->
    <form method="get" class="d-flex align-items-center justify-content-between mb-4">
      <div class="d-flex align-items-center gap-3">
        <input type="date" name="start" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
        <span class="fw-semibold">to</span>
        <input type="date" name="end" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
        <button type="submit" class="btn btn-primary">Apply</button>
        <a href="?start=<?php echo date('Y-m-d', strtotime('monday this week')); ?>&end=<?php echo date('Y-m-d', strtotime('+5 weeks', strtotime('monday this week'))); ?>" class="btn btn-outline-secondary">Today</a>
      </div>
    </form>

    <!-- Schedule Table -->
    <div class="table-responsive">
      <table class="table table-bordered align-middle text-center">
        <thead class="table-light">
          <tr>
            <th class="text-start">Employee</th>
            <?php foreach ($mondays as $monday): ?>
              <th>
                <?php echo date('M j', $monday); ?><br>
                <small class="text-muted">Week of <?php echo date('n/j', $monday); ?></small>
              </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($employees as $employee): ?>
            <tr>
              <td class="text-start fw-semibold"><?php echo htmlspecialchars($employee); ?></td>
              <?php foreach ($mondays as $monday): ?>
                <td>-</td> <!-- Placeholder: replace with actual assignments later -->
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</body>
</html>
