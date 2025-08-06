<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get today's date
$today = date('Y-m-d');

// Adjust start date: if the selected date is not a Monday, shift it to the previous Monday
$startDate = isset($_GET['start']) ? date('Y-m-d', strtotime('previous monday', strtotime($_GET['start']))) : date('Y-m-d', strtotime('monday -2 weeks'));

// Automatically calculate the end date to be 5 weeks after the selected start date, making it a 6-week view
$endDate = date('Y-m-d', strtotime('+5 weeks', strtotime($startDate)));

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
    <style>
      .form-select, .form-control {
        background-color: #f9fafb;
        border-radius: 8px;
      }
      .highlight-today {
        background-color: lightblue !important;
      }
    </style>
    <script>
      // JavaScript function to auto-submit form on date change
      function autoSubmitDateFilter() {
        document.getElementById("filterForm").submit();
      }
    </script>
</head>
<body class="d-flex">

  <?php include_once '../templates/sidebar.php'; ?>

  <div class="flex-grow-1 p-4">
    <h3 class="mb-0">Master Schedule</h3>
    <p class="text-muted mb-4">Complete overview of all client engagements and team assignments</p>

    <!-- Filter Section -->
    <div class="bg-white border rounded p-4 mb-4">
      <div class="mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-funnel text-muted"></i>
        Filters
      </div>

      <form id="filterForm" method="get" class="row g-3">
        <!-- Search -->
        <div class="col-md-7">
          <input 
            type="text" 
            name="search" 
            class="form-control" 
            placeholder="Search projects or clients..."
            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
          >
        </div>

        <!-- Status Dropdown -->
        <div class="col-md-2">
          <select name="status" class="form-select">
            <option value="">All Statuses</option>
            <option value="active" <?php echo (isset($_GET['status']) && $_GET['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
            <option value="completed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
            <option value="on_hold" <?php echo (isset($_GET['status']) && $_GET['status'] == 'on_hold') ? 'selected' : ''; ?>>On Hold</option>
          </select>
        </div>

        <!-- Date Selector Toolbar -->
        <div class="col-md-3 d-flex align-items-center gap-3">
          <input type="date" name="start" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>" onchange="autoSubmitDateFilter()">
          <a href="?start=<?php echo date('Y-m-d', strtotime('monday -3 weeks')); ?>" class="btn btn-outline-secondary">Today</a>
        </div>
      </form>
    </div>

    <!-- Schedule Table -->
    <div class="table-responsive">
      <table class="table table-bordered align-middle text-center">
        <thead class="table-light">
          <tr>
            <th class="text-start">Employee</th>
            <?php 
              // Highlight the week of today's date (using 'week of' calculation)
              foreach ($mondays as $monday):
                $weekStart = date('Y-m-d', $monday);
                $highlightClass = (date('Y-m-d', strtotime($today)) >= $weekStart && date('Y-m-d', strtotime($today)) < date('Y-m-d', strtotime('+7 days', strtotime($weekStart)))) ? 'highlight-today' : '';
            ?>
              <th class="<?php echo $highlightClass; ?>">
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
