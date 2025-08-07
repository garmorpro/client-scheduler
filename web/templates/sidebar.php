<!-- sidebar.php -->
<div class="d-flex flex-column justify-content-between bg-light border-end" style="width: 250px; height: 100vh; padding: 1.5rem;">

  <!-- Branding -->
  <div>
    <div class="d-flex align-items-center mb-5">
      <div class="bg-dark text-white rounded p-2 me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
        <i class="bi bi-calendar2-week"></i> <!-- Bootstrap icon -->
      </div>
      <div>
        <h5 class="mb-0 fw-bold">AARC-360</h5>
         <!-- <img src="../assets/images/aarc-360-logo-1.webp" alt="" class="" style="width: 50%;"><br> -->
        <small class="text-muted">Schedule Manager</small>
      </div>
    </div>

    <!-- Nav Links -->
    <ul class="nav flex-column">
      <li class="nav-item mb-2">
        <a href="dashboard.php" class="nav-link d-flex align-items-center px-0 text-dark">
          <i class="bi bi-layout-wtf me-2"></i>
          Dashboard
        </a>
      </li>
      <li class="nav-item mb-2">
        <a href="master-schedule.php" class="nav-link d-flex align-items-center px-0 text-dark">
          <i class="bi bi-calendar-range me-2"></i>
          Master Schedule
        </a>
      </li>
      <li class="nav-item">
        <a href="my-schedule.php" class="nav-link d-flex align-items-center px-0 text-dark">
          <i class="bi bi-person me-2"></i>
          My Schedule
        </a>
      </li>
      <div class="mt-4"></div>
      <hr>
      <span style="font-size: 20px; font-weight: 500;">Admin Settings</span>
      <hr>
      <li class="nav-item">
        <a href="#" class="nav-link d-flex align-items-center px-0 text-dark" data-bs-toggle="modal" data-bs-target="#addEngagementModal">
          <i class="bi bi-building-add me-2"></i>
          Add Engagement
        </a>
      </li>

    </ul>
  </div>

  <!-- Bottom User Info -->
  <div class="d-flex align-items-center">
    <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px;">
        <?php
        $firstInitial = isset($_SESSION['first_name'][0]) ? $_SESSION['first_name'][0] : '';
        $lastInitial = isset($_SESSION['last_name'][0]) ? $_SESSION['last_name'][0] : '';
        echo strtoupper($firstInitial . $lastInitial);
        ?>
    </div>
    <div>
      <div class="fw-semibold"><?php echo $_SESSION['first_name']; ?> <?php echo $_SESSION['last_name'] ?></div>
      <small class="text-muted text-capitalize"><?php echo $_SESSION['user_role']; ?></small>
    </div>
    <a href="logout.php" class="ms-auto text-decoration-none text-muted">
      <i class="bi bi-box-arrow-right"></i>
    </a>
  </div>
</div>


<!-- Add Engagement Modal -->
<div class="modal fade" id="addEngagementModal" tabindex="-1" aria-labelledby="addEngagementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content rounded-3 shadow">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="addEngagementModalLabel">Create New Project</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="addEngagementForm" action="create-engagement.php" method="POST">
        <div class="modal-body">
          <p class="text-muted mb-4">Add a new client engagement to the schedule</p>

          <div class="mb-3">
            <label for="projectName" class="form-label">Project Name</label>
            <input type="text" class="form-control" id="projectName" name="project_name" required>
          </div>

          <div class="mb-3">
            <label for="client" class="form-label">Client</label>
            <input type="text" class="form-control" id="client" name="client" required>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="type" class="form-label">Type</label>
              <select class="form-select" id="type" name="type" required>
                <option value="">Select type</option>
                <option value="Audit">Audit</option>
                <option value="Consulting">Consulting</option>
                <option value="Review">Review</option>
              </select>
            </div>

            <div class="col-md-6 mb-3">
              <label for="status" class="form-label">Status</label>
              <select class="form-select" id="status" name="status" required>
                <option value="">Select status</option>
                <option value="Planning">Planning</option>
                <option value="In Progress">In Progress</option>
                <option value="Complete">Complete</option>
              </select>
            </div>
          </div>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="startDate" class="form-label">Start Date</label>
              <input type="date" class="form-control" id="startDate" name="start_date" required>
            </div>

            <div class="col-md-6 mb-3">
              <label for="endDate" class="form-label">End Date</label>
              <input type="date" class="form-control" id="endDate" name="end_date" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="estimatedHours" class="form-label">Estimated Hours</label>
            <input type="number" class="form-control" id="estimatedHours" name="estimated_hours" min="0" required>
          </div>

          <div class="mb-3">
            <label for="description" class="form-label">Description (Optional)</label>
            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief project description..."></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-dark">Create Project</button>
        </div>
      </form>
    </div>
  </div>
</div>
