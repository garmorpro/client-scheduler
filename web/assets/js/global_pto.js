document.addEventListener("DOMContentLoaded", function() {
    const startMonth = document.getElementById("startMonth");
    const endMonth = document.getElementById("endMonth");
    const startWeek = document.getElementById("startWeek");
    const endWeek = document.getElementById("endWeek");
    const weekContainer = document.getElementById("weekSelectorContainer");
    const dayContainer = document.getElementById("dayHoursContainer");
    const dayInputsDiv = document.getElementById("dayInputs");
    const summaryText = document.getElementById("summaryText");

    const pad2 = n => String(n).padStart(2, "0");
    function ymd(date) { return `${date.getFullYear()}-${pad2(date.getMonth()+1)}-${pad2(date.getDate())}`; }
    function parseLocalYMD(s) { const [Y,M,D] = s.split("-").map(Number); return new Date(Y, M-1, D); }

    function getMondaysInMonth(year, month) {
        const mondays = [];
        let date = new Date(year, month - 1, 1);
        while (date.getDay() !== 1) date.setDate(date.getDate() + 1);
        while (date.getMonth() === month - 1) { mondays.push(new Date(date)); date.setDate(date.getDate() + 7); }
        return mondays;
    }

    function formatDateLong(date) {
        return date.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
    }

    function populateWeekSelector(selector, month) {
        if (!month) return;
        const weeks = getMondaysInMonth(2025, parseInt(month, 10));
        selector.innerHTML = '<option value="">Select Week</option>';
        weeks.forEach(date => {
            const val = ymd(date);
            const label = formatDateLong(date);
            selector.innerHTML += `<option value="${val}">${label}</option>`;
        });
        weekContainer.style.display = "block";
        dayContainer.style.display = "none";
    }

    startMonth.addEventListener("change", () => populateWeekSelector(startWeek, startMonth.value));
    endMonth.addEventListener("change", () => populateWeekSelector(endWeek, endMonth.value));

    function generateDayInputs(start, end) {
        dayInputsDiv.innerHTML = "";
        const startMonday = parseLocalYMD(start);
        const endMonday = parseLocalYMD(end);
        let currentWeek = new Date(startMonday);

        while (currentWeek <= endMonday) {
            const rowDiv = document.createElement("div");
            rowDiv.className = "week-row d-flex align-items-center gap-2 mb-3";
            rowDiv.dataset.weekStart = ymd(currentWeek);

            for (let i = 0; i < 5; i++) {
                const d = new Date(currentWeek);
                d.setDate(d.getDate() + i);
                const dateStr = (d.getMonth() + 1) + '/' + d.getDate();

                const input = document.createElement("input");
                input.type = "number";
                input.min = 0;
                input.max = 10;
                input.value = 0;
                input.className = "form-control form-control-sm day-hour";
                input.style.width = "55px";
                input.dataset.date = ymd(d);

                const wrapper = document.createElement("div");
                wrapper.className = "d-flex flex-column align-items-center";
                const label = document.createElement("label");
                label.style.fontSize = "0.75rem";
                label.textContent = dateStr;
                wrapper.appendChild(label);
                wrapper.appendChild(input);
                rowDiv.appendChild(wrapper);
            }

            const weekTotalLabel = document.createElement("span");
            weekTotalLabel.textContent = "= 0 hrs";
            weekTotalLabel.style.fontWeight = "bold";
            weekTotalLabel.style.marginLeft = "15px";
            weekTotalLabel.className = "week-total";
            rowDiv.appendChild(weekTotalLabel);

            dayInputsDiv.appendChild(rowDiv);
            currentWeek.setDate(currentWeek.getDate() + 7);
        }

        dayContainer.style.display = "flex";
        document.querySelectorAll(".day-hour").forEach(input => input.addEventListener("input", updateTotals));
        updateTotals();

        function updateTotals() {
            let totalHours = 0;
            const weekRows = document.querySelectorAll(".week-row");
            weekRows.forEach(row => {
                let weekSum = 0;
                row.querySelectorAll(".day-hour").forEach(input => { weekSum += parseInt(input.value) || 0; });
                row.querySelector(".week-total").textContent = `= ${weekSum} hrs`;
                totalHours += weekSum;
            });
            summaryText.textContent = `Total Weeks: ${weekRows.length} | Total Hours: ${totalHours}`;
        }
    }

    startWeek.addEventListener("change", () => { if (startWeek.value && endWeek.value) generateDayInputs(startWeek.value, endWeek.value); });
    endWeek.addEventListener("change", () => { if (startWeek.value && endWeek.value) generateDayInputs(startWeek.value, endWeek.value); });

    const form = document.getElementById("addGlobalPTOForm");
    form.addEventListener("submit", function(e) {
        e.preventDefault();

        const note = document.getElementById("pto_note").value.trim();
        const entries = [];

        document.querySelectorAll(".week-row").forEach(row => {
            let weekSum = 0;
            row.querySelectorAll(".day-hour").forEach(input => weekSum += parseInt(input.value) || 0);
            if (weekSum > 0) {
                entries.push({
                    timeoff_note: note,
                    week_start: row.dataset.weekStart,
                    assigned_hours: weekSum,
                    is_global_timeoff: 1
                });
            }
        });

        if (entries.length === 0) return;

        fetch("add_global_pto.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ entries })
        })
        .then(async res => {
            if (!res.ok) throw new Error("Network response was not ok");
            const data = await res.json();
            return data;
        })
        .then(data => {
            if (data && data.success) {
                // Close modal first (Bootstrap example)
                const modalEl = document.getElementById("addGlobalPTO");
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }

                // Redirect after modal closes
                window.location.href = "/pages/admin-panel.php#time_off#global_pto";
            } else {
                console.error("Error saving PTO:", data);
                alert("Something went wrong while adding PTO.");
            }
        })
        .catch(err => {
            console.error(err);
            alert("Something went wrong while adding PTO.");
        });
    });
});
