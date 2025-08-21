document.addEventListener("DOMContentLoaded", function () {
    const globalPtoContainer = document.getElementById("global-pto-table");

    async function fetchGlobalPTOs() {
        try {
            const res = await fetch("get_global_pto.php");
            return await res.json();
        } catch (err) {
            console.error("Error fetching PTOs:", err);
            return [];
        }
    }

    function groupByNote(entries) {
        const groups = {};
        entries.forEach(e => {
            if (!groups[e.timeoff_note]) groups[e.timeoff_note] = [];
            groups[e.timeoff_note].push(e);
        });
        return groups;
    }

    function formatDateShort(ymd) {
        const [year, month, day] = ymd.split("-").map(Number);
        const d = new Date(year, month - 1, day);
        return d.toLocaleDateString('en-US', { month: "short", day: "numeric" });
    }

    async function renderGlobalPTOs() {
        const entries = await fetchGlobalPTOs();
        const grouped = groupByNote(entries);

        globalPtoContainer.innerHTML = "";

        Object.keys(grouped).forEach((note, idx) => {
            const group = grouped[note];
            const totalHours = group.reduce((sum, e) => sum + Number(e.assigned_hours), 0);
            const weeks = group.map(e => formatDateShort(e.week_start)).join(", ");

            const card = document.createElement("div");
            card.className = "card mb-3 shadow-sm";

            card.innerHTML = `
                <div class="card-header d-flex justify-content-between align-items-center" 
                     data-bs-toggle="collapse" 
                     data-bs-target="#collapse-${idx}" 
                     style="cursor:pointer;">
                    <div>
                        <strong>${note}</strong><br>
                        <small class="text-muted">Weeks: ${weeks}</small>
                    </div>
                    <div><span class="badge bg-primary">${totalHours} hrs</span></div>
                </div>
                <div id="collapse-${idx}" class="collapse">
                    <div class="card-body">
                        ${group.map(entry => `
                            <form class="entry-form mb-3" data-id="${entry.timeoff_id}">
                                <div class="row g-2 align-items-center">
                                    <div class="col-md-4">
                                        <label class="form-label mb-0 small">Week Start</label>
                                        <input type="date" class="form-control form-control-sm" 
                                               name="week_start" value="${entry.week_start}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-0 small">Assigned Hours</label>
                                        <input type="number" class="form-control form-control-sm" 
                                               name="assigned_hours" value="${entry.assigned_hours}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label mb-0 small">Note</label>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="timeoff_note" value="${entry.timeoff_note}">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end gap-1">
                                        <button type="submit" class="btn btn-sm save-btn-hover"><i class="bi bi-floppy2-fill"></i></button>
                                        <button type="button" class="btn btn-sm delete-btn-hover delete-entry"><i class="bi bi-trash"></i></button>
                                    </div>
                                </div>
                            </form>
                        `).join("")}
                    </div>
                </div>
            `;

            globalPtoContainer.appendChild(card);
        });

        // Save & Delete handlers
        document.querySelectorAll(".entry-form").forEach(form => {
            form.addEventListener("submit", async function (e) {
                e.preventDefault();
                const id = form.dataset.id;
                const formData = new FormData(form);

                try {
                    const res = await fetch(`update_global_pto.php?id=${id}`, {
                        method: "POST",
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        // alert(`Entry ${id} saved successfully!`);
                        renderGlobalPTOs(); // reload after save
                    } else {
                        alert(`Error saving entry ${id}: ${data.error}`);
                    }
                } catch (err) {
                    console.error(err);
                    alert("Network error while saving entry.");
                }
            });

            form.querySelector(".delete-entry").addEventListener("click", async function () {
                const id = form.dataset.id;
                // if (!confirm(`Are you sure you want to delete entry ${id}?`)) return;

                try {
                    const res = await fetch(`delete_global_pto.php?id=${id}`, { method: "POST" });
                    const data = await res.json();
                    if (data.success) {
                        // alert(`Entry ${id} deleted!`);
                        renderGlobalPTOs();
                    } else {
                        alert(`Error deleting entry ${id}: ${data.error}`);
                    }
                } catch (err) {
                    console.error(err);
                    alert("Network error while deleting entry.");
                }
            });
        });
    }

    renderGlobalPTOs();
});
