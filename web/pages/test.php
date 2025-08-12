<!-- Include Bootstrap CSS & JS (make sure these are in your page!) -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
  .custom-dropdown {
    position: relative;
    font-size: 14px;
  }

  .dropdown-btn {
    border: none !important;
    background-color: rgb(243, 243, 245) !important;
    font-size: 14px !important;
    width: 100%;
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    user-select: none;
    border-radius: 4px;
  }

  .dropdown-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    margin-top: 5px;
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ccc;
    background: white;
    z-index: 2000; /* HIGHER than modal backdrop */
    display: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    border-radius: 10px;
  }

  .dropdown-item {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
  }

  .dropdown-item:last-child {
    border-bottom: none;
  }

  .dropdown-item:hover,
  .dropdown-item:focus {
    background: #f0f0f0;
    outline: none;
  }

  .dropdown-item div small.text-muted {
    font-size: 12px;
    font-weight: 400;
    color: #6c757d;
  }

  #selectedClient {
    font-weight: 500;
  }

  /* Important: allow dropdown to overflow modal-body */
  .modal-body {
    overflow: visible !important;
  }
</style>

<!-- Modal -->
<div
  class="modal fade"
  id="assignmentModal"
  tabindex="-1"
  aria-labelledby="assignmentModalLabel"
  aria-hidden="true"
>
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="assignmentForm" action="add_assignment.php" method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="assignmentModalLabel">
            <i class="bi bi-calendar-range me-2"></i>New Entry<br />
            <span
              class="text-muted"
              style="font-size: 12px; font-weight: 400; padding-top: 0"
            >
              Assign work for
              <strong><span id="modalEmployeeNameDisplay"></span></strong> during
              week of <strong><span id="modalWeekDisplay"></span></strong>
            </span>
          </h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>

        <div class="modal-body">
          <input type="hidden" id="modalUserId" name="user_id" value="" />
          <input type="hidden" id="modalWeek" name="week_start" value="" />

          <div class="custom-dropdown">
            <label for="engagementInput" class="form-label">Client Name</label>
            <div
              class="dropdown-btn"
              id="dropdownBtn"
              tabindex="0"
              aria-haspopup="listbox"
              aria-expanded="false"
              role="combobox"
              aria-labelledby="selectedClient"
            >
              <span id="selectedClient" class="text-muted">Select a client</span>
              <span>&#9662;</span>
            </div>

            <div
              class="dropdown-list"
              id="dropdownList"
              role="listbox"
              tabindex="-1"
              aria-labelledby="selectedClient"
            >
              <div
                class="dropdown-item"
                data-engagement-id="1"
                data-client-name="Client One"
                role="option"
                tabindex="0"
              >
                <div>
                  <span class="fw-semibold">Client One</span><br />
                  <small class="text-muted">Confirmed • 10 / 40 hrs</small>
                </div>
              </div>
              <div
                class="dropdown-item"
                data-engagement-id="2"
                data-client-name="Client Two"
                role="option"
                tabindex="0"
              >
                <div>
                  <span class="fw-semibold">Client Two</span><br />
                  <small class="text-muted">Pending • 5 / 20 hrs</small>
                </div>
              </div>
              <div
                class="dropdown-item"
                data-engagement-id="3"
                data-client-name="Client Three"
                role="option"
                tabindex="0"
              >
                <div>
                  <span class="fw-semibold">Client Three</span><br />
                  <small class="text-muted">Not Confirmed • 0 / 10 hrs</small>
                </div>
              </div>
            </div>

            <input
              type="hidden"
              id="engagementInput"
              name="engagement_id"
              required
            />
          </div>

          <div class="mb-3 mt-3">
            <label for="assignedHours" class="form-label">Hours</label>
            <input
              type="number"
              class="form-control"
              id="assignedHours"
              name="assigned_hours"
              min="0"
              step="0.25"
              required
            />
          </div>
        </div>

        <div class="modal-footer">
          <button
            type="button"
            class="btn badge text-black p-2 text-decoration-none fw-medium"
            style="font-size: 0.875rem; box-shadow: inset 0 0 0 1px rgb(229, 229, 229)"
            data-bs-dismiss="modal"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="btn badge text-white p-2 text-decoration-none fw-medium"
            style="font-size: 0.875rem; background-color: rgb(3, 2, 18); border: none !important"
          >
            Submit
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  document.addEventListener("DOMContentLoaded", () => {
    const dropdownBtn = document.getElementById("dropdownBtn");
    const dropdownList = document.getElementById("dropdownList");
    const selectedClient = document.getElementById("selectedClient");
    const engagementInput = document.getElementById("engagementInput");

    dropdownBtn.addEventListener("click", () => {
      const expanded = dropdownBtn.getAttribute("aria-expanded") === "true";
      dropdownBtn.setAttribute("aria-expanded", (!expanded).toString());
      dropdownList.style.display = expanded ? "none" : "block";
    });

    dropdownBtn.addEventListener("keydown", (e) => {
      if (e.key === "ArrowDown" || e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        dropdownList.style.display = "block";
        dropdownBtn.setAttribute("aria-expanded", "true");
        const firstItem = dropdownList.querySelector(".dropdown-item");
        if (firstItem) firstItem.focus();
      }
    });

    dropdownList.querySelectorAll(".dropdown-item").forEach((item) => {
      item.addEventListener("click", () => {
        selectClient(item);
      });
      item.addEventListener("keydown", (e) => {
        if (e.key === "Enter" || e.key === " ") {
          e.preventDefault();
          selectClient(item);
        } else if (e.key === "ArrowDown") {
          e.preventDefault();
          const next =
            item.nextElementSibling || dropdownList.querySelector(".dropdown-item");
          if (next) next.focus();
        } else if (e.key === "ArrowUp") {
          e.preventDefault();
          const prev =
            item.previousElementSibling ||
            dropdownList.querySelector(".dropdown-item:last-child");
          if (prev) prev.focus();
        } else if (e.key === "Escape") {
          closeDropdown();
          dropdownBtn.focus();
        }
      });
    });

    document.addEventListener("click", (e) => {
      if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
        closeDropdown();
      }
    });

    function selectClient(item) {
      const clientName = item.getAttribute("data-client-name");
      const engagementId = item.getAttribute("data-engagement-id");
      selectedClient.textContent = clientName;
      engagementInput.value = engagementId;
      closeDropdown();
    }

    function closeDropdown() {
      dropdownList.style.display = "none";
      dropdownBtn.setAttribute("aria-expanded", "false");
    }
  });
</script>

<!-- Button to trigger modal for testing -->
<button
  type="button"
  class="btn btn-primary mt-4"
  data-bs-toggle="modal"
  data-bs-target="#assignmentModal"
>
  Open Modal
</button>
