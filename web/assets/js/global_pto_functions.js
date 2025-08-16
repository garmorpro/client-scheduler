document.addEventListener("DOMContentLoaded", function () {
  const addForm = document.getElementById("addGlobalPTOForm");
  const ptoAccordion = document.getElementById("ptoAccordion");

  // --- Add new PTO ---
  addForm.addEventListener("submit", async function (e) {
    e.preventDefault();
    const formData = new FormData(addForm);

    try {
      const res = await fetch("add_global_pto.php", { method: "POST", body: formData });
      const data = await res.json();

      if (data.success) {
        const entry = data.entry; // {timeoff_id, week_start, assigned_hours, timeoff_note, week_start_raw}
        mergeOrCreateGroup(entry);
        addForm.reset();
      } else {
        alert("Error: " + data.error);
      }
    } catch (err) {
      console.error(err);
      alert("Something went wrong while adding PTO.");
    }
  });

  // --- Update existing PTO ---
  function attachUpdateHandlers() {
    document.querySelectorAll(".updatePTOForm").forEach(form => {
      form.addEventListener("submit", async function (e) {
        e.preventDefault();
        const formData = new FormData(form);

        try {
          const res = await fetch("update_global_pto.php", { method: "POST", body: formData });
          const data = await res.json();
          if (data.success) {
            reloadPTOAccordion();
          } else {
            alert("Error: " + data.error);
          }
        } catch (err) {
          console.error(err);
        }
      });
    });
  }

  // --- Delete PTO ---
  function attachDeleteHandlers() {
    document.querySelectorAll(".deletePTOBtn").forEach(btn => {
      btn.addEventListener("click", async function () {
        if (!confirm("Are you sure you want to delete this PTO entry?")) return;

        const id = this.dataset.id;
        const formData = new FormData();
        formData.append("timeoff_id", id);

        try {
          const res = await fetch("delete_global_pto.php", { method: "POST", body: formData });
          const data = await res.json();
          if (data.success) {
            reloadPTOAccordion();
          } else {
            alert("Error: " + data.error);
          }
        } catch (err) {
          console.error(err);
          alert("Something went wrong while deleting PTO.");
        }
      });
    });
  }

  attachUpdateHandlers();
  attachDeleteHandlers();

  // --- Merge or Create a Group Card ---
  function mergeOrCreateGroup(entry) {
    const note = entry.timeoff_note || 'No Note';
    const existingCard = ptoAccordion.querySelector(`.global-pto-card[data-note='${CSS.escape(note)}']`);

    if (existingCard) {
      // Merge into existing card
      const body = existingCard.querySelector(".card-body");
      body.insertAdjacentHTML("beforeend", makeEntryRow(entry));

      // Update weeks and hours
      const weeksEl = existingCard.querySelector(".group-weeks");
      const hoursEl = existingCard.querySelector(".group-hours");

      weeksEl.textContent += ", " + entry.week_start;
      hoursEl.textContent = (parseInt(hoursEl.textContent) + parseInt(entry.assigned_hours)) + " hrs";

      attachUpdateHandlers();
      attachDeleteHandlers();
    } else {
      // Create a new card at top
      const newIndex = document.querySelectorAll(".global-pto-card").length + 1;
      const cardHTML = `
        <div class="card shadow-sm global-pto-card" data-note="${note}" style="border-radius:6px;border:1px solid #e0e0e0;">
          <div class="card-header d-flex justify-content-between align-items-center" style="cursor:pointer;height:85px;" data-bs-toggle="collapse" data-bs-target="#collapse${newIndex}">
            <div>
              <p class="mb-1 fs-6 fw-semibold text-capitalize">${note}</p>
              <small class="text-muted group-weeks">Weeks: ${entry.week_start}</small>
            </div>
            <div class="d-flex align-items-center gap-3">
              <span class="fw-semibold group-hours">${entry.assigned_hours} hrs</span>
              <i class="bi bi-chevron-down text-muted"></i>
            </div>
          </div>
          <div id="collapse${newIndex}" class="collapse" data-bs-parent="#ptoAccordion">
            <div class="card-body d-flex flex-column gap-2">
              ${makeEntryRow(entry)}
            </div>
          </div>
        </div>
      `;
      ptoAccordion.insertAdjacentHTML("afterbegin", cardHTML);
      attachUpdateHandlers();
      attachDeleteHandlers();
    }
  }

  // --- Make Entry Row ---
  function makeEntryRow(entry) {
    return `
      <form action="update_global_pto.php" method="POST" class="updatePTOForm d-flex flex-row align-items-center gap-2 border p-2 rounded">
        <input type="hidden" name="timeoff_id" value="${entry.timeoff_id}">
        <div>
          <label class="form-label small mb-0">Week</label>
          <input type="date" name="week_start" value="${entry.week_start_raw}" class="form-control form-control-sm" required>
        </div>
        <div>
          <label class="form-label small mb-0">Hours</label>
          <input type="number" name="assigned_hours" value="${entry.assigned_hours}" class="form-control form-control-sm" min="0" required>
        </div>
        <div class="flex-fill">
          <label class="form-label small mb-0">Note</label>
          <input type="text" name="timeoff_note" value="${entry.timeoff_note}" class="form-control form-control-sm">
        </div>
        <div class="d-flex gap-2 align-items-end">
          <button type="submit" class="btn btn-sm btn-primary">Save</button>
          <button type="button" class="btn btn-sm btn-outline-danger deletePTOBtn" data-id="${entry.timeoff_id}">Delete</button>
        </div>
      </form>
    `;
  }

  // --- Reload PTO Accordion ---
  async function reloadPTOAccordion() {
    try {
      const res = await fetch("get_global_pto.php");
      const html = await res.text();
      ptoAccordion.innerHTML = html;
      attachUpdateHandlers();
      attachDeleteHandlers();
    } catch (err) {
      console.error(err);
    }
  }
});
