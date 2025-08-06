<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
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
    <!-- Dashboard content goes here -->
    
    <h3 class="mb-0">Master Schedule</h3>
<p class="text-muted mb-4">Complete overview of all client engagements and team assignments</p>

<!-- Toolbar: Date range and Today button -->
<div class="d-flex align-items-center justify-content-between mb-4">
  <div class="d-flex align-items-center gap-3">
    <input type="date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
    <span class="fw-semibold">to</span>
    <input type="date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
    <button class="btn btn-outline-primary">Today</button>
  </div>

  <!-- Placeholder for filter/search tools (optional) -->
  <div>
    <!-- Future filters/search can go here -->
  </div>
</div>

<!-- Schedule Grid Placeholder -->
<div class="table-responsive">
  <table class="table table-bordered align-middle text-center">
    <thead class="table-light">
      <tr>
        <th>Employee</th>
        <?php
          // Generate headers for 14 days starting from today (e.g., Mon 8/5)
          for ($i = 0; $i < 14; $i++) {
              $date = strtotime("+$i days");
              echo '<th>' . date('D', $date) . '<br>' . date('n/j', $date) . '</th>';
          }
        ?>
      </tr>
    </thead>
    <tbody>
      <?php
        // Example static rows; you can pull this from your DB
        $employees = ['John Doe', 'Jane Smith', 'Alex Johnson'];

        foreach ($employees as $employee) {
            echo '<tr>';
            echo '<td class="text-start fw-semibold">' . $employee . '</td>';
            for ($i = 0; $i < 14; $i++) {
                echo '<td>-</td>'; // Placeholder for actual client assignment
            }
            echo '</tr>';
        }
      ?>
    </tbody>
  </table>
</div>
    

  </div>

</body>
</html>
