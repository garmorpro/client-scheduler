document.addEventListener("DOMContentLoaded", function () {
    const globalPtoContainer = document.getElementById("global-pto-table");

    // ---- Fake fetch for demo (replace with API call) ----
    async function fetchGlobalPTOs() {
        // Example: You’d replace this with a real `fetch('/api/global-pto')`
        return [
            { id: 1, timeoff_note: "Labor Day", week_start: "2025-08-11", assigned_hours: 8 },
            { id: 2, timeoff_note: "Labor Day", week_start: "2025-08-18", assigned_hours: 8 },
            { id: 3, timeoff_note: "Annual Training", week_start: "2025-09-01", assigned_hours: 16 }
        ];
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
        const d = new Date(ymd);
        return d.toLocaleDateString('en-US', { month: "short", day: "numeric" });
    }

    async function renderGlobalPTOs() {
        const entries = await fetchGlobalPTOs();
        const grouped = groupByNote(entries);

        globalPtoContainer.innerHTML = "";

        Object.keys(grouped).forEach((note, idx) => {
            const group = grouped[note];
            const totalHours = group.reduce((sum, e) => sum + e.assigned_hours, 0);
            const weeks = group.map(e => formatDateShort(e.week_start)).join(", ");

            // Card wrapper
            const card = document.createElement("div");
            card.className = "card mb-3 shadow-sm";

            // Card header (clickable accordion)
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
                            <form class="entry-form mb-3" data-id="${entry.id}">
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
                                    <div class="col-md-4">
                                        <label class="form-label mb-0 small">Note</label>
                                        <input type="text" class="form-control form-control-sm" 
                                               name="timeoff_note" value="${entry.timeoff_note}">
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button class="btn btn-sm btn-success w-100">Save</button>
                                    </div>
                                </div>
                            </form>
                        `).join("")}
                    </div>
                </div>
            `;

            globalPtoContainer.appendChild(card);
        });

        // Save form handlers
        document.querySelectorAll(".entry-form").forEach(form => {
            form.addEventListener("submit", function (e) {
                e.preventDefault();
                const id = form.dataset.id;
                const formData = Object.fromEntries(new FormData(form).entries());
                console.log("Save entry", id, formData);

                // TODO: call API to update entry
                // fetch(`/api/global-pto/${id}`, { method:"PUT", body: JSON.stringify(formData) })

                alert(`Entry ${id} saved!`);
            });
        });
    }

    renderGlobalPTOs();
});